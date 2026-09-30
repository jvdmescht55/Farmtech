<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Services\Herd\CsvReader;
use App\Services\Herd\DataExporter;
use App\Services\Herd\EventImporter;
use App\Services\Herd\HerdImporter;
use App\Services\Herd\ScanImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * One place to get data in and out. Uploads are sniffed — herd register,
 * weights/scans or logbook — shown as a preview, then routed to the right
 * importer. Every export is a CSV; "backup" zips them all.
 */
class DataController extends Controller
{
    public const KINDS = [
        'herd' => ['Kuddeboek / stamregister', 'Diere word bygevoeg of opgedateer (dieselfde ID = opdateer, nooit dupliseer nie). Ouers en grootouers word gekoppel.'],
        'scans' => ['Wegings / skanderings', 'Elke ry word \'n skandering, met gewig indien daar is. Onbekende oormerke word nuwe diere.'],
        'events' => ['Logboek', 'Behandelings, paring, geboortes, verkope en sterftes — gekoppel aan diere volgens ID of EID.'],
    ];

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

        return view('rfid.data', ['preview' => $preview, 'exports' => DataExporter::TYPES, 'kinds' => self::KINDS]);
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:csv,txt,tsv']]);

        $token = Str::random(32);
        $dir = dirname($this->path($token));
        is_dir($dir) || mkdir($dir, 0775, true);
        $request->file('file')->move($dir, basename($this->path($token)));

        $parsed = CsvReader::read($this->path($token));
        if (! $parsed['rows']) {
            @unlink($this->path($token));

            return back()->withErrors(['file' => 'Die lêer is leeg of kon nie gelees word nie.']);
        }

        $kind = $this->sniff($parsed['headers']);

        return redirect()->route('rfid.data')->with('preview', [
            'token' => $token,
            'name' => $request->file('file')->getClientOriginalName(),
            'kind' => $kind,
            'headers' => $parsed['headers'],
            'recognised' => $this->recognised($parsed['headers']),
            'rows' => array_slice($parsed['rows'], 0, 8),
            'count' => count($parsed['rows']),
        ]);
    }

    public function import(Request $request, HerdImporter $herd, ScanImporter $scans, EventImporter $events)
    {
        $data = $request->validate(['token' => ['required', 'alpha_num', 'size:32'], 'kind' => ['required', 'in:'.implode(',', array_keys(self::KINDS))]]);
        $path = $this->path($data['token']);
        abort_unless(is_file($path), 410, 'Die oplaai het verval — laai asseblief weer op.');

        $rows = CsvReader::read($path)['rows'];
        $user = $request->user();

        $message = match ($data['kind']) {
            'herd' => (function () use ($herd, $user, $rows) {
                $r = $herd->import($user, $rows);

                return "Kuddeboek: {$r['created']} nuut, {$r['updated']} opgedateer, {$r['skipped']} oorgeslaan.";
            })(),
            'scans' => (function () use ($scans, $user, $rows, $request) {
                $s = $scans->import($user, null, 'csv', $rows, $request->input('name'));

                return "Skanderings: {$s->scan_count} gestoor — {$s->matched_count} bekende diere, {$s->new_count} nuwe diere.";
            })(),
            'events' => (function () use ($events, $user, $rows) {
                $r = $events->import($user, $rows);

                return "Logboek: {$r['created']} inskrywings, {$r['skipped']} oorgeslaan".($r['unknown'] ? ' (onbekende diere: '.implode(', ', array_slice($r['unknown'], 0, 5)).')' : '').'.';
            })(),
        };
        @unlink($path);

        return redirect()->route('rfid.data')->with('status', $message);
    }

    public function backup(Request $request, DataExporter $exporter)
    {
        $path = $exporter->backup($request->user());

        return response()->download($path, 'farmtech-rugsteun-'.now()->format('Y-m-d').'.zip')->deleteFileAfterSend();
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
        }, "farmtech-sjabloon-{$kind}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Decide what a file is from its columns. */
    private function sniff(array $headers): string
    {
        $h = array_flip($headers);
        $has = fn (array $keys) => (bool) array_intersect_key($h, array_flip($keys));

        if ($has(EventImporter::TYPE) && $has(['date', 'datum'])) {
            return 'events';
        }
        if ($has(['sire', 'dam', 'vaar', 'moer', 'sire_sire', 'birth_date', 'dob', 'registered', 'tier', 'sex', 'geslag']) && ! $has(ScanImporter::WEIGHT)) {
            return 'herd';
        }

        return 'scans';
    }

    private function recognised(array $headers): array
    {
        $known = array_merge(HerdImporter::templateHeaders(), ScanImporter::EID, ScanImporter::VID, ScanImporter::WEIGHT, ScanImporter::DATE, ScanImporter::TYPE,
            EventImporter::TYPE, ['species', 'product', 'produk', 'dose', 'dosis', 'withdrawal_days', 'withdrawal_until', 'mate', 'count', 'result', 'uitslag', 'notes', 'notas', 'datum', 'gewig']);

        return array_values(array_intersect($headers, $known));
    }

    private function path(string $token): string
    {
        return storage_path('app/private/imports/'.auth()->id().'-'.$token.'.csv');
    }
}
