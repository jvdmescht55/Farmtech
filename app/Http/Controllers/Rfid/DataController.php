<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Services\Herd\DataExporter;
use App\Services\Herd\ScanImporter;
use App\Services\Herd\SmartImport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * One place to get data in and out. Any spreadsheet is read by SmartImport,
 * shown as a plain-words preview, then split into the herd book, weighings
 * and records in one go. Every export is a CSV; "backup" zips them all.
 */
class DataController extends Controller
{
    public function index(Request $request, DataExporter $exporter)
    {
        if ($type = $request->query('export')) {
            abort_unless(array_key_exists($type, DataExporter::TYPES), 404);

            return $exporter->stream($request->user(), $type);
        }

        $preview = session('preview');
        if ($preview && ! is_file($this->path($preview['token']))) {
            $preview = null;
        }

        return view('rfid.data', ['preview' => $preview, 'exports' => DataExporter::TYPES, 'fields' => SmartImport::FIELDS]);
    }

    public function preview(Request $request, SmartImport $smart)
    {
        $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:csv,txt,tsv,xlsx,xlsm']]);

        $token = Str::random(32);
        $dir = dirname($this->path($token));
        is_dir($dir) || mkdir($dir, 0775, true);
        $request->file('file')->move($dir, basename($this->path($token)));

        return $this->showPreview($smart, $token, $request->file('file')->getClientOriginalName());
    }

    private function showPreview(SmartImport $smart, string $token, string $name)
    {
        $sheets = $smart->analyse($this->path($token), $name);
        if (! $sheets) {
            @unlink($this->path($token));

            return redirect()->route('rfid.data')->withErrors(['file' => 'We couldn\'t find any rows in that file. Is it the right one?']);
        }

        return redirect()->route('rfid.data')->with('preview', ['token' => $token, 'name' => $name, 'sheets' => $sheets]);
    }

    public function import(Request $request, SmartImport $smart)
    {
        $data = $request->validate([
            'token' => ['required', 'alpha_num', 'size:32'],
            'name' => ['nullable', 'string', 'max:200'],
            'map' => ['array'], 'map.*' => ['array'], 'map.*.*' => ['string', 'max:40'],
            'include' => ['array'], 'include.*' => ['boolean'],
        ]);
        $path = $this->path($data['token']);
        abort_unless(is_file($path), 410, 'That upload expired. Please upload it again.');

        $include = array_map('boolval', $data['include'] ?? []);
        $r = $smart->run($request->user(), $path, $data['name'] ?: 'Upload', $data['map'] ?? [], $include);
        @unlink($path);

        $parts = array_filter([
            $r['created'] ? number_format($r['created']).' new animal'.($r['created'] === 1 ? '' : 's') : null,
            $r['updated'] ? number_format($r['updated']).' updated' : null,
            $r['weights'] ? number_format($r['weights']).' weighing'.($r['weights'] === 1 ? '' : 's') : null,
            $r['records'] ? number_format($r['records']).' record'.($r['records'] === 1 ? '' : 's') : null,
            $r['kept'] ? number_format($r['kept']).' extra detail'.($r['kept'] === 1 ? '' : 's').' kept in notes' : null,
        ]);
        $message = $parts ? 'Done: '.implode(', ', $parts).'.' : 'Nothing new in that file. It was all here already.';
        if ($r['skipped']) {
            $message .= ' '.$r['skipped'].' row'.($r['skipped'] === 1 ? ' was' : 's were').' left out (no animal ID or no date).';
        }

        return redirect()->route('rfid.data')->with('status', $message)->with('confetti', (bool) $parts);
    }

    /**
     * "Paste from the scale": the lines a KraalTrac prints over USB when you
     * press B (ref|id|tag|type|gender|sire|dam|weight|ts, or the older
     * id|type|gender|sire|dam|weight). The BEGIN/END lines can be included.
     */
    public function paste(Request $request, ScanImporter $scans, SmartImport $smart)
    {
        $text = (string) $request->validate(['lines' => ['required', 'string', 'max:2000000']])['lines'];
        $rows = $this->scaleRows(preg_split('/\R/', $text));
        if (! $rows) {
            // Not scale lines: rows copied straight out of Excel. Treat it like an uploaded sheet.
            if (count(preg_split('/\R/', trim($text))) >= 1 && preg_match('/[\t,;]/', $text)) {
                $token = Str::random(32);
                $dir = dirname($this->path($token));
                is_dir($dir) || mkdir($dir, 0775, true);
                file_put_contents($this->path($token), $text);

                return $this->showPreview($smart, $token, 'Pasted rows');
            }

            return back()->withErrors(['lines' => 'We couldn\'t make sense of that. Paste rows copied from Excel (with the heading row), or the lines the scale prints.'])->withInput();
        }

        $sync = $scans->import($request->user(), null, 'paste', $rows, 'Pasted from the scale');
        $dupes = collect($scans->results)->where('status', 'duplicate')->count();

        return redirect()->route('rfid.data')->with('status', "From the scale: {$sync->scan_count} saved".($dupes ? ", {$dupes} were already in (skipped)" : '').". It's now safe to clear the scale's queue (A + PIN).")->with('confetti', true);
    }

