<?php

namespace App\Services\Herd;

use Illuminate\Support\Str;

/** Reads a CSV/TXT export into rows keyed by normalised header, tolerating ; or tab delimiters. */
class CsvReader
{
    /** @return array{headers: string[], rows: array<int, array<string, string>>} */
    public static function read(string $path): array
    {
        // Excel workbook (zip) — read it directly.
        $head = (string) @file_get_contents($path, false, null, 0, 4);
        if (str_starts_with($head, "PK\x03\x04")) {
            $rows = XlsxReader::rows($path);
            $rows = array_values(array_filter($rows, fn ($r) => implode('', $r) !== ''));

            return self::fromRows($rows);
        }

        $contents = file_get_contents($path);
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', (string) $contents);
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));
        if (! $lines || $lines === ['']) {
            return ['headers' => [], 'rows' => []];
        }

        $first = $lines[0];
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($first, $d))->first();

        $rows = array_map(fn ($l) => array_map('trim', str_getcsv($l, $delimiter)), array_filter($lines, fn ($l) => trim($l) !== ''));

        return self::fromRows(array_values($rows));
    }

    /**
     * The raw grid of every sheet: [sheet name => rows of cells]. A CSV is one
     * sheet; an Excel workbook gives every visible worksheet.
     *
     * @return array<string, array<int, array<int, string>>>
     */
    public static function sheets(string $path, string $csvName = 'Sheet1'): array
    {
        $head = (string) @file_get_contents($path, false, null, 0, 4);
        if (str_starts_with($head, "PK\x03\x04")) {
            return array_map(fn ($rows) => array_values(array_filter($rows, fn ($r) => implode('', $r) !== '')), XlsxReader::sheets($path));
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($path));
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($contents)), fn ($l) => trim($l, " \t,;") !== ''));
        if (! $lines) {
            return [$csvName => []];
        }
        $sample = implode("\n", array_slice($lines, 0, 20));
        $delimiter = collect([',', ';', "\t", '|'])->sortByDesc(fn ($d) => substr_count($sample, $d))->first();

        // fgetcsv keeps quoted cells that run over several lines together.
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, implode("\n", $lines));
        rewind($stream);
        $rows = [];
        while (($r = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            if (implode('', $r) !== '') {
                $rows[] = array_map(fn ($c) => trim((string) $c), $r);
            }
        }
        fclose($stream);

        return [$csvName => $rows];
    }

    /** Shared by CSV and Excel: header detection and keyed rows. */
    private static function fromRows(array $rows): array
    {
        if (! $rows) {
            return ['headers' => [], 'rows' => []];
        }

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
