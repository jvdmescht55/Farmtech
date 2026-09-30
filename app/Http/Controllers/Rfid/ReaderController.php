<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rfid\Concerns\OwnsRecords;
use App\Models\Reader;
use App\Models\ReaderSync;
use App\Services\Herd\CsvReader;
use App\Services\Herd\ScanImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReaderController extends Controller
{
    use OwnsRecords;

    public function index(Request $request)
    {
        $user = $request->user();

        return view('rfid.readers.index', [
            'readers' => $user->readers()->withCount('syncs')->get(),
            'syncs' => ReaderSync::with('reader')->where('user_id', $user->id)->latest()->paginate(20),
            'shownToken' => session('shownToken'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:64'],
            'model' => ['nullable', 'string', 'max:64'],
        ]);
        $reader = $request->user()->readers()->create($data);

        return back()->with('status', "Reader \"{$reader->name}\" added.")->with('shownToken', [$reader->id => $reader->api_token]);
    }

    public function regenerateToken(Reader $reader)
    {
        $this->own($reader);
        $reader->update(['api_token' => Str::random(48)]);

        return back()->with('status', 'New sync token issued — update it on the reader/app. The old one no longer works.')
            ->with('shownToken', [$reader->id => $reader->api_token]);
    }

    public function destroy(Reader $reader)
    {
        $this->own($reader);
        $reader->delete();

        return back()->with('status', 'Reader removed. Its past scans are kept.');
    }

    public function upload(Request $request, ScanImporter $importer)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,tsv'],
            'reader_id' => ['nullable', 'integer'],
            'weigh_type' => ['nullable', 'in:birth,wean,post_wean,routine'],
        ]);

        $reader = $data['reader_id'] ?? null ? Reader::find($data['reader_id']) : null;
        if ($reader) {
            $this->own($reader);
        }

        $parsed = CsvReader::read($request->file('file')->getRealPath());
        if (! array_intersect($parsed['headers'], array_merge(ScanImporter::EID, ScanImporter::VID))) {
            return back()->withErrors(['file' => 'Couldn\'t find an EID or animal ID column. Expected a header like "EID", "Tag" or "Visual ID".']);
        }

        $rows = $parsed['rows'];
        if (! empty($data['weigh_type'])) {
            $rows = array_map(fn ($r) => $r + ['weigh_type' => $data['weigh_type']], $rows);
        }

        $sync = $importer->import($request->user(), $reader, 'csv', $rows, $request->file('file')->getClientOriginalName());

        return redirect()->route('rfid.sync.show', $sync)
            ->with('status', "Imported {$sync->scan_count} scans: {$sync->matched_count} matched, {$sync->new_count} new animals.");
    }

    public function showSync(ReaderSync $sync)
    {
        $this->own($sync);

        return view('rfid.readers.sync', [
            'sync' => $sync->load('reader'),
            'scans' => $sync->scans()->with('animal')->orderBy('scanned_at')->paginate(100),
        ]);
    }
}
