<?php

namespace App\Services\Herd;

use Illuminate\Support\Str;

/** Reads a CSV/TXT export into rows keyed by normalised header, tolerating ; or tab delimiters. */
class CsvReader
{
    /** @return array{headers: string[], rows: array<int, array<string, string>>} */
    public static function read(string $path): array
    {
        $contents = file_get_contents($path);
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', (string) $contents);
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));
        if (! $lines || $lines === ['']) {
            return ['headers' => [], 'rows' => []];
        }

        $first = $lines[0];
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($first, $d))->first();

        $rows = array_map(fn ($l) => array_map('trim', str_getcsv($l, $delimiter)), array_filter($lines, fn ($l) => trim($l) !== ''));
        $rows = array_values($rows);

        // Headerless reader dumps: first cell is a 15-digit ISO 11784 tag.
        if (preg_match('/^\d{15}$/', str_replace([' ', '.'], '', $rows[0][0] ?? ''))) {
            $headers = self::sniffHeaderless($rows[0]);
        } else {
            $headers = array_map([self::class, 'key'], array_shift($rows));
        }

        $out = [];
        foreach ($rows as $row) {
            $assoc = [];
            foreach ($headers as $i => $h) {
                $assoc[$h] = $row[$i] ?? '';
            }
            $out[] = $assoc;
        }

        return ['headers' => $headers, 'rows' => $out];
    }

    /** Guess the columns of a headerless line from what each value looks like. */
    private static function sniffHeaderless(array $first): array
    {
        $headers = [];
        foreach ($first as $i => $v) {
            $v = trim($v);
            $digits = str_replace([' ', '.'], '', $v);
            $headers[] = match (true) {
                $i === 0 => 'eid',
                ! in_array('date', $headers, true) && (ctype_digit($v) && strlen($v) >= 9 || preg_match('#\d{1,4}[-/]\d{1,2}[-/]\d{1,4}#', $v)) => 'date',
                ! in_array('weight', $headers, true) && is_numeric(str_replace(',', '.', $v)) && (float) str_replace(',', '.', $v) < 5000 => 'weight',
                ! in_array('visual_id', $headers, true) && $v !== '' && ! is_numeric($digits) => 'visual_id',
                default => 'col'.$i,
            };
        }

        return $headers;
    }

    public static function key(string $header): string
    {
        return Str::of($header)->lower()->replaceMatches('/[^a-z0-9%]+/', '_')->trim('_')->value();
    }

    /** First non-empty value among a list of alias headers. */
    public static function pick(array $row, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $v = $row[$alias] ?? null;
            if ($v !== null && trim($v) !== '') {
                return trim($v);
            }
        }

        return null;
    }
}
