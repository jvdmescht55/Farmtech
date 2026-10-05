<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Weekly South African red meat prices from the Red Meat Producers Organisation
 * (RPO), https://rpo.co.za/carcass-prices/ — national averages, published weekly.
 * Fetched by `php artisan market:prices` (scheduled) and kept as a small JSON file,
 * so pages never wait on RPO and still work if their site is down.
 */
class MarketPrices
{
    public const SOURCE_URL = 'https://rpo.co.za/carcass-prices/';

    private const FILE = 'market-prices.json';

    /** Column order in RPO's table. */
    public const SERIES = [
        'beef_a' => ['Beef carcass A2/3', 'beef'],
        'beef_b' => ['Beef carcass B2/3', 'beef'],
        'beef_c' => ['Beef carcass C2/3', 'beef'],
        'weaner' => ['Weaner calves (live)', 'beef'],
        'lamb_a' => ['Lamb carcass A2/3', 'mutton'],
        'mutton_b' => ['Mutton carcass B2/3', 'mutton'],
        'mutton_c' => ['Mutton carcass C2/3', 'mutton'],
        'feeder_lamb' => ['Feeder lambs (live)', 'mutton'],
    ];

    public function refresh(): int
    {
        $html = Http::timeout(20)->withHeaders(['User-Agent' => 'FarmtechMarketPrices/1.0 (+https://farmtech.site)'])
            ->get(self::SOURCE_URL)->throw()->body();
        $rows = $this->parse($html);
        if (count($rows) < 4) {
            throw new \RuntimeException('RPO price table not found or changed layout.');
        }
        Storage::put(self::FILE, json_encode(['fetched_at' => now()->toIso8601String(), 'rows' => $rows]));

        return count($rows);
    }

    /** @return array<int, array{week: string, beef_a: float|null, ...}> newest first */
    public function parse(string $html): array
    {
        $rows = [];
        preg_match_all('/<table.*?<\/table>/s', $html, $tables);
        foreach ($tables[0] as $table) {
            preg_match_all('/<tr.*?<\/tr>/s', $table, $trs);
            foreach ($trs[0] as $tr) {
                preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/s', $tr, $m);
                $cells = array_map(fn ($c) => trim(html_entity_decode(strip_tags($c))), $m[1]);
                if (count($cells) < 9 || ! preg_match_all('#(\d{1,2})/(\d{1,2})/(\d{4})#', $cells[0], $dates, PREG_SET_ORDER)) {
                    continue;
                }
                $d = end($dates); // "02/10/2026 week ended 25/09/2026" → the week it covers
                $row = ['week' => sprintf('%04d-%02d-%02d', $d[3], $d[2], $d[1])];
                foreach (array_keys(self::SERIES) as $i => $key) {
                    $row[$key] = preg_match('/\d+(?:[.,]\d+)?/', $cells[$i + 1], $v) ? (float) str_replace(',', '.', $v[0]) : null;
                }
                $rows[$row['week']] = $row;
            }
        }
        krsort($rows);

        return array_values($rows);
    }

    /** @return array{fetched_at: ?string, rows: array} */
    public function all(): array
    {
        $data = Storage::exists(self::FILE) ? json_decode(Storage::get(self::FILE), true) : null;

        return $data ?: ['fetched_at' => null, 'rows' => []];
    }

    /** Latest week, previous week, and a year of history per series. */
    public function summary(): ?array
    {
        $rows = $this->all()['rows'];
        if (! $rows) {
            return null;
        }
        [$now, $prev] = [$rows[0], $rows[1] ?? null];
        $yearAgo = collect($rows)->first(fn ($r) => $r['week'] <= Carbon::parse($now['week'])->subYear()->toDateString());
        $series = [];
        foreach (self::SERIES as $key => [$label, $group]) {
            $series[$key] = [
                'label' => $label, 'group' => $group, 'value' => $now[$key],
                'week_change' => $prev && $prev[$key] && $now[$key] ? round(($now[$key] - $prev[$key]) / $prev[$key] * 100, 1) : null,
                'year_change' => $yearAgo && $yearAgo[$key] && $now[$key] ? round(($now[$key] - $yearAgo[$key]) / $yearAgo[$key] * 100, 1) : null,
                'history' => collect($rows)->take(52)->reverse()->pluck($key)->filter()->values()->all(),
            ];
        }

        return ['week' => $now['week'], 'fetched_at' => $this->all()['fetched_at'], 'series' => $series, 'rows' => array_slice($rows, 0, 260)];
    }
}
