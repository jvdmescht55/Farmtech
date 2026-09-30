<?php

namespace App\Services\Herd;

use App\Models\Animal;
use App\Models\Scan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything a weigh-scale indicator would tell you, computed from the
 * farm's weight scans: sessions (one per weigh day), per-animal growth
 * (ADG in g/day), distributions, group comparisons and drafting.
 *
 * A "weighing" is one animal's last weight on a calendar day; a "session"
 * is every weighing on that day.
 */
class WeighStats
{
    /** @var array<string, Collection> memo of weighing rows per user+species */
    private array $rows = [];

    private ?string $species = null;

    /** Limit every figure to one species — sheep and cattle must never be averaged together. */
    public function forSpecies(?string $species): static
    {
        $this->species = $species && array_key_exists($species, config('herd.species')) ? $species : null;

        return $this;
    }

    /** Species present in the herd (for the switcher), most common first. */
    public static function speciesIn(int $userId): Collection
    {
        return Animal::where('user_id', $userId)->where('in_herd', true)->groupBy('species')
            ->selectRaw('species, count(*) c')->orderByDesc('c')->pluck('c', 'species');
    }

    public function __construct(private readonly PedigreeTier $tiers) {}

    /**
     * Every weighing with its previous one for the same animal.
     *
     * @return Collection<int, object{animal_id:int, date:string, at:Carbon, kg:float, type:?string, prev_kg:?float, prev_date:?string, days:?int, change:?float, adg:?int}>
     */
    public function rows(int $userId): Collection
    {
        $memo = $userId.'|'.$this->species;
        if (isset($this->rows[$memo])) {
            return $this->rows[$memo];
        }

        $herdIds = Animal::where('user_id', $userId)->where('in_herd', true)
            ->when($this->species, fn ($q) => $q->where('species', $this->species))
            ->pluck('id')->flip();

        $scans = Scan::where('user_id', $userId)
            ->whereNotNull('weight_kg')->whereNotNull('animal_id')
            ->orderBy('scanned_at')
            ->get(['animal_id', 'weight_kg', 'weigh_type', 'scanned_at'])
            ->filter(fn ($s) => $herdIds->has($s->animal_id));

        $rows = collect();
        foreach ($scans->groupBy('animal_id') as $animalId => $series) {
            $byDay = $series->groupBy(fn ($s) => $s->scanned_at->toDateString())->map->last()->values();
            $prev = null;
            foreach ($byDay as $s) {
                $date = $s->scanned_at->toDateString();
                $kg = (float) $s->weight_kg;
                $days = $prev ? max(1, (int) Carbon::parse($prev->date)->diffInDays($date)) : null;
                $row = (object) [
                    'animal_id' => (int) $animalId,
                    'date' => $date,
                    'at' => $s->scanned_at,
                    'kg' => $kg,
                    'type' => $s->weigh_type,
                    'prev_kg' => $prev?->kg,
                    'prev_date' => $prev?->date,
                    'days' => $days,
                    'change' => $prev ? round($kg - $prev->kg, 1) : null,
                    'adg' => $prev ? (int) round(($kg - $prev->kg) * 1000 / $days) : null,
                ];
                $rows->push($row);
                $prev = $row;
            }
        }

        return $this->rows[$memo] = $rows;
    }

    /** Sessions newest first, each with its summary stats. */
    public function sessions(int $userId): Collection
    {
        // Birth weights belong to each animal's curve, not to a "weigh day".
        $sessions = $this->rows($userId)->where('type', '!=', 'birth')->groupBy('date')->map(function (Collection $rows, $date) {
            $kgs = $rows->pluck('kg')->all();
            $adgs = $rows->pluck('adg')->filter(fn ($v) => $v !== null)->all();

            return (object) ([
                'date' => $date,
                'rows' => $rows,
                'adg' => self::summary($adgs),
                'lost' => $rows->filter(fn ($r) => $r->change !== null && $r->change < 0)->count(),
                'reweighed' => count($adgs),
            ] + ['kg' => self::summary($kgs)]);
        })->sortKeysDesc();

        // Change in the mean versus the previous session.
        $ordered = $sessions->values();
        foreach ($ordered as $i => $s) {
            $older = $ordered[$i + 1] ?? null;
            $s->mean_change = $older ? round($s->kg['mean'] - $older->kg['mean'], 1) : null;
            $s->prev_date = $older?->date;
        }

        return $sessions;
    }

    /**
     * Latest state of every weighed animal: current weight, recent and
     * lifetime ADG, number of weighings.
     *
     * @return Collection<int, object> keyed by animal_id
     */
    public function latest(int $userId): Collection
    {
        return $this->rows($userId)->groupBy('animal_id')->map(function (Collection $series) {
            $first = $series->first();
            $last = $series->last();
            $lifeDays = max(1, (int) Carbon::parse($first->date)->diffInDays($last->date));

            return (object) [
                'animal_id' => $last->animal_id,
                'kg' => $last->kg,
                'date' => $last->date,
                'change' => $last->change,
                'adg' => $last->adg,
                'adg_life' => $series->count() > 1 ? (int) round(($last->kg - $first->kg) * 1000 / $lifeDays) : null,
                'first_kg' => $first->kg,
                'first_date' => $first->date,
                'count' => $series->count(),
                'wean_kg' => $series->where('type', 'wean')->last()?->kg,
                'series' => $series,
            ];
        });
    }

