<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Services\Herd\PedigreeTier;
use App\Services\Herd\WeighStats;
use Illuminate\Http\Request;

/** Drafting / sorting: split animals into weight bands and project when each reaches target. */
class DraftController extends Controller
{
    public function index(Request $request, WeighStats $stats)
    {
        return view('rfid.draft', $this->build($request, $stats));
    }

    public function export(Request $request, WeighStats $stats)
    {
        $d = $this->build($request, $stats);

        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Group', 'Animal ID', 'EID', 'Sex', 'Weight kg', 'Weighed', 'ADG g/day', 'Days to target', 'Target date']);
            foreach ($d['bands'] as $band) {
                foreach ($band['animals'] as $r) {
                    fputcsv($out, [$band['label'], $r->animal->visual_id, $r->animal->eid, $r->animal->sex, $r->kg, $r->date, $r->adg, $r->days_to_target, $r->target_date]);
                }
            }
            fclose($out);
        }, 'draft-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function build(Request $request, WeighStats $stats): array
    {
        $userId = $request->user()->id;
        $speciesList = WeighStats::speciesIn($userId);
        $stats->forSpecies($speciesList->count() > 1 ? $request->input('species', $speciesList->keys()->first()) : null);
        $sessions = $stats->sessions($userId);
        $source = $request->input('source', 'latest');
        $sex = $request->input('sex');
        $target = (float) $request->input('target', 0) ?: null;
        $graph = PedigreeTier::graph($userId);

        if ($source !== 'latest' && $sessions->has($source)) {
            $rows = $sessions[$source]->rows->map(fn ($r) => clone $r);
        } else {
            $source = 'latest';
            // Only weights recent enough to act on.
            $rows = $stats->latest($userId)->filter(fn ($l) => \Carbon\Carbon::parse($l->date)->gte(now()->subDays(120)))
                ->map(fn ($l) => (object) ['animal_id' => $l->animal_id, 'kg' => $l->kg, 'date' => $l->date, 'adg' => $l->adg])->values();
        }

        $rows = $rows->map(function ($r) use ($graph, $target) {
            $r->animal = $graph->get($r->animal_id);
            $r->days_to_target = $target ? WeighStats::daysToTarget($r->kg, $r->adg, $target) : null;
            $r->target_date = $r->days_to_target !== null ? now()->addDays($r->days_to_target)->toDateString() : null;

            return $r;
        })->filter(fn ($r) => $r->animal && $r->animal->status === 'active' && (! $sex || $r->animal->sex === $sex))->sortBy('kg')->values();

        // Cut points: user-supplied, or tertiles of the current weights.
        $cuts = collect(preg_split('/[\s,;]+/', (string) $request->input('cuts')))->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v)->sort()->values();
        if ($cuts->isEmpty() && $rows->count() >= 3) {
            $kgs = $rows->pluck('kg')->values();
            $cuts = collect([$kgs[intdiv($kgs->count(), 3)], $kgs[intdiv($kgs->count() * 2, 3)]])->map(fn ($v) => round($v))->unique()->values();
        }

        $edges = collect([null])->concat($cuts)->push(null)->values();
        $bands = [];
        for ($i = 0; $i < $edges->count() - 1; $i++) {
            [$lo, $hi] = [$edges[$i], $edges[$i + 1]];
            $in = $rows->filter(fn ($r) => ($lo === null || $r->kg >= $lo) && ($hi === null || $r->kg < $hi))->values();
            $bands[] = [
                'label' => $lo === null ? "Under {$hi} kg" : ($hi === null ? "{$lo} kg +" : "{$lo} – {$hi} kg"),
                'animals' => $in,
                'stats' => WeighStats::summary($in->pluck('kg')->all()),
            ];
        }

        return [
            'sessionDates' => $sessions->keys(),
            'source' => $source,
            'sex' => $sex,
            'target' => $target,
            'cuts' => $cuts,
            'bands' => $bands,
            'total' => $rows->count(),
            'ready' => $target ? $rows->where('days_to_target', 0)->count() : null,
            'within30' => $target ? $rows->filter(fn ($r) => $r->days_to_target !== null && $r->days_to_target > 0 && $r->days_to_target <= 30)->count() : null,
            'stalled' => $target ? $rows->filter(fn ($r) => $r->days_to_target === null)->count() : null,
        ];
    }
}
