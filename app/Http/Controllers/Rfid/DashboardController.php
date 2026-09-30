<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Models\ReaderSync;
use App\Models\Scan;
use App\Services\Herd\PedigreeTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, PedigreeTier $tiers)
    {
        $user = $request->user();
        $graph = PedigreeTier::graph($user->id);
        $herd = $graph->where('in_herd', true)->where('status', 'active');

        $tierCounts = collect(array_reverse(config('herd.tiers')))->mapWithKeys(fn ($t) => [$t => 0])->put('?', 0);
        foreach ($herd as $animal) {
            $t = $tiers->resolve($animal)->tier ?? '?';
            $tierCounts[$t] = ($tierCounts[$t] ?? 0) + 1;
        }

        $since = now()->subDays(30);
        $monthly = Scan::where('user_id', $user->id)
            ->whereNotNull('weight_kg')
            ->where('scanned_at', '>=', now()->subMonths(6)->startOfMonth())
            ->get(['weight_kg', 'scanned_at'])
            ->groupBy(fn ($s) => $s->scanned_at->format('Y-m'))
            ->map(fn ($g) => ['avg' => round($g->avg('weight_kg'), 1), 'n' => $g->count()])
            ->sortKeys();

        return view('rfid.dashboard', [
            'user' => $user,
            'herdCount' => $herd->count(),
            'ewes' => $herd->where('sex', 'F')->count(),
            'rams' => $herd->where('sex', 'M')->count(),
            'unsexed' => $herd->whereNull('sex')->count(),
            'tierCounts' => $tierCounts,
            'noEid' => $herd->whereNull('eid')->count(),
            'noPedigree' => $herd->filter(fn ($a) => ! $a->sire_id || ! $a->dam_id)->count(),
            'notSeen' => $herd->filter(fn ($a) => ! $a->last_seen_at || $a->last_seen_at->lt($since))->count(),
            'scans30' => Scan::where('user_id', $user->id)->where('scanned_at', '>=', $since)->count(),
            'weighed30' => Scan::where('user_id', $user->id)->where('scanned_at', '>=', $since)->whereNotNull('weight_kg')->distinct('animal_id')->count('animal_id'),
            'monthly' => $monthly,
            'syncs' => ReaderSync::with('reader')->where('user_id', $user->id)->latest()->limit(6)->get(),
            'readers' => $user->readers()->orderByDesc('last_synced_at')->get(),
            'catalogues' => $user->catalogues()->withCount('lots')->latest()->limit(4)->get(),
            'recent' => $herd->sortByDesc('last_seen_at')->take(8),
            'byStatus' => $graph->where('in_herd', true)->groupBy('status')->map->count(),
        ]);
    }
}