    /** Weight at a given age (days) by linear interpolation between the two weighings that bracket it. */
    public static function weightAtAge(Collection $series, ?Carbon $birth, int $ageDays): ?float
    {
        if (! $birth) {
            return null;
        }
        $target = $birth->copy()->addDays($ageDays);
        $before = $series->filter(fn ($r) => Carbon::parse($r->date)->lte($target))->last();
        $after = $series->first(fn ($r) => Carbon::parse($r->date)->gte($target));
        if (! $before || ! $after) {
            return null;
        }
        if ($before->date === $after->date) {
            return $before->kg;
        }
        $span = Carbon::parse($before->date)->diffInDays($after->date);
        $t = Carbon::parse($before->date)->diffInDays($target) / max(1, $span);

        return round($before->kg + ($after->kg - $before->kg) * $t, 1);
    }

    /** Days until $target kg at the animal's recent ADG; null if not growing. */
    public static function daysToTarget(float $kg, ?int $adg, float $target): ?int
    {
        if ($kg >= $target) {
            return 0;
        }
        if (! $adg || $adg <= 0) {
            return null;
        }

        return (int) ceil(($target - $kg) * 1000 / $adg);
    }

    /** @return array{n:int, mean:?float, median:?float, min:?float, max:?float, sd:?float, cv:?float} */
    public static function summary(array $values): array
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== null));
        $n = count($values);
        if ($n === 0) {
            return ['n' => 0, 'mean' => null, 'median' => null, 'min' => null, 'max' => null, 'sd' => null, 'cv' => null];
        }
        sort($values);
        $mean = array_sum($values) / $n;
        $median = $n % 2 ? $values[intdiv($n, 2)] : ($values[$n / 2 - 1] + $values[$n / 2]) / 2;
        $sd = $n > 1 ? sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($n - 1)) : 0.0;

        return [
            'n' => $n,
            'mean' => round($mean, 1),
            'median' => round($median, 1),
            'min' => round($values[0], 1),
            'max' => round($values[$n - 1], 1),
            'sd' => round($sd, 1),
            'cv' => $mean != 0 ? round($sd / abs($mean) * 100, 1) : null,
        ];
    }

    /**
     * Equal-width histogram with "nice" bin edges.
     *
     * @return array<int, array{from: float, to: float, count: int}>
     */
    public static function histogram(array $values, int $targetBins = 10): array
    {
        $values = array_values(array_filter($values, fn ($v) => $v !== null));
        if (! $values) {
            return [];
        }
        $min = min($values);
        $max = max($values);
        $range = max(1e-9, $max - $min);
        $raw = $range / $targetBins;
        $mag = 10 ** floor(log10($raw));
        $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($s) => $s >= $raw) ?? $raw;
        $start = floor($min / $step) * $step;
        $bins = [];
        for ($from = $start; $from <= $max; $from += $step) {
            $bins[] = ['from' => round($from, 2), 'to' => round($from + $step, 2), 'count' => 0];
            if (count($bins) > 40) {
                break;
            }
        }
        foreach ($values as $v) {
            $i = min(count($bins) - 1, (int) floor(($v - $start) / $step));
            $bins[$i]['count']++;
        }

        return $bins;
    }

    /** Dimensions animals can be grouped by for comparison. */
    public const DIMENSIONS = [
        'sex' => 'Sex',
        'sire' => 'Sire (ram)',
        'birth_type' => 'Birth type',
        'tier' => 'Genetic tier',
        'birth_year' => 'Birth year',
        'breed' => 'Breed',
    ];

    public const METRICS = [
        'kg' => ['Latest weight', 'kg'],
        'adg' => ['Recent growth', 'g/dag'],
        'adg_life' => ['Lifetime growth', 'g/dag'],
        'wean_kg' => ['Weaning weight', 'kg'],
        'kg100' => ['100-day weight', 'kg'],
    ];

    public function groupKey(Animal $a, string $dimension): string
    {
        return match ($dimension) {
            'sex' => $a->sexLabel(),
            'sire' => $a->sire?->visual_id ?? 'Unknown sire',
            'birth_type' => $a->birth_type ? $a->birth_type.' · '.(config('herd.birth_types')[$a->birth_type] ?? '') : 'Unknown',
            'tier' => $this->tiers->resolve($a)->tier ?? 'Unknown',
            'birth_year' => $a->birth_date?->format('Y') ?? 'Unknown',
            'breed' => $a->breed ?? 'Unknown',
            default => '—',
        };
    }

    public function metricValue(object $latest, Animal $a, string $metric): ?float
    {
        return match ($metric) {
            'kg100' => self::weightAtAge($latest->series, $a->birth_date, 100),
            default => $latest->{$metric} ?? null,
        };
    }
}
