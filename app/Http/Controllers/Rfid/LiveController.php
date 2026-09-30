<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Services\Herd\HerdAlerts;
use App\Services\Herd\WeighStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Live scan screen: whatever the scanner sends appears here within seconds. */
class LiveController extends Controller
{
    public function index(Request $request)
    {
        return view('rfid.live', ['readers' => $request->user()->readers()->orderByDesc('last_synced_at')->get()]);
    }

    public function feed(Request $request, HerdAlerts $alerts)
    {
        $user = $request->user();
        $after = (int) $request->query('after', 0);

        $scans = Scan::with('animal.sire', 'animal.dam')
            ->where('user_id', $user->id)
            ->when($after, fn ($q) => $q->where('id', '>', $after), fn ($q) => $q->where('scanned_at', '>=', now()->subHours(12)))
            ->orderByDesc('id')->limit(40)->get()->reverse()->values();

        // Alerts are herd-wide computations — cache briefly so polling stays cheap.
        $byAnimal = Cache::remember("live-alerts:{$user->id}", 20, fn () => $alerts->forUser($user->id)
            ->filter(fn ($a) => $a['animal'])
            ->groupBy(fn ($a) => $a['animal']->id)
            ->map(fn ($g) => $g->map(fn ($a) => ['level' => $a['severity'], 'title' => $a['title']])->values()->all())
            ->all());

        $today = Scan::where('user_id', $user->id)->where('scanned_at', '>=', now()->startOfDay());

        return response()->json([
            'last_id' => $scans->last()?->id ?? $after,
            'today' => [
                'scans' => (clone $today)->count(),
                'animals' => (clone $today)->distinct('animal_id')->count('animal_id'),
                'avg_kg' => round((float) (clone $today)->whereNotNull('weight_kg')->avg('weight_kg'), 1) ?: null,
            ],
            'scans' => $scans->map(function (Scan $s) use ($byAnimal) {
                $a = $s->animal;
                $prev = $s->weight_kg !== null && $a
                    ? Scan::where('animal_id', $a->id)->whereNotNull('weight_kg')->where('scanned_at', '<', $s->scanned_at->copy()->startOfDay())->orderByDesc('scanned_at')->first()
                    : null;
                $days = $prev ? max(1, (int) $prev->scanned_at->startOfDay()->diffInDays($s->scanned_at->copy()->startOfDay())) : null;

                return [
                    'id' => $s->id,
                    'time' => $s->scanned_at->format('H:i:s'),
                    'eid' => $s->eid,
                    'animal' => $a?->visual_id,
                    'url' => $a ? route('rfid.animals.show', $a) : null,
                    'sex' => $a?->sexLabel(),
                    'age' => $a?->ageLabel(),
                    'sire' => $a?->sire?->visual_id,
                    'dam' => $a?->dam?->visual_id,
                    'weight' => $s->weight_kg !== null ? (float) $s->weight_kg : null,
                    'prev' => $prev ? (float) $prev->weight_kg : null,
                    'change' => $prev ? round($s->weight_kg - $prev->weight_kg, 1) : null,
                    'adg' => $prev ? (int) round(($s->weight_kg - $prev->weight_kg) * 1000 / $days) : null,
                    'is_new' => $a && $a->created_at->gte($s->created_at->copy()->subSeconds(5)) && ! $a->sex,
                    'alerts' => $a ? ($byAnimal[$a->id] ?? []) : [],
                ];
            }),
        ]);
    }
}
