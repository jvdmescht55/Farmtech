<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Services\Herd\HerdImporter;
use App\Services\Herd\SmartImport;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function create()
    {
        return redirect()->route('rfid.data');
    }

    /** Old upload form: same smart import as Import & export. */
    public function store(Request $request, SmartImport $smart)
    {
        $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:csv,txt,tsv,xlsx,xlsm']]);
        $file = $request->file('file');
        $r = $smart->run($request->user(), $file->getRealPath(), $file->getClientOriginalName());

        return redirect()->route('rfid.animals.index')
            ->with('status', "Import: {$r['created']} added, {$r['updated']} updated, {$r['weights']} weighings, {$r['records']} records.");
    }

    public function template()
    {
        $headers = HerdImporter::templateHeaders();
        $example = array_fill_keys($headers, '');
        $example = array_merge($example, [
            'visual_id' => 'DVS 25 5082', 'eid' => '982000123456789', 'sex' => 'F', 'birth_date' => '24/03/2025',
            'birth_type' => '02', 'registered' => 'yes', 'gen' => '88', 'status' => 'active',
            'sire' => 'DVS 222435', 'dam' => 'DVS 220120', 'sire_sire' => 'DVS 211012', 'sire_dam' => 'DVS 199111',
            'dam_sire' => 'DVS 189165', 'dam_dam' => 'DVS 200022', 'sire_birth_type' => '02', 'dam_birth_type' => '01',
            'wean_dir' => '2.52', 'wean_dir_acc' => '65', 'wean_mat' => '0.54', 'wean_mat_acc' => '44',
            'dam_first' => '15.7', 'dam_sp' => '256', 'dam_tl' => '5', 'dam_lb' => '9', 'dam_lw' => '5', 'dam_mli' => '88', 'dam_epi' => '110',
            'notes' => 'Moontlik dragtig van DVS 23 3369',
        ]);

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, array_values($example));
            fclose($out);
        }, 'herd-import-template.csv', ['Content-Type' => 'text/csv']);
    }
}
