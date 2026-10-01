<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceReading;
use App\Models\Reader;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * POST /api/v1/readings — custom devices.
 *   {"tank_level": 72.5, "temp": 21.4}                        (now)
 *   {"ts": 1790798104, "readings": {"tank_level": 72.5}}
 *   {"readings": [{"metric":"tank_level","value":72.5,"ts":…}, …]}
 * Unknown metric names are added to the device automatically so nothing is lost.
 */
class ReadingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $reader = Reader::findByToken($request->bearerToken() ?: $request->header('X-Device-Token') ?: $request->query('token') ?: $request->query('key'));
        if (! $reader) {
            return response()->json(['ok' => false, 'error' => 'Unknown device key.'], 401);
        }

        $data = $request->json()->all() ?: $request->except(['token', 'key']);
        $defaultTs = $this->time($data['ts'] ?? null);
        $rows = [];
        $readings = $data['readings'] ?? collect($data)->except(['ts', 'device', 'compact'])->all();

        foreach ($readings as $k => $v) {
            if (is_array($v)) {
                if (isset($v['metric'], $v['value']) && is_numeric($v['value'])) {
                    $rows[] = [Str::slug($v['metric'], '_'), (float) $v['value'], $this->time($v['ts'] ?? null) ?? $defaultTs];
                }
            } elseif (is_numeric($v) && is_string($k)) {
                $rows[] = [Str::slug($k, '_'), (float) $v, $defaultTs];
            }
        }
        if (! $rows) {
            return response()->json(['ok' => false, 'error' => 'No numeric readings found.'], 422);
        }

        $metrics = collect($reader->metrics ?? []);
        foreach (collect($rows)->pluck(0)->unique() as $key) {
            if (! $metrics->contains('key', $key)) {
                $metrics->push(['key' => $key, 'label' => Str::headline($key), 'unit' => null, 'min' => null, 'max' => null]);
            }
        }
        $reader->forceFill(['metrics' => $metrics->values()->all(), 'last_synced_at' => now(), 'last_ip' => $request->ip()])->save();

        foreach ($rows as [$metric, $value, $ts]) {
            DeviceReading::create(['reader_id' => $reader->id, 'metric' => $metric, 'value' => $value, 'recorded_at' => $ts ?? now()]);
        }

        $out = $metrics->filter(fn ($m) => collect($rows)->contains(0, $m['key']))->map(function ($m) use ($rows) {
            $v = collect($rows)->where(0, $m['key'])->last()[1];

            return ['metric' => $m['key'], 'value' => $v, 'alert' => ($m['min'] !== null && $v < $m['min']) || ($m['max'] !== null && $v > $m['max'])];
        })->values();

        return response()->json(['ok' => true, 'stored' => count($rows), 'readings' => $out], 201);
    }

    private function time($v): ?Carbon
    {
        if ($v === null || $v === '') {
            return null;
        }
        try {
            $t = is_numeric($v) ? Carbon::createFromTimestamp(strlen((string) (int) $v) >= 12 ? intdiv((int) $v, 1000) : (int) $v) : Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }

        return $t->year < 2015 || $t->gt(now()->addHours(2)) ? null : $t;
    }
}
