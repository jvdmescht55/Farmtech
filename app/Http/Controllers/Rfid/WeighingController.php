<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Services\Herd\PedigreeTier;
use App\Services\Herd\WeighStats;
use Illuminate\Http\Request;

class WeighingController extends Controller
{
    public function index(Request $request, WeighStats $stats)
    {
        $sessions = $stats->sessions($request->user()->id);

        return view('rfid.weighings.index', [
            'sessions' => $sessions,
            'trend' => $sessions->reverse()->map(fn ($s) => ['label' => $s->date, 'value' => $s->kg['mean']])->values()->all(),
        ]);
    }

    public function show(Request $request, string $date, WeighStats $stats)
    {
        $sessions = $stats->sessions($request->user()->id);
        $session = $sessions->get($date) ?? abort(404);
        $graph = PedigreeTier::graph($request->user()->id);

        $sort = $request->input('sort', 'kg');
        $rows = $session->rows->map(function ($r) use ($graph) {
            $r->animal = $graph->get($r->animal_id);

            return $r;
        });
        $rows = match ($sort) {
            'adg' => $rows->sortByDesc(fn ($r) => $r->adg ?? PHP_INT_MIN),
            'change' => $rows->sortBy(fn ($r) => $r->change ?? PHP_INT_MAX),
            'id' => $rows->sortBy(fn ($r) => $r->animal->visual_id, SORT_NATURAL),
            default => $rows->sortByDesc('kg'),
        };

        $keys = $sessions->keys()->values();
        $pos = $keys->search($date);

        return view('rfid.weighings.show', [
            'session' => $session,
            'rows' => $rows->values(),
            'histogram' => WeighStats::histogram($session->rows->pluck('kg')->all()),
            'adgHistogram' => WeighStats::histogram($session->rows->pluck('adg')->all(), 8),
            'newer' => $pos > 0 ? $keys[$pos - 1] : null,
            'older' => $keys[$pos + 1] ?? null,
            'bySex' => $rows->groupBy(fn ($r) => $r->animal->sexLabel())->map(fn ($g) => WeighStats::summary($g->pluck('kg')->all())),
        ]);
    }

    public function export(Request $request, string $date, WeighStats $stats)
    {
        $session = $stats->sessions($request->user()->id)->get($date) ?? abort(404);
        $graph = PedigreeTier::graph($request->user()->id);

        return response()->streamDownload(function () use ($session, $graph) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Animal ID', 'EID', 'Sex', 'Weight kg', 'Previous kg', 'Previous date', 'Days', 'Change kg', 'ADG g/day']);
            foreach ($session->rows->sortByDesc('kg') as $r) {
                $a = $graph->get($r->animal_id);
                fputcsv($out, [$a->visual_id, $a->eid, $a->sex, $r->kg, $r->prev_kg, $r->prev_date, $r->days, $r->change, $r->adg]);
            }
            fclose($out);
        }, "weigh-session-{$date}.csv", ['Content-Type' => 'text/csv']);
    }
}
