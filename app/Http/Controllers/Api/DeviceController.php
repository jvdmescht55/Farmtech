<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use App\Models\ReaderSync;
use App\Services\Herd\CsvReader;
use App\Services\Herd\HerdAlerts;
use App\Services\Herd\ScanImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Device API v1 — for Farmtech scanners (and anything else that can make an
 * HTTP request). Deliberately forgiving about format so simple firmware can
 * post whatever is easiest:
 *
 *   Auth: "Authorization: Bearer <token>", or "X-Device-Token: <token>", or ?token=<token>
 *
 *   POST /api/v1/scans with any of:
 *     {"eid":"982000123456789","weight":42.5,"ts":1727686800}
 *     [{"eid":"…","weight":…}, …]
 *     {"device":{"battery":87,"firmware":"1.0.3"},"scans":[…]}
 *     text/csv or text/plain body:  982000123456789,42.5,2026-09-30 08:15
 *     form fields:                   eid=982000123456789&weight=42.5
 *
 *   Field names are flexible: eid|tag|rfid|uid|code, weight|kg|w, ts|timestamp|time|date,
 *   id|ref|seq (optional — makes retries safe), vid|visual_id (management tag).
 *
 *   The reply tells the device who it just read and how the weight compares
 *   to last time, plus any alerts — small screens can show that to the farmer.
 */
class DeviceController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        $reader = $this->reader($request);
        if (! $reader) {
            return $this->unauthorised();
        }
        $this->touch($request, $reader);

        return response()->json([
            'ok' => true,
            'device' => $reader->name,
            'farm' => $reader->user->farm_name ?: $reader->user->name,
            'server_time' => now()->toIso8601String(),
            'epoch' => now()->timestamp,
        ]);
    }

    public function scans(Request $request, ScanImporter $importer, HerdAlerts $alerts): JsonResponse
    {
        $reader = $this->reader($request);
        if (! $reader) {
            return $this->unauthorised();
        }

        $rows = $this->rows($request);
        if (! $rows) {
            return response()->json(['ok' => false, 'error' => 'No scans found in the request. Send JSON {"eid":…,"weight":…}, a JSON array, CSV lines, or form fields.'], 422);
        }
        if (count($rows) > 5000) {
            return response()->json(['ok' => false, 'error' => 'Send at most 5000 scans per request.'], 413);
        }

        $this->touch($request, $reader);

        // One sync record per device per working session (gap < 3 hours), so a
        // morning at the crush reads as one session no matter how many posts.
        $open = ReaderSync::where('reader_id', $reader->id)->where('source', 'api')
            ->where('updated_at', '>=', now()->subHours(3))->whereDate('created_at', today())
            ->latest()->first();

        $sync = $importer->import($reader->user, $reader, 'api', $rows, null, $open);

        $animalAlerts = collect();
        $ids = collect($importer->results)->pluck('animal_id')->filter()->unique();
        if ($ids->isNotEmpty() && $ids->count() <= 50) {
            $animalAlerts = $alerts->forUser($reader->user_id)->filter(fn ($a) => $a['animal'] && $ids->contains($a['animal']->id))
                ->groupBy(fn ($a) => $a['animal']->id);
        }

        $results = collect($importer->results)->map(function ($r) use ($animalAlerts) {
            if (isset($r['animal_id'])) {
                $r['alerts'] = $animalAlerts->get($r['animal_id'], collect())->map(fn ($a) => ['level' => $a['severity'], 'title' => $a['title']])->values();
                unset($r['animal_id']);
            }

            return $r;
        });

        return response()->json([
            'ok' => true,
            'session' => $sync->id,
            'stored' => $results->where('status', 'ok')->count(),
            'duplicates' => $results->where('status', 'duplicate')->count(),
            'skipped' => $results->where('status', 'skipped')->count(),
            'results' => $results->values(),
        ], 201);
    }

    private function rows(Request $request): array
    {
        $type = strtolower((string) $request->header('Content-Type'));

        if (str_contains($type, 'text/') || str_contains($type, 'csv')) {
            $tmp = tempnam(sys_get_temp_dir(), 'scan');
            file_put_contents($tmp, $request->getContent());
            $rows = CsvReader::read($tmp)['rows'];
            @unlink($tmp);

            return $rows;
        }

        $data = $request->json()->all() ?: $request->all();
        unset($data['token']);

        if (isset($data['device']) && is_array($data['device'])) {
            unset($data['device']);
        }
        if (isset($data['scans']) && is_array($data['scans'])) {
            return array_values(array_filter($data['scans'], 'is_array'));
        }
        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        return $data ? [$data] : [];
    }

    private function reader(Request $request): ?Reader
    {
        $token = $request->bearerToken() ?: $request->header('X-Device-Token') ?: $request->query('token');
        if (! $token || strlen($token) < 20) {
            return null;
        }
        $reader = Reader::with('user')->where('api_token', $token)->first();

        return $reader && $reader->user->hasModule('rfid') ? $reader : null;
    }

    private function touch(Request $request, Reader $reader): void
    {
        $device = $request->json('device') ?? [];
        $reader->forceFill(array_filter([
            'last_synced_at' => now(),
            'last_ip' => $request->ip(),
            'battery_pct' => isset($device['battery']) && is_numeric($device['battery']) ? max(0, min(100, (int) $device['battery'])) : null,
            'firmware' => isset($device['firmware']) ? mb_substr((string) $device['firmware'], 0, 32) : null,
        ], fn ($v) => $v !== null))->save();
    }

    private function unauthorised(): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => 'Unknown or unlicensed device token.'], 401);
    }
}
