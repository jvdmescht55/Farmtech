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

        return back()->with('status', "Toestel \"{$reader->name}\" bygevoeg.")->with('shownToken', [$reader->id => $reader->plainToken]);
    }

    /** Farmer types the 6-digit code the device is showing. */
    public function pair(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string'], 'name' => ['nullable', 'string', 'max:255']]);
        $code = preg_replace('/\D/', '', $data['code']);
        $pairing = \App\Models\DevicePairing::open()->where('code', $code)->first();

        if (! $pairing) {
            return back()->withErrors(['code' => 'Daai kode is ongeldig of het verval. Begin weer koppel op die toestel.']);
        }

        $user = $request->user();
        $reader = $pairing->serial ? $user->readers()->where('serial', $pairing->serial)->first() : null;
        if ($reader) {
            $plain = $reader->rotateToken();
            $reader->update(array_filter(['model' => $pairing->model, 'firmware' => $pairing->firmware]));
        } else {
            $reader = $user->readers()->create([
                'name' => ($data['name'] ?? null) ?: ($pairing->model ?: 'Farmtech-skandeerder').($pairing->serial ? ' · '.$pairing->serial : ''),
                'serial' => $pairing->serial,
                'model' => $pairing->model,
                'firmware' => $pairing->firmware,
            ]);
            $plain = $reader->plainToken;
        }

        $pairing->update(['reader_id' => $reader->id, 'token_encrypted' => $plain, 'claimed_at' => now()]);

        return back()->with('status', "Gekoppel! \"{$reader->name}\" haal nou sy sleutel — dit neem 'n paar sekondes.");
    }

    public function regenerateToken(Reader $reader)
    {
        $this->own($reader);
        $plain = $reader->rotateToken();

        return back()->with('status', 'Nuwe sinch-sleutel uitgereik — sit dit op die toestel. Die ou sleutel werk nie meer nie.')
            ->with('shownToken', [$reader->id => $plain]);
    }

    public function destroy(Reader $reader)
    {
        $this->own($reader);
        $reader->delete();

        return back()->with('status', 'Toestel verwyder. Sy skanderings bly gestoor.');
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

    /** Reference ESP32 firmware, ready to adapt. */
    public function firmware()
    {
        return response()->download(resource_path('firmware/farmtech_scanner.ino'), 'farmtech_scanner.ino', ['Content-Type' => 'text/plain']);
    }
}
