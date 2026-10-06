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

    /**
     * GET /api/v1/flock — what the scale needs to work offline.
     *   ?format=ids  (default) "250901,250902,2415,"  — active animal numbers, comma-separated
     *   ?format=tags "982000123456789=250901\n…"     — tag → animal number, to rebuild the tag map
     *   ?format=json both, plus the next free birthday number for this month
     */
    public function flock(Request $request)
    {
        $reader = $this->reader($request);
        if (! $reader) {
            return response('ERROR: unknown device key', 401)->header('Content-Type', 'text/plain');
        }
        $this->touch($request, $reader);

        $animals = \App\Models\Animal::where('user_id', $reader->user_id)->where('in_herd', true)->where('status', 'active')
            ->orderBy('visual_id')->get(['visual_id', 'eid']);

        if ($request->query('format') === 'info') {
            return response($this->infoLines($reader->user_id))->header('Content-Type', 'text/plain');
        }

        return match ($request->query('format', 'ids')) {
            'tags' => response($animals->whereNotNull('eid')->map(fn ($a) => $a->eid.'='.$a->visual_id)->implode("\n")."\n")->header('Content-Type', 'text/plain'),
            'json' => response()->json([
                'ids' => $animals->pluck('visual_id'),
                'tags' => $animals->whereNotNull('eid')->pluck('visual_id', 'eid'),
                'next_id' => \App\Support\BirthdayId::next($reader->user_id, $request->query('ym')),
            ]),
            default => response($animals->pluck('visual_id')->implode(',').',')->header('Content-Type', 'text/plain'),
        };
    }

    /**
     * Compact animal list the KraalTrac keeps on the device, one line each:
     *   id|tag|sex|last kg|date of that weight (dd/mm)|short warning
     * e.g. "250912|982000123456789|F|42.5|12/09|Weight loss". About 45 bytes a line.
     */
    private function infoLines(int $userId): string
    {
        $animals = \App\Models\Animal::where('user_id', $userId)->where('in_herd', true)->where('status', 'active')
            ->orderBy('visual_id')->get(['id', 'visual_id', 'eid', 'sex']);
        $latest = \App\Models\Scan::whereIn('animal_id', $animals->pluck('id'))->whereNotNull('weight_kg')
            ->whereIn('id', fn ($q) => $q->selectRaw('max(id)')->from('scans')->where('user_id', $userId)->whereNotNull('weight_kg')->groupBy('animal_id'))
            ->get(['animal_id', 'weight_kg', 'scanned_at'])->keyBy('animal_id');
        // Short screen labels, most important first (the scanner shows one).
        $short = ['withdrawal' => 'WITHDRAWAL', 'weight_drop' => 'Weight drop!', 'weight_loss' => 'Losing weight', 'missed_drink' => 'Not drinking',
            'low_birth_weight' => 'Low birth wt', 'ill_thrift' => 'Poor grower', 'overdue' => 'Overdue', 'duplicate_eid' => 'Tag on 2 IDs'];
        $order = array_flip(array_keys($short));
        $warn = app(HerdAlerts::class)->forUser($userId)->filter(fn ($a) => $a['animal'] && isset($short[$a['code']]))
            ->groupBy(fn ($a) => $a['animal']->id)
            ->map(fn ($g) => $short[$g->sortBy(fn ($a) => $order[$a['code']])->first()['code']]);
        $clean = fn ($v) => str_replace(['|', "\n", "\r"], ' ', (string) $v);

        return $animals->map(function ($a) use ($latest, $warn, $clean) {
            $w = $latest->get($a->id);

            return implode('|', [
                $clean($a->visual_id), $a->eid ?? '', $a->sex ?? '',
                $w ? rtrim(rtrim(number_format((float) $w->weight_kg, 1, '.', ''), '0'), '.') : '',
                $w?->scanned_at ? $w->scanned_at->format('d/m') : '',
                mb_substr($clean($warn->get($a->id, '')), 0, 14),
            ]);
        })->implode("\n")."\n";
    }

    /** GET /api/v1/next-id?ym=2510 → "251004" — the next free birthday number for that month (default: this month). */
    public function nextId(Request $request)
    {
        $reader = $this->reader($request);
        if (! $reader) {
            return response('ERROR: unknown device key', 401)->header('Content-Type', 'text/plain');
        }

        return response(\App\Support\BirthdayId::next($reader->user_id, $request->query('ym')))->header('Content-Type', 'text/plain');
    }

    public function scans(Request $request, ScanImporter $importer, HerdAlerts $alerts): JsonResponse
    {
        $reader = $this->reader($request);
        if (! $reader) {
            return $this->unauthorised();
        }

        $rows = $this->rows($request);
        // Batch-level defaults, e.g. {"weigh_type":"wean","scans":[…]} — applied where a scan doesn't say otherwise.
        $defaults = array_filter(['weigh_type' => $request->json('weigh_type') ?? $request->input('weigh_type')]);
        if ($defaults) {
            $rows = array_map(fn ($r) => $r + $defaults, $rows);
        }
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

        // Small devices: {"compact":true} or ?compact=1 → minimal keys, no padding.
        if ($request->boolean('compact') || $request->json('compact')) {
            return response()->json([
                'ok' => 1,
                'status' => 'SUCCESS',
                'n' => $results->where('status', 'ok')->count(),
                'r' => $results->map(fn ($r) => array_filter([
                    's' => ['ok' => 1, 'duplicate' => 2, 'skipped' => 0][$r['status']] ?? 0,
                    'a' => $r['animal'] ?? null,
                    'w' => $r['weight'] ?? null,
                    'c' => $r['change'] ?? null,
                    'g' => $r['adg'] ?? null,
                    'x' => isset($r['alerts']) ? $r['alerts']->count() : null,
                    't' => isset($r['alerts']) && $r['alerts']->isNotEmpty() ? mb_substr($r['alerts']->first()['title'], 0, 24) : null,
                ], fn ($v) => $v !== null))->values(),
            ], 201);
        }

        return response()->json([
            'ok' => true,
            'status' => 'SUCCESS',
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
        unset($data['token'], $data['key'], $data['compact'], $data['weigh_type']);

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
        $reader = Reader::findByToken($request->bearerToken() ?: $request->header('X-Device-Token') ?: $request->header('X-Api-Key') ?: $request->query('token') ?: $request->query('key'));

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
