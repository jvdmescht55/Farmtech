<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Models\ReaderSync;
use App\Models\Scan;
use App\Services\Herd\PedigreeTier;
use App\Services\Herd\WeighStats;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, PedigreeTier $tiers, WeighStats $stats)
    {
        $user = $request->user();
        $speciesList = WeighStats::speciesIn($user->id);
        $species = $request->input('species', $speciesList->keys()->first());
        $stats->forSpecies($speciesList->count() > 1 ? $species : null);
        $graph = PedigreeTier::graph($user->id);
        $herd = $graph->where('in_herd', true)->where('status', 'active')
            ->when($speciesList->count() > 1, fn ($c) => $c->where('species', $species));

        $sessions = $stats->sessions($user->id);
        $latestSession = $sessions->first();
        $latest = $stats->latest($user->id)->filter(fn ($l) => $herd->has($l->animal_id));

        $tierCounts = collect(array_reverse(config('herd.tiers')))->mapWithKeys(fn ($t) => [$t => 0])->put('?', 0);
        foreach ($herd as $animal) {
            $t = $tiers->resolve($animal)->tier ?? '?';
            $tierCounts[$t] = ($tierCounts[$t] ?? 0) + 1;
        }

        $withAdg = $latest->filter(fn ($l) => $l->adg !== null && Carbon::parse($l->date)->gte(now()->subDays(60)));
        $stale = $herd->filter(fn ($a) => ! $latest->has($a->id) || Carbon::parse($latest[$a->id]->date)->lt(now()->subDays(60)));

        return view('rfid.dashboard', [
            'user' => $user,
            'herdCount' => $herd->count(),
            'ewes' => $herd->where('sex', 'F')->count(),
            'rams' => $herd->where('sex', 'M')->count(),
            'latestSession' => $latestSession,
            'avgKg' => WeighStats::summary($latest->filter(fn ($l) => Carbon::parse($l->date)->gte(now()->subDays(120)))->pluck('kg')->all()),
            'avgAdg' => WeighStats::summary($withAdg->pluck('adg')->all()),
            'trend' => $sessions->reverse()->take(-12)->map(fn ($s) => ['label' => $s->date, 'value' => $s->kg['mean']])->values()->all(),
            'adgTrend' => $sessions->reverse()->take(-12)->filter(fn ($s) => $s->adg['n'])->map(fn ($s) => ['label' => $s->date, 'value' => $s->adg['mean']])->values()->all(),
            'histogram' => WeighStats::histogram($latest->filter(fn ($l) => Carbon::parse($l->date)->gte(now()->subDays(120)))->pluck('kg')->all()),
            'speciesList' => $speciesList,
            'species' => $species,
            'alerts' => app(\App\Services\Herd\HerdAlerts::class)->forUser($user->id)->take(6),
            'gainers' => $withAdg->sortByDesc('adg')->take(5)->map(fn ($l) => [$graph->get($l->animal_id), $l]),
            'laggers' => $withAdg->sortBy('adg')->take(5)->map(fn ($l) => [$graph->get($l->animal_id), $l]),
            'lost' => $latestSession ? $latestSession->rows->filter(fn ($r) => $r->change !== null && $r->change < 0)->count() : 0,
            'stale' => $stale->count(),
            'noEid' => $herd->whereNull('eid')->count(),
            'noPedigree' => $herd->filter(fn ($a) => ! $a->sire_id || ! $a->dam_id)->count(),
            'tierCounts' => $tierCounts,
            'scans30' => Scan::where('user_id', $user->id)->where('scanned_at', '>=', now()->subDays(30))->count(),
            'syncs' => ReaderSync::with('reader')->where('user_id', $user->id)->latest()->limit(5)->get(),
            'sessionCount' => $sessions->count(),
            'tourSeen' => $user->hasSeenTour('rfid'),
            'deviceCount' => $user->readers()->where('kind', 'handheld')->count(),
            'bookCount' => $user->catalogues()->count(),
        ]);
    }
}
