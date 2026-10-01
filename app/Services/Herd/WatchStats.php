<?php

namespace App\Services\Herd;

use App\Models\Animal;
use App\Models\Reader;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * KraalTrac Watch: tag reads from devices at gates and water points.
 *
 * Reads of the same animal at the same point within VISIT_GAP minutes are
 * one visit (an ewe standing at the trough gets read over and over). An
 * animal is "expected" at the water if a Watch device has seen it in the
 * last LOOKBACK days — so animals in another camp aren't flagged.
 */
class WatchStats
{
    public const VISIT_GAP = 30;      // minutes

    public const LOOKBACK = 14;       // days

    public const DEFAULT_HOURS = 24;  // missed-drink limit when a point has none set

    private array $memo = [];

    public function points(int $userId): Collection
    {
        return Reader::where('user_id', $userId)->where('kind', 'watch')->orderBy('name')->get();
    }

    /** @return Collection<int, object{reader_id:int, animal_id:int, at:int}> visits in the window, oldest first */
    public function visits(int $userId, int $days = self::LOOKBACK): Collection
    {
        $key = "$userId|$days";
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $points = $this->points($userId)->pluck('id');
        if ($points->isEmpty()) {
            return $this->memo[$key] = collect();
        }

        $reads = DB::table('scans')
            ->join('reader_syncs', 'reader_syncs.id', '=', 'scans.reader_sync_id')
            ->whereIn('reader_syncs.reader_id', $points)
            ->whereNotNull('scans.animal_id')
            ->where('scans.scanned_at', '>=', now()->subDays($days))
            ->orderBy('scans.scanned_at')
            ->get(['reader_syncs.reader_id', 'scans.animal_id', 'scans.scanned_at']);

        $last = [];
        $visits = collect();
        foreach ($reads as $r) {
            $t = strtotime($r->scanned_at);
            $k = $r->reader_id.'|'.$r->animal_id;
            if (! isset($last[$k]) || $t - $last[$k] > self::VISIT_GAP * 60) {
                $visits->push((object) ['reader_id' => (int) $r->reader_id, 'animal_id' => (int) $r->animal_id, 'at' => $t]);
            }
            $last[$k] = $t;
        }

        return $this->memo[$key] = $visits;
    }

    /** Animals that should be coming to the water, with when they last did. */
    public function animals(int $userId): Collection
    {
        $points = $this->points($userId)->keyBy('id');
        $visits = $this->visits($userId);
        $animals = Animal::where('user_id', $userId)->where('in_herd', true)->where('status', 'active')
            ->whereIn('id', $visits->pluck('animal_id')->unique())->get()->keyBy('id');
        $now = time();

        return $visits->groupBy('animal_id')->map(function (Collection $v, $animalId) use ($animals, $points, $now) {
            $a = $animals->get($animalId);
            if (! $a) {
                return null;
            }
            $last = $v->last();
            $limit = $v->pluck('reader_id')->unique()->map(fn ($id) => $points[$id]->alert_hours ?? self::DEFAULT_HOURS)->min();
            $hoursSince = ($now - $last->at) / 3600;
            $perDay = $v->groupBy(fn ($x) => date('Y-m-d', $x->at))->map->count();

            return (object) [
                'animal' => $a,
                'last_at' => Carbon::createFromTimestamp($last->at, config('app.timezone')),
                'last_point' => $points[$last->reader_id]->location ?: $points[$last->reader_id]->name,
                'hours_since' => round($hoursSince, 1),
                'limit' => $limit,
                'missed' => $hoursSince > $limit,
                'visits_7d' => $v->filter(fn ($x) => $x->at >= $now - 7 * 86400)->count(),
                'avg_per_day' => round($perDay->avg(), 1),
            ];
        })->filter()->sortByDesc('hours_since')->values();
    }

    /** Unique animals per day for the last N days. */
    public function headcount(int $userId, int $days = 14): array
    {
        $byDay = $this->visits($userId)->groupBy(fn ($v) => date('Y-m-d', $v->at))->map(fn ($g) => $g->pluck('animal_id')->unique()->count());
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $out[] = ['label' => $d, 'value' => $byDay[$d] ?? 0];
        }

        return $out;
    }

    /** Visits per hour of day over the last 7 days — shows when they actually drink. */
    public function hourly(int $userId): array
    {
        $since = time() - 7 * 86400;
        $h = array_fill(0, 24, 0);
        foreach ($this->visits($userId)->filter(fn ($v) => $v->at >= $since) as $v) {
            $h[(int) date('G', $v->at)]++;
        }

        return $h;
    }

    /** Per point: visits today, animals today, last read. */
    public function pointSummary(int $userId): Collection
    {
        $today = strtotime('today');
        $visits = $this->visits($userId)->filter(fn ($v) => $v->at >= $today)->groupBy('reader_id');

        return $this->points($userId)->map(fn (Reader $p) => (object) [
            'point' => $p,
            'visits_today' => $visits->get($p->id, collect())->count(),
            'animals_today' => $visits->get($p->id, collect())->pluck('animal_id')->unique()->count(),
        ]);
    }
}
