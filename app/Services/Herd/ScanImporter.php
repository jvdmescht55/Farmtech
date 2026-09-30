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
    public const EID = ['eid', 'tag', 'rfid', 'electronic_id', 'eid_tag', 'tag_number', 'eid_number', 'electronic_tag'];

    public const VID = ['visual_id', 'vid', 'animal_id', 'dier_id', 'id', 'visual_tag', 'management_tag'];

    public const WEIGHT = ['weight', 'weight_kg', 'kg', 'mass', 'massa', 'gewig'];

    public const DATE = ['scanned_at', 'date', 'datetime', 'date_time', 'time', 'datum'];

    public const TYPE = ['weigh_type', 'type', 'event'];

    /** @param array<int, array<string, mixed>> $rows */
    public function import(User $user, ?Reader $reader, string $source, array $rows, ?string $filename = null): ReaderSync
    {
        return DB::transaction(function () use ($user, $reader, $source, $rows, $filename) {
            $sync = ReaderSync::create([
                'user_id' => $user->id,
                'reader_id' => $reader?->id,
                'source' => $source,
                'filename' => $filename,
            ]);

            $matched = $new = $count = 0;

            foreach ($rows as $row) {
                $row = array_change_key_case(array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $row));
                $eid = $this->normalizeEid(CsvReader::pick($row, self::EID));
                $vid = Animal::normalizeVisualId(CsvReader::pick($row, self::VID));
                if (! $eid && ! $vid) {
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
                        'eid' => $eid,
                        'visual_id' => $vid ?? $eid,
                        'breed' => $user->breed,
                        'last_seen_at' => $scannedAt,
                    ]);
                }

                $weight = CsvReader::pick($row, self::WEIGHT);
                $weight = $weight !== null && is_numeric(str_replace(',', '.', $weight)) ? (float) str_replace(',', '.', $weight) : null;
                $type = CsvReader::pick($row, self::TYPE);

                Scan::create([
                    'user_id' => $user->id,
                    'reader_sync_id' => $sync->id,
                    'animal_id' => $animal->id,
                    'eid' => $eid,
                    'visual_id' => $vid,
                    'weight_kg' => $weight && $weight > 0 && $weight < 5000 ? $weight : null,
                    'weigh_type' => array_key_exists($type ?? '', Scan::WEIGH_TYPES) ? $type : ($weight ? 'routine' : null),
                    'scanned_at' => $scannedAt,
                ]);
                $count++;
            }

            $sync->update(['scan_count' => $count, 'matched_count' => $matched, 'new_count' => $new]);
            $reader?->update(['last_synced_at' => now()]);

            return $sync;
        });
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
