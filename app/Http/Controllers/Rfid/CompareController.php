<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Services\Herd\PedigreeTier;
use App\Services\Herd\WeighStats;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompareController extends Controller
{
    public function index(Request $request, WeighStats $stats)
    {
        $userId = $request->user()->id;
        $mode = $request->input('mode', 'groups');
        $sessions = $stats->sessions($userId);

        $data = ['mode' => $mode, 'sessionDates' => $sessions->keys()];

        if ($mode === 'sessions') {
            $a = $request->input('a', $sessions->keys()->get(1));
            $b = $request->input('b', $sessions->keys()->first());
            $data += $this->compareSessions($userId, $sessions, $a, $b);
        } else {
            $request->validate([
                'by' => ['nullable', Rule::in(array_keys(WeighStats::DIMENSIONS))],
                'metric' => ['nullable', Rule::in(array_keys(WeighStats::METRICS))],
            ]);
            $by = $request->input('by', 'sire');
            $metric = $request->input('metric', 'adg_life');
            $data += $this->compareGroups($userId, $stats, $by, $metric, $request->input('sex'));
        }

        return view('rfid.compare', $data);
    }

    private function compareGroups(int $userId, WeighStats $stats, string $by, string $metric, ?string $sex): array
    {
        $graph = PedigreeTier::graph($userId);
        $latest = $stats->latest($userId);

        $values = [];
        foreach ($latest as $l) {
            $a = $graph->get($l->animal_id);
            if (! $a || $a->status !== 'active' || ($sex && $a->sex !== $sex)) {
                continue;
            }
            $v = $stats->metricValue($l, $a, $metric);
            if ($v !== null) {
                $values[$stats->groupKey($a, $by)][] = ['v' => $v, 'animal' => $a];
            }
        }

        $all = collect($values)->flatten(1)->pluck('v')->all();
        $herd = WeighStats::summary($all);

        $groups = collect($values)->map(function ($items, $key) use ($herd) {
            $s = WeighStats::summary(array_column($items, 'v'));
            $best = collect($items)->sortByDesc('v')->first();

            return $s + [
                'key' => $key,
                'vs_herd' => $herd['mean'] ? round(($s['mean'] - $herd['mean']) / abs($herd['mean']) * 100, 1) : null,
                'best' => $best,
            ];
        })->sortByDesc('mean')->values();

        return compact('by', 'metric', 'sex', 'groups', 'herd');
    }

    private function compareSessions(int $userId, $sessions, ?string $a, ?string $b): array
    {
        if (! $a || ! $b || ! $sessions->has($a) || ! $sessions->has($b)) {
            return ['a' => $a, 'b' => $b, 'matched' => collect(), 'sa' => null, 'sb' => null];
        }
        [$a, $b] = $a > $b ? [$b, $a] : [$a, $b];
        $graph = PedigreeTier::graph($userId);
        $ra = $sessions[$a]->rows->keyBy('animal_id');
        $rb = $sessions[$b]->rows->keyBy('animal_id');
        $days = max(1, (int) \Carbon\Carbon::parse($a)->diffInDays($b));

        $matched = $rb->filter(fn ($r, $id) => $ra->has($id))->map(fn ($r, $id) => (object) [
            'animal' => $graph->get($id),
            'a' => $ra[$id]->kg,
            'b' => $r->kg,
            'change' => round($r->kg - $ra[$id]->kg, 1),
            'adg' => (int) round(($r->kg - $ra[$id]->kg) * 1000 / $days),
        ])->sortByDesc('adg')->values();

        return [
            'a' => $a, 'b' => $b, 'days' => $days, 'matched' => $matched,
            'sa' => $sessions[$a], 'sb' => $sessions[$b],
            'onlyA' => $ra->count() - $matched->count(), 'onlyB' => $rb->count() - $matched->count(),
            'adgSummary' => WeighStats::summary($matched->pluck('adg')->all()),
            'changeSummary' => WeighStats::summary($matched->pluck('change')->all()),
        ];
    }
}