    /** "Get data off the scale" — Wi-Fi status, USB sync in the browser, paste. */
    public function scale(Request $request)
    {
        $u = $request->user();

        return view('rfid.scale', [
            'scales' => \App\Models\Reader::where('user_id', $u->id)->where(fn ($q) => $q->whereNull('kind')->orWhere('kind', 'handheld'))->orderByDesc('last_synced_at')->get(),
            'recent' => \App\Models\ReaderSync::where('user_id', $u->id)->latest()->limit(5)->get(),
        ]);
    }

    /**
     * "Plug in the scale": the browser reads the queue over USB (Web Serial),
     * posts it here with the farmer's own login, and only tells the scale to
     * clear once we answer ok=true (every line saved or already in).
     */
    public function usb(Request $request, ScanImporter $scans)
    {
        $lines = $request->validate(['lines' => ['required', 'array', 'max:2000'], 'lines.*' => ['string', 'max:500']])['lines'];
        $rows = $this->scaleRows($lines);
        if (! $rows) {
            return response()->json(['ok' => false, 'message' => 'Those lines don\'t look like KraalTrac records.'], 422);
        }

        $sync = $scans->import($request->user(), null, 'paste', $rows, 'USB sync from the scale');
        $by = collect($scans->results)->countBy('status');

        return response()->json([
            'ok' => count($rows) === count($lines),
            'saved' => (int) $sync->scan_count,
            'duplicates' => (int) ($by['duplicate'] ?? 0),
            'skipped' => (int) ($by['skipped'] ?? 0) + count($lines) - count($rows),
        ]);
    }

    /** KraalTrac queue lines → importer rows (9-field current, 6-field legacy). */
    private function scaleRows(array $lines): array
    {
        $rows = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            $f = explode('|', $line);
            if (count($f) === 9) {
                $rows[] = array_combine(['ref', 'id', 'tag', 'type', 'gender', 'sire', 'dam', 'weight', 'ts'], $f);
            } elseif (count($f) === 6) {
                $rows[] = array_combine(['id', 'type', 'gender', 'sire', 'dam', 'weight'], $f) + ['ref' => 'paste-'.md5($line)];
            }
        }

        return $rows;
    }

    public function backup(Request $request, DataExporter $exporter)
    {
        $path = $exporter->backup($request->user());

        return response()->download($path, 'farmtech-backup-'.now()->format('Y-m-d').'.zip')->deleteFileAfterSend();
    }

    public function template(string $kind)
    {
        $templates = [
            'herd' => [\App\Services\Herd\HerdImporter::templateHeaders(), []],
            'scans' => [['eid', 'visual_id', 'weight', 'date', 'weigh_type'], [['982000123456789', 'DVS 25 5082', '42.5', '30/09/2026 08:15', 'routine']]],
            'events' => [['visual_id', 'type', 'date', 'product', 'dose', 'withdrawal_days', 'mate', 'count', 'result', 'notes'], [
                ['DVS 21 3100', 'dosing', '30/09/2026', 'Closantel', '5 ml', '28', '', '', '', ''],
                ['DVS 21 3107', 'mating', '01/05/2026', '', '', '', 'DVS 222435', '', '', ''],
                ['DVS 21 3114', 'pregnancy_scan', '20/07/2026', '', '', '', '', '', 'twins', ''],
            ]],
        ];
        abort_unless(isset($templates[$kind]), 404);
        [$headers, $rows] = $templates[$kind];

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, "farmtech-template-{$kind}.csv", ['Content-Type' => 'text/csv']);
    }

    private function path(string $token): string
    {
        return storage_path('app/private/imports/'.auth()->id().'-'.$token.'.csv');
    }
}
