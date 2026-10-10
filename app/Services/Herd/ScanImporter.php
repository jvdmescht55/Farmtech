<?php

namespace App\Services\Herd;

use App\Models\Animal;
use App\Models\Reader;
use App\Models\ReaderSync;
use App\Models\Scan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turns reader output (API push or an exported session file) into scans,
 * matching each tag to an animal by EID first, then visual ID. Unknown tags
 * become new herd animals so nothing a reader saw is silently dropped.
 */
class ScanImporter
{
    public const EID = ['eid', 'tag', 'rfid', 'electronic_id', 'eid_tag', 'tag_number', 'eid_number', 'electronic_tag', 'uid', 'code', 'tag_id', 'number'];

    public const VID = ['visual_id', 'vid', 'animal_id', 'dier_id', 'id', 'visual_tag', 'management_tag'];

    public const WEIGHT = ['weight', 'weight_kg', 'kg', 'mass', 'massa', 'gewig', 'w'];

    public const DATE = ['scanned_at', 'date', 'datetime', 'date_time', 'time', 'datum', 'ts', 'timestamp'];

    // NOT 'id' — scale firmware sends the sheep number as "id" (see VID).
    public const REF = ['ref', 'client_ref', 'scan_id', 'uuid', 'seq'];

    /** @var array<int, array> per-scan outcome of the last import() call (API replies use this). */
    public array $results = [];

    public const TYPE = ['weigh_type', 'weight_type', 'wtype', 'type', 'event'];

    public const SEX = ['sex', 'gender', 'geslag'];

    public const SIRE = ['sire', 'sire_id', 'vaar', 'father', 'ram'];

    public const DAM = ['dam', 'dam_id', 'moer', 'mother', 'ewe'];

