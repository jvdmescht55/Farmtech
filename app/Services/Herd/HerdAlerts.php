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
        'health' => 'Gesondheid & groei',
        'birth' => 'Geboortes & lammers',
        'breeding' => 'Teling',
        'movement' => 'Waar is hy?',
        'withdrawal' => 'Onttrekkingstydperk',
        'data' => 'Rekords',
    ];

    public function __construct(private readonly WeighStats $stats) {}

    /** @return Collection<int, array> */
    public function forUser(int $userId, bool $includeDismissed = false): Collection
    {
        $graph = PedigreeTier::graph($userId);
        $herd = $graph->where('in_herd', true);
        $active = $herd->where('status', 'active');
        $latest = $this->stats->latest($userId);
        $rows = $this->stats->rows($userId);
        $events = AnimalEvent::where('user_id', $userId)->get();
        $today = now()->startOfDay();

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
            $recent = Carbon::parse($l->date)->gte($today->copy()->subDays(45));

            if ($recent && $l->change !== null && $l->change < 0) {
                $prev = $l->kg - $l->change;
                $pct = $prev > 0 ? abs($l->change) / $prev * 100 : 0;
                if ($pct >= 5) {
                    $add('weight_drop', 'critical', 'health', $a, 'Skerp gewigsverlies', sprintf('%s kg verloor (%.1f%%) sedert %s. Gewigsverlies is die vroegste teken van siekte.', abs($l->change), $pct, Carbon::parse($l->series->slice(-2, 1)->first()->date ?? $l->date)->format('j M')), 'Ondersoek vandag — kyk vir interne parasiete (FAMACHA), kreupelheid of siekte.');
                } else {
                    $add('weight_loss', 'warning', 'health', $a, 'Verloor gewig', abs($l->change).' kg minder as by die vorige weging ('.$l->adg.' g/dag).', 'Hou dop by die volgende weging; kontroleer weiding en parasiete.');
                }
            }

            // Ill-thrift: far behind animals of the same species and age.
            $cohort = $recentAdgBySpeciesAge->get($a->species.'|'.$this->ageBand($a));
            if ($recent && $l->adg !== null && $l->adg >= 0 && $cohort && $cohort->count() >= 8) {
                $median = WeighStats::summary($cohort->pluck('adg')->all())['median'];
                if ($median > 0 && $l->adg < $median * 0.5) {
                    $add('ill_thrift', 'warning', 'health', $a, 'Groei sukkel (ill-thrift)', "Groei {$l->adg} g/dag teenoor die groep se mediaan van ".round($median).' g/dag.', 'Tande, parasiete en voeding nagaan; oorweeg om te skei.');
                }
            }

            // Implausible jump: probably a misread or wrong animal on the scale.
            if ($l->change !== null && $l->series->count() >= 2) {
                $prevRow = $l->series->slice(-2, 1)->first();
                $days = max(1, Carbon::parse($prevRow->date)->diffInDays($l->date));
                $pct = $prevRow->kg > 0 ? abs($l->change) / $prevRow->kg * 100 : 0;
                if ($days <= 21 && $pct > $a->bio('max_jump_pct')) {
                    $add('weight_jump', 'info', 'data', $a, 'Ongewone gewigsprong', round($pct).'% verandering in '.$days.' dae — moontlik \'n foutiewe lesing of verkeerde dier op die skaal.', 'Herweeg om te bevestig.');
                }
            }
        }

        // ── Movement: not seen / scanned when it shouldn't be ────────────
        foreach ($active as $a) {
            $seen = $a->last_seen_at;
            if ($seen && $seen->lt($today->copy()->subDays(120))) {
                $add('missing', 'warning', 'movement', $a, 'Lanklaas gesien', 'Laas geskandeer '.$seen->diffForHumans().'.', 'Tel die kamp — verlore, gesteel of dood?');
            } elseif ($seen && $seen->lt($today->copy()->subDays(60))) {
                $add('not_seen', 'info', 'movement', $a, 'Nie onlangs geskandeer nie', 'Laas geskandeer '.$seen->diffForHumans().'.');
            }
        }
        foreach ($herd->whereIn('status', ['dead', 'sold']) as $a) {
            $since = $a->status_date;
            if (! $since) {
                continue;
            }
            $after = $rows->where('animal_id', $a->id)->filter(fn ($r) => Carbon::parse($r->date)->gt($since));
            if ($after->isNotEmpty()) {
                $add('ghost_scan', 'warning', 'movement', $a, 'Geskandeer ná '.($a->status === 'dead' ? 'dood' : 'verkoop'), 'Gemerk as '.$a->status.' op '.$since->format('j M').', maar weer gelees op '.Carbon::parse($after->last()->date)->format('j M').'.', 'Kontroleer die oormerk — dalk op die verkeerde dier of hergebruik.');
            }
        }

        // ── Births & young stock ────────────────────────────────────────
        foreach ($active->filter(fn ($a) => $a->birth_date && $a->birth_date->gte($today->copy()->subDays(45))) as $a) {
            $birthRow = $rows->where('animal_id', $a->id)->first(fn ($r) => Carbon::parse($r->date)->lte($a->birth_date->copy()->addDays(3)));
            if ($birthRow && $birthRow->kg < $a->bio('low_birth_kg')) {
                $add('low_birth_weight', 'critical', 'birth', $a, 'Lae geboortegewig', "{$birthRow->kg} kg by geboorte — onder {$a->bio('low_birth_kg')} kg is oorlewing baie laer.", 'Sorg vir biesmelk (colostrum) binne 6 uur, warmte en skuiling; oorweeg bottel.');
            }
            if ((int) $a->birth_type >= 3) {
                $add('triplet', 'warning', 'birth', $a, 'Drieling', 'Drielinge het omtrent 33% sterfte teenoor 10% vir enkelinge.', 'Skei die ooi met haar lammers; ekstra voer en biesmelk vir elke lam.');
            } elseif ($a->birth_type === '02') {
                $add('twin', 'info', 'birth', $a, 'Tweeling', 'Tweelinge is ligter by geboorte en het ~15% sterfte.', 'Maak seker albei suip; hou die ooi se kondisie dop.');
            }
        }
        foreach ($active->filter(fn ($a) => $a->birth_date) as $a) {
            $age = $a->birth_date->diffInDays($today);
            $weaned = $events->where('animal_id', $a->id)->where('type', 'weaning')->isNotEmpty()
                || $rows->where('animal_id', $a->id)->where('type', 'wean')->isNotEmpty();
            if (! $weaned && $age > $a->bio('wean_age') + 30 && $age < $a->bio('wean_age') + 200) {
                $add('wean_overdue', 'info', 'birth', $a, 'Speen agterstallig', "{$age} dae oud en nog nie as gespeen aangeteken nie.", 'Speen of teken die speengewig aan.');
            }
            $years = $age / 365;
            if ($a->sex === 'F' && $years > $a->bio('old_age_years')) {
                $add('old_dam', 'info', 'breeding', $a, 'Oorweeg uitskot', round($years, 1).' jaar oud.', 'Kyk na tande, uier en lamrekord voor die volgende paarseisoen.');
            }
        }
        // Dams that lost young stock in the first 30 days.
        foreach ($herd->where('status', 'dead')->filter(fn ($a) => $a->birth_date && $a->dam_id && $a->status_date && $a->birth_date->diffInDays($a->status_date) <= 30 && $a->status_date->gte($today->copy()->subDays(60))) as $lamb) {
            if ($dam = $active->get($lamb->dam_id)) {
                $add('lamb_loss', 'warning', 'birth', $dam, 'Het lam verloor', $lamb->visual_id.' het binne '.$lamb->birth_date->diffInDays($lamb->status_date).' dae gevrek.', 'Kontroleer die ooi se uier en melkproduksie.');
            }
        }

        // ── Breeding ────────────────────────────────────────────────────
        foreach ($events->where('type', 'mating') as $e) {
            $a = $active->get($e->animal_id);
            if (! $a || $a->sex !== 'F') {
                continue;
            }
            $due = $e->date->copy()->addDays($a->bio('gestation'));
            $bornSince = $herd->contains(fn ($x) => $x->dam_id === $a->id && $x->birth_date && $x->birth_date->gte($e->date->copy()->addDays(100)));
            $birthLogged = $events->where('animal_id', $a->id)->where('type', 'birth')->contains(fn ($b) => $b->date->gte($e->date));
            if ($bornSince || $birthLogged) {
                continue;
            }
            $days = (int) $today->diffInDays($due, false);
            if ($days >= 0 && $days <= 14) {
                $add('due', $days <= 3 ? 'warning' : 'info', 'breeding', $a, 'Moet binnekort '.($a->species === 'cattle' ? 'kalf' : 'lam'), 'Verwag omtrent '.$due->format('j M').' ('.$days.' dae).', 'Skuif na die lamkamp en hou dop.');
            } elseif ($days < -10 && $days > -60) {
                $add('overdue', 'warning', 'breeding', $a, 'Oor tyd', 'Was verwag op '.$due->format('j M').' — '.abs($days).' dae gelede.', 'Kontroleer of sy dragtig is; dalk leeg.');
            }
        }
        foreach ($events->where('type', 'pregnancy_scan')->whereIn('result', ['twins', 'triplets']) as $e) {
            if (($a = $active->get($e->animal_id)) && $e->date->gte($today->copy()->subDays(150))) {
                $add('multiple_pregnancy', $e->result === 'triplets' ? 'warning' : 'info', 'breeding', $a, $e->result === 'triplets' ? 'Dra drieling' : 'Dra tweeling', 'Geskandeer op '.$e->date->format('j M').'.', 'Groepeer volgens vrugte en voer daarvolgens in laat dragtigheid.');
            }
        }
        foreach ($events->where('type', 'pregnancy_scan')->where('result', 'empty') as $e) {
            if (($a = $active->get($e->animal_id)) && $e->date->gte($today->copy()->subDays(120))) {
                $add('empty', 'info', 'breeding', $a, 'Leeg geskandeer', 'Nie dragtig by die skandering op '.$e->date->format('j M').' nie.', 'Herpaar of oorweeg uitskot as dit herhaal.');
            }
        }
        // Close relatives mated (from recorded pedigree).
        foreach ($active->filter(fn ($a) => $a->sire_id && $a->dam_id && $a->birth_date && $a->birth_date->gte($today->copy()->subYears(2))) as $a) {
            $s = $a->sire;
            $d = $a->dam;
            $why = match (true) {
                $d->sire_id && $d->sire_id === $s->id => 'die vader is ook die ma se vader',
                $s->dam_id && $s->dam_id === $d->id => 'die ma is ook die vader se ma',
                $s->sire_id && $s->sire_id === $d->sire_id => 'ouers het dieselfde vader (halfsibbe)',
                $s->dam_id && $s->dam_id === $d->dam_id => 'ouers het dieselfde ma (halfsibbe)',
                default => null,
            };
            if ($why) {
                $add('inbreeding', 'warning', 'breeding', $a, 'Inteling', 'Nabye familie gepaar: '.$why.'.', 'Vermy hierdie paring in die toekoms; hou die dier se groei en vrugbaarheid dop.');
            }
        }

        // ── Withdrawal periods ──────────────────────────────────────────
        foreach ($events->filter(fn ($e) => $e->withdrawal_until && $e->withdrawal_until->gte($today)) as $e) {
            if ($a = $herd->get($e->animal_id)) {
                $add('withdrawal', 'info', 'withdrawal', $a, 'Onttrekking tot '.$e->withdrawal_until->format('j M'), ($e->product ?: $e->label()).' op '.$e->date->format('j M').'.', 'Moenie verkoop of slag voor '.$e->withdrawal_until->format('j M Y').' nie.');
            }
        }

        // ── Records ─────────────────────────────────────────────────────
        foreach ($herd->whereNotNull('eid')->groupBy('eid')->filter(fn ($g) => $g->count() > 1) as $eid => $dupes) {
            foreach ($dupes as $a) {
                $add('duplicate_eid', 'critical', 'data', $a, 'Dubbele EID', "Oormerk {$eid} is op {$dupes->count()} diere.", 'Maak die rekords reg — skanderings kan by die verkeerde dier beland.');
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
