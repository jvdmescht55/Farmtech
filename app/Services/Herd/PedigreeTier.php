<?php

namespace App\Services\Herd;

use App\Models\Animal;
use Illuminate\Support\Collection;

/**
 * Works out an animal's genetic tier from its pedigree rather than its
 * performance: an offspring enters one step above the weaker parent on the
 * config('herd.tiers') ladder, capped at the top (SP). Any commercial or
 * foundation ancestor therefore holds descendants back until enough
 * generations of stud sires have been used.
 */
class PedigreeTier
{
    private const MAX_DEPTH = 8;

    /**
     * Every animal (herd + pedigree references) for one farm, keyed by id,
     * with sire/dam relations wired in memory — one query for any number of
     * tier lookups instead of one per ancestor.
     *
     * @return Collection<int, Animal>
     */
    public static function graph(int $userId): Collection
    {
        $all = Animal::where('user_id', $userId)->get()->keyBy('id');
        foreach ($all as $animal) {
            $animal->setRelation('sire', $animal->sire_id ? $all->get($animal->sire_id) : null);
            $animal->setRelation('dam', $animal->dam_id ? $all->get($animal->dam_id) : null);
        }

        return $all;
    }

    /** @var array<string, TierResult> per-request memo — a big herd shares most ancestors */
    private array $memo = [];

    public function resolve(Animal $animal): TierResult
    {
        return $this->memo[$animal->id.'@'.$animal->updated_at?->timestamp.'|'.$animal->tier.'|'.$animal->sire_id.'|'.$animal->dam_id] ??= $this->walk($animal, 0, []);
    }

    /** Computed from the parents, ignoring any official tier stored on the animal itself. */
    public function computed(Animal $animal): TierResult
    {
        return $this->fromParents($animal, 0, [$animal->id => true]);
    }

    private function walk(?Animal $animal, int $depth, array $seen): TierResult
    {
        if (! $animal) {
            return new TierResult(null, ['parent not recorded']);
        }
        if (isset($seen[$animal->id]) || $depth > self::MAX_DEPTH) {
            return new TierResult(null, ["{$animal->visual_id}: pedigree loop or too deep"]);
        }
        $seen[$animal->id] = true;

        if ($animal->tier) {
            return new TierResult($animal->tier, ["{$animal->visual_id} is recorded as {$animal->tier}"], true);
        }
        if ($animal->is_commercial) {
            return new TierResult('CC', ["{$animal->visual_id} is commercial/foundation stock"], true);
        }

        $fromParents = $this->fromParents($animal, $depth, $seen);

        // A registered ancestor bred elsewhere, with no recorded parents, is
        // taken to be full stud — the stud book wouldn't list it otherwise.
        if ($fromParents->tier === null && ! $animal->in_herd && $animal->registered && ! $animal->sire_id && ! $animal->dam_id) {
            return new TierResult($this->top(), ["{$animal->visual_id} is a registered stud ancestor"]);
        }

        return $fromParents;
    }

    private function fromParents(Animal $animal, int $depth, array $seen): TierResult
    {
        $sire = $this->walk($animal->sire, $depth + 1, $seen);
        $dam = $this->walk($animal->dam, $depth + 1, $seen);

        if ($sire->tier === null || $dam->tier === null) {
            $missing = $sire->tier === null ? 'sire' : 'dam';

            return new TierResult(null, ["{$animal->visual_id}: {$missing} tier unknown — record the full pedigree to compute"]);
        }

        $ladder = config('herd.tiers');
        $weakest = min(array_search($sire->tier, $ladder, true), array_search($dam->tier, $ladder, true));
        $tier = $ladder[min($weakest + 1, count($ladder) - 1)];

        $reasons = [];
        if ($weakest < count($ladder) - 1) {
            $weakSide = array_search($dam->tier, $ladder, true) <= array_search($sire->tier, $ladder, true) ? $dam : $sire;
            $reasons = $weakSide->reasons;
            $reasons[] = "{$animal->visual_id}: weaker parent is {$ladder[$weakest]} → offspring grades up to {$tier}";
        } else {
            $reasons[] = "{$animal->visual_id}: both parents are {$tier}";
        }

        return new TierResult($tier, $reasons);
    }

    private function top(): string
    {
        $ladder = config('herd.tiers');

        return end($ladder);
    }
}
