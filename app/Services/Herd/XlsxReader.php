<?php

namespace App\Services\Herd;

/**
 * Minimal .xlsx reader (first worksheet only) — enough for farmers to upload
 * an Excel file straight away instead of "Save as CSV". Returns rows of
 * strings; Excel date serials in columns whose header mentions "date" are
 * turned into dd/mm/yyyy.
 */
class XlsxReader
{
    /** @return array<int, array<int, string>> First worksheet only. */
    public static function rows(string $path): array
    {
        return array_values(self::sheets($path))[0] ?? [];
    }

    /**
     * Every worksheet, in workbook order: [sheet name => rows].
     *
     * @return array<string, array<int, array<int, string>>>
     */
    public static function sheets(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return [];
        }

        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sst = simplexml_load_string($xml);
            foreach ($sst->si as $si) {
                $text = isset($si->t) ? (string) $si->t : '';
                foreach ($si->r ?? [] as $run) {
                    $text .= (string) $run->t;
                }
                $shared[] = $text;
            }
        }

        $paths = [];
        if (($wb = $zip->getFromName('xl/workbook.xml')) !== false && ($rels = $zip->getFromName('xl/_rels/workbook.xml.rels')) !== false) {
            $wbx = simplexml_load_string($wb);
            $relx = simplexml_load_string($rels);
            $targets = [];
            foreach ($relx->Relationship as $r) {
                $targets[(string) $r['Id']] = 'xl/'.ltrim(str_replace('/xl/', '', (string) $r['Target']), '/');
            }
            foreach ($wbx->sheets->sheet as $sheet) {
                $rid = (string) $sheet->attributes('r', true)->id;
                if (isset($targets[$rid]) && (string) $sheet['state'] !== 'hidden') {
                    $paths[(string) $sheet['name'] ?: 'Sheet'.(count($paths) + 1)] = $targets[$rid];
                }
            }
        }
        $paths = $paths ?: ['Sheet1' => 'xl/worksheets/sheet1.xml'];

        $out = [];
        foreach (array_slice($paths, 0, 20, true) as $name => $sheetPath) {
            $sheet = $zip->getFromName($sheetPath);
            if ($sheet !== false) {
                $out[$name] = self::parseSheet($sheet, $shared);
            }
        }
        $zip->close();

        return $out;
    }

    private static function parseSheet(string $sheet, array $shared): array
    {
        $rows = [];
        $x = simplexml_load_string($sheet);
        foreach ($x->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = self::colIndex(preg_replace('/\d+/', '', (string) $c['r']));
                $type = (string) $c['t'];
                $v = (string) ($c->v ?? '');
                $cells[$col] = match ($type) {
                    's' => $shared[(int) $v] ?? '',
                    'inlineStr' => (string) ($c->is->t ?? ''),
                    'b' => $v === '1' ? 'yes' : 'no',
                    default => $v,
                };
            }
            if ($cells) {
                $line = [];
                for ($i = 0; $i <= max(array_keys($cells)); $i++) {
                    $line[] = trim((string) ($cells[$i] ?? ''));
                }
                $rows[] = $line;
            }
        }

        // Excel stores dates as day numbers: convert in "date" columns.
        if ($rows) {
            foreach ($rows[0] as $i => $h) {
                if (str_contains(strtolower($h), 'date') || str_contains(strtolower($h), 'datum') || strtolower($h) === 'born') {
                    foreach (array_keys($rows) as $r) {
                        if ($r > 0 && is_numeric($rows[$r][$i] ?? null) && $rows[$r][$i] > 20000 && $rows[$r][$i] < 80000) {
                            $rows[$r][$i] = date('d/m/Y', (int) round(((float) $rows[$r][$i] - 25569) * 86400));
                        }
                    }
                }
            }
        }

        return $rows;
    }

    private static function colIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(0, $n - 1);
    }
}
