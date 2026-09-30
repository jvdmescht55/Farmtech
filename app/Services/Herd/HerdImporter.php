<?php

namespace App\Services\Herd;

use App\Models\Animal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Bulk herd-register import (e.g. a Logix/stud-book export). Parents and
 * grandparents given by ID are linked, creating pedigree-only reference
 * rows for ancestors that aren't in this herd.
 */
class HerdImporter
{
    public static function templateHeaders(): array
    {
        $h = ['visual_id', 'eid', 'name', 'sex', 'birth_date', 'birth_type', 'registered', 'tier', 'gen', 'status',
            'sire', 'dam', 'sire_sire', 'sire_dam', 'dam_sire', 'dam_dam', 'sire_birth_type', 'dam_birth_type'];
        foreach (array_keys(config('herd.ebvs')) as $k) {
            $h[] = $k;
            $h[] = $k.'_acc';
        }
        foreach (array_keys(config('herd.dam_record')) as $k) {
            $h[] = 'dam_'.$k;
        }
        $h[] = 'notes';

        return $h;
    }

    /** @return array{created: int, updated: int, skipped: int} */
    public function import(User $user, array $rows): array
    {
        $created = $updated = $skipped = 0;

        DB::transaction(function () use ($user, $rows, &$created, &$updated, &$skipped) {
            foreach ($rows as $row) {
                $vid = Animal::normalizeVisualId(CsvReader::pick($row, ScanImporter::VID));
                if (! $vid) {
                    $skipped++;

                    continue;
                }

                $animal = Animal::firstOrNew(['user_id' => $user->id, 'visual_id' => $vid]);
                $animal->exists ? $updated++ : $created++;

                $attrs = ['in_herd' => true];
                if ($eid = preg_replace('/\D/', '', (string) CsvReader::pick($row, ScanImporter::EID))) {
                    $attrs['eid'] = $eid;
                }
                if ($v = CsvReader::pick($row, ['name'])) {
                    $attrs['name'] = $v;
                }
                if ($v = CsvReader::pick($row, ['sex', 'geslag'])) {
                    $attrs['sex'] = in_array(strtoupper($v[0]), ['M', 'R'], true) ? 'M' : 'F';
                }
                if ($v = CsvReader::pick($row, ['birth_date', 'dob', 'geb_datum', 'born'])) {
                    $attrs['birth_date'] = $this->date($v);
                }
                if ($v = CsvReader::pick($row, ['birth_type', 'birth_status'])) {
                    $attrs['birth_type'] = str_pad(preg_replace('/\D/', '', $v), 2, '0', STR_PAD_LEFT);
                }
                if (($v = CsvReader::pick($row, ['registered', 'reg'])) !== null) {
                    $attrs['registered'] = in_array(strtolower($v), ['1', 'y', 'yes', 'ja', 'reg', 'true'], true);
                }
                if ($v = CsvReader::pick($row, ['tier', 'status_tier', 'grade'])) {
                    $attrs['tier'] = in_array(strtoupper($v), config('herd.tiers'), true) ? strtoupper($v) : null;
                }
                if ($v = CsvReader::pick($row, ['gen', 'gen_score'])) {
                    $attrs['gen_score'] = (int) $v;
                }
                if (($v = CsvReader::pick($row, ['status'])) && array_key_exists(strtolower($v), Animal::STATUSES)) {
                    $attrs['status'] = strtolower($v);
                }
                if ($v = CsvReader::pick($row, ['notes', 'comment', 'opmerking'])) {
                    $attrs['notes'] = $v;
                }
                $attrs['breed'] = $animal->breed ?? $user->breed;

                $ebvs = $animal->ebvs ?? [];
                foreach (array_keys(config('herd.ebvs')) as $k) {
                    $v = CsvReader::pick($row, [$k]);
                    if ($v !== null && is_numeric($v)) {
                        $acc = CsvReader::pick($row, [$k.'_acc']);
                        $ebvs[$k] = ['v' => (float) $v, 'acc' => is_numeric($acc) ? (int) $acc : null];
                    }
                }
                $attrs['ebvs'] = $ebvs ?: null;

                $record = $animal->dam_record ?? [];
                foreach (array_keys(config('herd.dam_record')) as $k) {
                    $v = CsvReader::pick($row, ['dam_'.$k]);
                    if ($v !== null) {
                        $record[$k] = $v;
                    }
                }
                $attrs['dam_record'] = $record ?: null;

                $animal->fill($attrs)->save();

                $sire = $this->ancestor($user, $row, 'sire', 'sire_sire', 'sire_dam');
                $dam = $this->ancestor($user, $row, 'dam', 'dam_sire', 'dam_dam');
                $animal->update(array_filter(['sire_id' => $sire?->id, 'dam_id' => $dam?->id]));
            }
        });

        return compact('created', 'updated', 'skipped');
    }

    private function ancestor(User $user, array $row, string $key, string $sireKey, string $damKey): ?Animal
    {
        $parent = Animal::findOrReference($user->id, CsvReader::pick($row, [$key, $key.'_id', $key === 'sire' ? 'vaar' : 'moer']));
        if (! $parent) {
            return null;
        }
        $updates = [];
        if ($gs = Animal::findOrReference($user->id, CsvReader::pick($row, [$sireKey]))) {
            $updates['sire_id'] = $gs->id;
        }
        if ($gd = Animal::findOrReference($user->id, CsvReader::pick($row, [$damKey]))) {
            $updates['dam_id'] = $gd->id;
        }
        if (($bt = CsvReader::pick($row, [$key.'_birth_type'])) && ! $parent->birth_type) {
            $updates['birth_type'] = str_pad(preg_replace('/\D/', '', $bt), 2, '0', STR_PAD_LEFT);
        }
        if ($updates && ($parent->wasRecentlyCreated || ! $parent->in_herd)) {
            $parent->update($updates);
        }

        return $parent;
    }

    private function date(string $v): ?string
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y'] as $f) {
            try {
                $d = Carbon::createFromFormat('!'.$f, $v);
                if ($d && $d->format($f) === $v) {
                    return $d->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