    /** @param array<int, array<string, mixed>> $rows */
    public function import(User $user, ?Reader $reader, string $source, array $rows, ?string $filename = null, ?ReaderSync $into = null): ReaderSync
    {
        return DB::transaction(function () use ($user, $reader, $source, $rows, $filename, $into) {
            $sync = $into ?? ReaderSync::create([
                'user_id' => $user->id,
                'reader_id' => $reader?->id,
                'source' => $source,
                'filename' => $filename,
            ]);

            $matched = $new = $count = 0;
            $this->results = [];

            foreach ($rows as $row) {
                $row = array_change_key_case(array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $row));
                $eid = $this->normalizeEid(CsvReader::pick($row, self::EID));
                $vid = Animal::normalizeVisualId(CsvReader::pick($row, self::VID));
                if (! $eid && ! $vid) {
                    $this->results[] = ['status' => 'skipped', 'reason' => 'no tag or animal id'];

                    continue;
                }
                // Devices retry when the network drops — the same ref is only stored once.
                $ref = in_array($source, ['api', 'paste'], true) ? CsvReader::pick($row, self::REF) : null;
                if ($ref && Scan::where('user_id', $user->id)->where('client_ref', $ref)->exists()) {
                    $this->results[] = ['status' => 'duplicate', 'ref' => $ref, 'eid' => $eid];

                    continue;
                }

                $animal = null;
                if ($eid) {
                    $animal = Animal::where('user_id', $user->id)->where('eid', $eid)->first();
                }
                if (! $animal && $vid) {
                    $animal = Animal::where('user_id', $user->id)->where('visual_id', $vid)->first();
                }

                $scannedAt = $this->parseDate(CsvReader::pick($row, self::DATE));
                // A device whose clock was never set reports 1970/2000 or the far future.
                // Old weights from a spreadsheet are real history, so only devices get the 2015 floor.
                if ($scannedAt->year < ($source === 'csv' ? 1950 : 2015) || $scannedAt->gt(now()->addHours(2))) {
                    $scannedAt = now();
                }
                $isNew = ! $animal;

                if ($animal) {
                    $matched++;
                    $updates = ['last_seen_at' => max($animal->last_seen_at ?? $scannedAt, $scannedAt), 'in_herd' => true];
                    if ($eid && ! $animal->eid) {
                        $updates['eid'] = $eid;
                    }
                    $animal->update($updates);
                } else {
                    $new++;
                    $animal = Animal::create([
                        'user_id' => $user->id,
                        'birth_date' => \App\Support\BirthdayId::birthDate($vid),
                        'eid' => $eid,
                        'visual_id' => $vid ?? $eid,
                        'breed' => $user->breed,
                        'species' => $user->species ?: 'sheep',
                        'last_seen_at' => $scannedAt,
                    ]);
                }

                // Extra details a terminal like the KraalTrac Pro can capture on its keypad.
                $this->fillDetails($user, $animal, $row);

                $weight = CsvReader::pick($row, self::WEIGHT);
                $weight = $weight !== null && is_numeric(str_replace(',', '.', $weight)) ? (float) str_replace(',', '.', $weight) : null;
                $type = $this->weighType(CsvReader::pick($row, self::TYPE));

                $weightKg = $weight && $weight > 0 && $weight < 5000 ? round($weight, 1) : null;

                // Same animal, same weight, within a few seconds = a double read, not a new weighing.
                $double = Scan::where('animal_id', $animal->id)
                    ->whereBetween('scanned_at', [$scannedAt->copy()->subSeconds(20), $scannedAt->copy()->addSeconds(20)])
                    ->where(fn ($q) => $weightKg === null ? $q->whereNull('weight_kg') : $q->where('weight_kg', $weightKg))
                    ->exists();
                if ($double) {
                    $this->results[] = ['status' => 'duplicate', 'eid' => $eid, 'animal' => $animal->visual_id];

                    continue;
                }

                $previous = $weightKg !== null
                    ? Scan::where('animal_id', $animal->id)->whereNotNull('weight_kg')->where('scanned_at', '<', $scannedAt->copy()->startOfDay())->orderByDesc('scanned_at')->first()
                    : null;

                Scan::create([
                    'user_id' => $user->id,
                    'reader_sync_id' => $sync->id,
                    'client_ref' => $ref,
                    'animal_id' => $animal->id,
                    'eid' => $eid,
                    'visual_id' => $vid,
                    'weight_kg' => $weightKg,
                    'weigh_type' => array_key_exists($type ?? '', Scan::WEIGH_TYPES) ? $type : ($weight ? 'routine' : null),
                    'scanned_at' => $scannedAt,
                ]);
                $count++;

                $days = $previous ? max(1, (int) $previous->scanned_at->startOfDay()->diffInDays($scannedAt->copy()->startOfDay())) : null;
                $this->results[] = array_filter([
                    'status' => 'ok',
                    'ref' => $ref,
                    'eid' => $eid,
                    'animal_id' => $animal->id,
                    'animal' => $animal->visual_id,
                    'new_animal' => $isNew,
                    'weight' => $weightKg,
                    'previous_weight' => $previous ? (float) $previous->weight_kg : null,
                    'change' => $previous ? round($weightKg - $previous->weight_kg, 1) : null,
                    'adg' => $previous ? (int) round(($weightKg - $previous->weight_kg) * 1000 / $days) : null,
                ], fn ($v) => $v !== null);
            }

            $sync->update(['scan_count' => $sync->scan_count * (int) (bool) $into + $count, 'matched_count' => $sync->matched_count * (int) (bool) $into + $matched, 'new_count' => $sync->new_count * (int) (bool) $into + $new]);
            $reader?->update(['last_synced_at' => now()]);

            return $sync;
        });
    }

    private function weighType(?string $raw): ?string
    {
        $t = strtolower(trim((string) $raw));

        // Accepts short codes and the long labels scale firmware uses ("Birth Weight", "Post Wean Wt").
        return match (true) {
            $t === '' => null,
            in_array($t, ['b', '1'], true) || str_contains($t, 'birth') || str_contains($t, 'geboorte') => 'birth',
            in_array($t, ['p', '3'], true) || str_contains($t, 'post') || str_contains($t, 'naspeen') => 'post_wean',
            in_array($t, ['w', '2'], true) || str_contains($t, 'wean') || str_contains($t, 'speen') => 'wean',
            in_array($t, ['m', '4'], true) || str_contains($t, 'mature') || str_contains($t, 'adult') || str_contains($t, 'volwasse') => 'mature',
            default => 'routine',
        };
    }

    /** Sex, sire and dam from the device — only fills what's still blank, never overwrites the herd book. */
    private function fillDetails(User $user, Animal $animal, array $row): void
    {
        $updates = [];
        $sex = strtolower((string) CsvReader::pick($row, self::SEX));
        if ($sex !== '' && ! $animal->sex) {
            $updates['sex'] = in_array($sex, ['m', 'male', 'ram', 'bull', 'buck', 'r', 'manlik', '1'], true) ? 'M' : 'F';
        }
        foreach (['sire_id' => self::SIRE, 'dam_id' => self::DAM] as $col => $aliases) {
            if (! $animal->{$col} && ($id = CsvReader::pick($row, $aliases))) {
                $parent = ctype_digit(preg_replace('/\s/', '', $id)) && strlen(preg_replace('/\D/', '', $id)) === 15
                    ? Animal::where('user_id', $user->id)->where('eid', preg_replace('/\D/', '', $id))->first()
                    : Animal::findOrReference($user->id, $id);
                if ($parent && $parent->id !== $animal->id) {
                    $updates[$col] = $parent->id;
                }
            }
        }
        if ($updates) {
            $animal->update($updates);
        }
    }

    /** ISO 11784 tags come as "982 000123456789", "982000123456789" or "982.000123456789". */
    public function normalizeEid(?string $eid): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $eid);

        return $digits === '' ? null : $digits;
    }

    private function parseDate(?string $value): Carbon
    {
        if (! $value) {
            return now();
        }
        // Unix epoch from a microcontroller clock (seconds or milliseconds).
        if (ctype_digit($value) && strlen($value) >= 9) {
            $ts = (int) $value;

            return Carbon::createFromTimestamp(strlen($value) >= 12 ? intdiv($ts, 1000) : $ts, config('app.timezone'));
        }
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
            try {
                $d = Carbon::createFromFormat($format, $value);
                if ($d && $d->format($format) === $value) {
                    return str_contains($format, 'H') ? $d : $d->startOfDay()->setTimeFrom(now());
                }
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }
    }
}
