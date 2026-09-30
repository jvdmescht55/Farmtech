<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Services\Herd\CsvReader;
use App\Services\Herd\HerdImporter;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function create()
    {
        return redirect()->route('rfid.data');
    }

    public function store(Request $request, HerdImporter $importer)
    {
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,tsv']]);
        $parsed = CsvReader::read($request->file('file')->getRealPath());

        if (! in_array('visual_id', $parsed['headers'], true) && ! array_intersect($parsed['headers'], ['vid', 'animal_id', 'dier_id', 'id'])) {
            return back()->withErrors(['file' => 'The file needs a "visual_id" (or "Animal ID") column. Download the template to see the layout.']);
        }

        $result = $importer->import($request->user(), $parsed['rows']);

        return redirect()->route('rfid.animals.index')
            ->with('status', "Herd import: {$result['created']} added, {$result['updated']} updated, {$result['skipped']} skipped (no ID).");
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
