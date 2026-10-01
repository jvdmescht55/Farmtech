<?php

namespace App\Services\Herd;

use App\Models\AlertDismissal;
use App\Models\Animal;
use App\Models\AnimalEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Automatic early-warning system. Runs every rule over the farm's records
 * and returns what needs attention, most urgent first. Nothing is stored:
 * alerts appear the moment the data says so and disappear once it doesn't.
 *
 * Severity: critical (act today), warning (act this week), info (know about it).
 */
class HerdAlerts
{
    public const CATEGORIES = [
        'health' => 'Health & growth',
        'water' => 'Water & movement',
        'birth' => 'Births & youngstock',
        'breeding' => 'Breeding',
        'movement' => 'Where is it?',
        'withdrawal' => 'Withdrawal periods',
        'devices' => 'Devices',
        'data' => 'Records',
    ];

    /** @var array<string, Collection> per-request memo (layout badge + page share one run) */
    private static array $memo = [];

    public function __construct(private readonly WeighStats $stats) {}

    /** @return Collection<int, array> */
    public function forUser(int $userId, bool $includeDismissed = false): Collection
    {
        return self::$memo[$userId.'|'.(int) $includeDismissed] ??= $this->compute($userId, $includeDismissed);
    }

    private function compute(int $userId, bool $includeDismissed): Collection
    {
        $graph = PedigreeTier::graph($userId);
        $herd = $graph->where('in_herd', true);
        $active = $herd->where('status', 'active');
        $latest = $this->stats->latest($userId);
        $rows = $this->stats->rows($userId);
        $events = AnimalEvent::where('user_id', $userId)->get();
        $eventsByAnimal = $events->groupBy('animal_id');
        $rowsByAnimal = $rows->groupBy('animal_id');
        $today = now()->startOfDay();
        $todayDay = intdiv(strtotime($today->toDateString().' 12:00:00'), 86400);
        $dayOf = fn (string $date) => intdiv(strtotime($date.' 12:00:00'), 86400);

        $alerts = collect();
        $add = function (string $code, string $severity, string $category, ?Animal $a, string $title, string $detail, string $action = '') use ($alerts) {
            $alerts->push([
                'key' => $code.':'.($a?->id ?? '0'),
                'code' => $code,
                'severity' => $severity,
                'category' => $category,
                'animal' => $a,
                'title' => $title,
                'detail' => $detail,
                'action' => $action,
            ]);
        };

        // ── Growth & health, from weighings ──────────────────────────────
        $recentAdgBySpeciesAge = $latest->filter(fn ($l) => $l->adg !== null && $active->has($l->animal_id))
            ->groupBy(fn ($l) => $graph[$l->animal_id]->species.'|'.$this->ageBand($graph[$l->animal_id]));

        foreach ($latest as $l) {
            $a = $active->get($l->animal_id);
            if (! $a) {
                continue;
            }
            $recent = $dayOf($l->date) >= $todayDay - 45;

            if ($recent && $l->change !== null && $l->change < 0) {
                $prev = $l->kg - $l->change;
                $pct = $prev > 0 ? abs($l->change) / $prev * 100 : 0;
                if ($pct >= 5) {
                    $add('weight_drop', 'critical', 'health', $a, 'Sharp weight loss', sprintf('Lost %s kg (%.1f%%) since %s. Weight loss is the earliest sign of illness.', abs($l->change), $pct, date('j M', strtotime($l->series->slice(-2, 1)->first()->date ?? $l->date))), 'Check today — worms (FAMACHA), lameness or illness.');
                } else {
                    $add('weight_loss', 'warning', 'health', $a, 'Losing weight', abs($l->change).' kg down on the last weighing ('.$l->adg.' g/day).', 'Keep an eye on it next weighing; check grazing and worms.');
                }
            }

            // Ill-thrift: far behind animals of the same species and age.
            $cohort = $recentAdgBySpeciesAge->get($a->species.'|'.$this->ageBand($a));
            if ($recent && $l->adg !== null && $l->adg >= 0 && $cohort && $cohort->count() >= 8) {
                $median = WeighStats::summary($cohort->pluck('adg')->all())['median'];
                if ($median > 0 && $l->adg < $median * 0.5) {
                    $add('ill_thrift', 'warning', 'health', $a, 'Not thriving', "Growing {$l->adg} g/day against ".round($median).' g/day for others its age.', 'Check teeth, worms and feed; consider separating it.');
                }
            }

            // Implausible jump: probably a misread or wrong animal on the scale.
            if ($l->change !== null && $l->series->count() >= 2) {
                $prevRow = $l->series->slice(-2, 1)->first();
                $days = max(1, $dayOf($l->date) - $prevRow->day);
                $pct = $prevRow->kg > 0 ? abs($l->change) / $prevRow->kg * 100 : 0;
                if ($days <= 21 && $pct > $a->bio('max_jump_pct')) {
                    $add('weight_jump', 'info', 'data', $a, 'Odd weight jump', round($pct).'% change in '.$days.' days — probably a misread or the wrong animal on the scale.', 'Weigh again to confirm.');
                }
            }
        }

        // ── Movement: not seen / scanned when it shouldn't be ────────────
        foreach ($active as $a) {
            $seen = $a->last_seen_at;
            if ($seen && $seen->lt($today->copy()->subDays(120))) {
                $add('missing', 'warning', 'movement', $a, 'Not seen in ages', 'Last scanned '.$seen->diffForHumans().'.', 'Count the camp — lost, stolen or dead?');
            } elseif ($seen && $seen->lt($today->copy()->subDays(60))) {
                $add('not_seen', 'info', 'movement', $a, 'Not scanned lately', 'Last scanned '.$seen->diffForHumans().'.');
            }
        }
        foreach ($herd->whereIn('status', ['dead', 'sold']) as $a) {
            $since = $a->status_date;
            if (! $since) {
                continue;
            }
            $sinceDay = $dayOf($since->toDateString());
            $after = $rowsByAnimal->get($a->id, collect())->filter(fn ($r) => $r->day > $sinceDay);
            if ($after->isNotEmpty()) {
                $add('ghost_scan', 'warning', 'movement', $a, 'Scanned after it was '.($a->status === 'dead' ? 'marked dead' : 'sold'), 'Marked '.$a->status.' on '.$since->format('j M').', but read again on '.date('j M', strtotime($after->last()->date)).'.', 'Check the tag — it may be on the wrong animal or reused.');
            }
        }

        // ── Births & young stock ────────────────────────────────────────
        foreach ($active->filter(fn ($a) => $a->birth_date && $a->birth_date->gte($today->copy()->subDays(45))) as $a) {
            $birthLimit = $dayOf($a->birth_date->toDateString()) + 3;
            $birthRow = $rowsByAnimal->get($a->id, collect())->first(fn ($r) => $r->day <= $birthLimit);
            if ($birthRow && $birthRow->kg < $a->bio('low_birth_kg')) {
                $add('low_birth_weight', 'critical', 'birth', $a, 'Low birth weight', "{$birthRow->kg} kg at birth — below {$a->bio('low_birth_kg')} kg survival drops a lot.", 'Colostrum within 6 hours, warmth and shelter; consider bottle-feeding.');
            }
            if ((int) $a->birth_type >= 3) {
                $add('triplet', 'warning', 'birth', $a, 'Triplet', 'Triplets lose about 1 in 3 against 1 in 10 for singles.', 'Pen the dam with her lambs; extra feed and colostrum for each.');
            } elseif ($a->birth_type === '02') {
                $add('twin', 'info', 'birth', $a, 'Twin', 'Twins are lighter at birth and lose about 15%.', 'Make sure both are drinking; watch the dam\'s condition.');
            }
        }
        foreach ($active->filter(fn ($a) => $a->birth_date) as $a) {
            $age = $a->birth_date->diffInDays($today);
            $weaned = $eventsByAnimal->get($a->id, collect())->where('type', 'weaning')->isNotEmpty()
                || $rowsByAnimal->get($a->id, collect())->where('type', 'wean')->isNotEmpty();
            if (! $weaned && $age > $a->bio('wean_age') + 30 && $age < $a->bio('wean_age') + 200) {
                $add('wean_overdue', 'info', 'birth', $a, 'Weaning overdue', "{$age} days old and not recorded as weaned.", 'Wean, or record the weaning weight.');
            }
            $years = $age / 365;
            if ($a->sex === 'F' && $years > $a->bio('old_age_years')) {
                $add('old_dam', 'info', 'breeding', $a, 'Time to think about culling', round($years, 1).' years old.', 'Check teeth, udder and lambing record before the next mating season.');
            }
        }
        // Dams that lost young stock in the first 30 days.
        foreach ($herd->where('status', 'dead')->filter(fn ($a) => $a->birth_date && $a->dam_id && $a->status_date && $a->birth_date->diffInDays($a->status_date) <= 30 && $a->status_date->gte($today->copy()->subDays(60))) as $lamb) {
            if ($dam = $active->get($lamb->dam_id)) {
                $add('lamb_loss', 'warning', 'birth', $dam, 'Lost a lamb', $lamb->visual_id.' died at '.$lamb->birth_date->diffInDays($lamb->status_date).' days old.', 'Check the dam\'s udder and milk.');
            }
        }

        // ── Breeding ────────────────────────────────────────────────────
        $youngByDam = $herd->filter(fn ($x) => $x->dam_id && $x->birth_date)->groupBy('dam_id');
        foreach ($events->where('type', 'mating') as $e) {
            $a = $active->get($e->animal_id);
            if (! $a || $a->sex !== 'F') {
                continue;
            }
            $due = $e->date->copy()->addDays($a->bio('gestation'));
            $bornSince = ($youngByDam[$a->id] ?? collect())->contains(fn ($x) => $x->birth_date->gte($e->date->copy()->addDays(100)));
            $birthLogged = $eventsByAnimal->get($a->id, collect())->where('type', 'birth')->contains(fn ($b) => $b->date->gte($e->date));
            if ($bornSince || $birthLogged) {
                continue;
            }
            $days = (int) $today->diffInDays($due, false);
            if ($days >= 0 && $days <= 14) {
                $add('due', $days <= 3 ? 'warning' : 'info', 'breeding', $a, 'Due to '.($a->species === 'cattle' ? 'calve' : 'give birth').' soon', 'Expected around '.$due->format('j M').' ('.$days.' days).', 'Move to the maternity camp and keep watch.');
            } elseif ($days < -10 && $days > -60) {
                $add('overdue', 'warning', 'breeding', $a, 'Overdue', 'Was expected '.$due->format('j M').' — '.abs($days).' days ago.', 'Check whether she\'s actually pregnant.');
            }
        }
        foreach ($events->where('type', 'pregnancy_scan')->whereIn('result', ['twins', 'triplets']) as $e) {
            if (($a = $active->get($e->animal_id)) && $e->date->gte($today->copy()->subDays(150))) {
                $add('multiple_pregnancy', $e->result === 'triplets' ? 'warning' : 'info', 'breeding', $a, $e->result === 'triplets' ? 'Carrying triplets' : 'Carrying twins', 'Scanned '.$e->date->format('j M').'.', 'Group by litter size and feed for it in late pregnancy.');
            }
        }
        foreach ($events->where('type', 'pregnancy_scan')->where('result', 'empty') as $e) {
            if (($a = $active->get($e->animal_id)) && $e->date->gte($today->copy()->subDays(120))) {
                $add('empty', 'info', 'breeding', $a, 'Scanned empty', 'Not pregnant at the scan on '.$e->date->format('j M').'.', 'Re-mate, or cull if it keeps happening.');
            }
        }
        // Close relatives mated (from recorded pedigree).
        foreach ($active->filter(fn ($a) => $a->sire_id && $a->dam_id && $a->birth_date && $a->birth_date->gte($today->copy()->subYears(2))) as $a) {
            $s = $a->sire;
            $d = $a->dam;
            $why = match (true) {
                $d->sire_id && $d->sire_id === $s->id => 'the sire is also the dam\'s sire',
                $s->dam_id && $s->dam_id === $d->id => 'the dam is also the sire\'s dam',
                $s->sire_id && $s->sire_id === $d->sire_id => 'the parents share a sire (half-siblings)',
                $s->dam_id && $s->dam_id === $d->dam_id => 'the parents share a dam (half-siblings)',
                default => null,
            };
            if ($why) {
                $add('inbreeding', 'warning', 'breeding', $a, 'Inbreeding', 'Close relatives were mated: '.$why.'.', 'Avoid this pairing in future; watch this animal\'s growth and fertility.');
            }
        }

        // ── Withdrawal periods ──────────────────────────────────────────
        foreach ($events->filter(fn ($e) => $e->withdrawal_until && $e->withdrawal_until->gte($today)) as $e) {
            if ($a = $herd->get($e->animal_id)) {
                $add('withdrawal', 'info', 'withdrawal', $a, 'Withdrawal until '.$e->withdrawal_until->format('j M'), ($e->product ?: $e->label()).' on '.$e->date->format('j M').'.', 'Don\'t sell or slaughter before '.$e->withdrawal_until->format('j M Y').'.');
            }
        }

        // ── KraalTrac Watch: who hasn't been to the water ───────────────
        $watch = app(WatchStats::class);
        if ($watch->points($userId)->isNotEmpty()) {
            foreach ($watch->animals($userId)->where('missed', true) as $w) {
                $add('missed_drink', $w->hours_since > $w->limit * 2 ? 'critical' : 'warning', 'water', $w->animal,
                    'Hasn\'t been to drink', 'Last at '.$w->last_point.' '.$w->last_at->diffForHumans().' (limit '.$w->limit.' h).',
                    'Go find it — check the camp, fences and the animal itself.');
            }
            foreach ($watch->points($userId) as $p) {
                if ($p->last_synced_at && $p->last_synced_at->lt(now()->subHours(6))) {
                    $add('point_offline:'.$p->id, 'warning', 'devices', null, ($p->location ?: $p->name).' is quiet', 'No data from this water point since '.$p->last_synced_at->diffForHumans().'.', 'Check power, Wi-Fi and that the antenna is still in place.');
                }
            }
        }

        // ── Custom devices: readings outside the farmer's own limits ────
        foreach (\App\Models\Reader::where('user_id', $userId)->where('kind', 'custom')->get() as $dev) {
            $latest = \App\Models\DeviceReading::where('reader_id', $dev->id)->where('recorded_at', '>=', now()->subDay())
                ->orderByDesc('recorded_at')->get()->unique('metric')->keyBy('metric');
            foreach ($dev->metrics ?? [] as $m) {
                $r = $latest->get($m['key']);
                if (! $r) {
                    continue;
                }
                $low = $m['min'] !== null && $r->value < $m['min'];
                $high = $m['max'] !== null && $r->value > $m['max'];
                if ($low || $high) {
                    $add('device_limit:'.$dev->id.':'.$m['key'], 'warning', 'devices', null, $dev->name.': '.$m['label'].' '.($low ? 'low' : 'high'),
                        $r->value.($m['unit'] ? ' '.$m['unit'] : '').' at '.$r->recorded_at->format('H:i').' — your limit is '.($low ? 'min '.$m['min'] : 'max '.$m['max']).'.',
                        'Have a look at '.($dev->location ?: 'the device').'.');
                }
            }
        }

        // ── Records ─────────────────────────────────────────────────────
        foreach ($herd->whereNotNull('eid')->groupBy('eid')->filter(fn ($g) => $g->count() > 1) as $eid => $dupes) {
            foreach ($dupes as $a) {
                $add('duplicate_eid', 'critical', 'data', $a, 'Duplicate EID', "Tag {$eid} is on {$dupes->count()} animals.", 'Fix the records — scans may land on the wrong animal.');
            }
        }

        $dismissed = AlertDismissal::where('user_id', $userId)
            ->where(fn ($q) => $q->whereNull('until')->orWhere('until', '>', now()))
            ->pluck('alert_key')->flip();

        $rank = ['critical' => 0, 'warning' => 1, 'info' => 2];

        return $alerts
            ->map(fn ($x) => $x + ['dismissed' => $dismissed->has($x['key'])])
            ->when(! $includeDismissed, fn ($c) => $c->reject(fn ($x) => $x['dismissed']))
            ->sortBy(fn ($x) => $rank[$x['severity']].'|'.$x['category'].'|'.($x['animal']?->visual_id ?? ''))
            ->values();
    }

    /** Alerts for one animal — used on its page and in the live scan screen. */
    public function forAnimal(int $userId, int $animalId): Collection
    {
        return $this->forUser($userId)->filter(fn ($x) => $x['animal']?->id === $animalId)->values();
    }

    private function ageBand(Animal $a): string
    {
        if (! $a->birth_date) {
            return 'unknown';
        }
        $m = $a->birth_date->diffInMonths(now());

        return match (true) {
            $m < 4 => '0-4',
            $m < 8 => '4-8',
            $m < 14 => '8-14',
            default => 'adult',
        };
    }
}
