<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rfid\Concerns\OwnsRecords;
use App\Models\Animal;
use App\Models\SaleCatalogue;
use App\Models\SaleLot;
use App\Services\Herd\PedigreeTier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogueController extends Controller
{
    use OwnsRecords;

    public function index(Request $request)
    {
        return view('rfid.catalogues.index', [
            'catalogues' => $request->user()->catalogues()->withCount('lots')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['breeder_line'] ??= $request->user()->breederLine();
        $data['breed'] ??= $request->user()->breed;
        $catalogue = $request->user()->catalogues()->create($data);

        return redirect()->route('rfid.catalogues.show', $catalogue)->with('status', 'Catalogue created — now add animals.');
    }

    public function show(Request $request, SaleCatalogue $catalogue, PedigreeTier $tiers)
    {
        $this->own($catalogue);
        $graph = PedigreeTier::graph($request->user()->id);
        $lots = $catalogue->lots()->get()->each(fn ($l) => $l->setRelation('animal', $graph->get($l->animal_id)));
        $inCatalogue = $lots->pluck('animal_id')->flip();

        $candidates = $graph->where('in_herd', true)->where('status', 'active')
            ->reject(fn ($a) => $inCatalogue->has($a->id))
            ->when($request->filled('sex'), fn ($c) => $c->where('sex', $request->input('sex')))
            ->when($request->filled('q'), fn ($c) => $c->filter(fn ($a) => str_contains(mb_strtoupper($a->visual_id.' '.$a->eid), mb_strtoupper($request->input('q')))))
            ->sortBy('visual_id', SORT_NATURAL)
            ->values();

        return view('rfid.catalogues.show', [
            'catalogue' => $catalogue,
            'lots' => $lots,
            'candidates' => $candidates,
            'tiers' => $tiers,
        ]);
    }

    public function update(Request $request, SaleCatalogue $catalogue)
    {
        $this->own($catalogue);
        $catalogue->update($this->validated($request));

        return back()->with('status', 'Catalogue details saved.');
    }

    public function destroy(SaleCatalogue $catalogue)
    {
        $this->own($catalogue);
        $catalogue->delete();

        return redirect()->route('rfid.catalogues.index')->with('status', 'Catalogue deleted. The animals themselves are untouched.');
    }

    public function addLots(Request $request, SaleCatalogue $catalogue)
    {
        $this->own($catalogue);
        $ids = $request->validate(['animal_ids' => ['required', 'array'], 'animal_ids.*' => ['integer']])['animal_ids'];

        $animals = Animal::where('user_id', $request->user()->id)->whereIn('id', $ids)->get()->sortBy('visual_id', SORT_NATURAL);
        $position = (int) $catalogue->lots()->max('position');
        foreach ($animals as $animal) {
            SaleLot::firstOrCreate(
                ['sale_catalogue_id' => $catalogue->id, 'animal_id' => $animal->id],
                ['position' => ++$position, 'comment' => $animal->notes],
            );
        }

        return back()->with('status', $animals->count().' animal(s) added. Use "Number lots" to assign lot numbers.');
    }

    public function updateLots(Request $request, SaleCatalogue $catalogue)
    {
        $this->own($catalogue);
        $data = $request->validate([
            'lots' => ['required', 'array'],
            'lots.*.lot_number' => ['nullable', 'string', 'max:12'],
            'lots.*.position' => ['nullable', 'integer', 'min:0'],
            'lots.*.comment' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($catalogue->lots()->get() as $lot) {
            if (isset($data['lots'][$lot->id])) {
                $row = $data['lots'][$lot->id];
                $lot->update([
                    'lot_number' => isset($row['lot_number']) ? strtoupper(trim($row['lot_number'])) : null,
                    'position' => $row['position'] ?? $lot->position,
                    'comment' => $row['comment'] ?? null,
                ]);
            }
        }

        return back()->with('status', 'Lots saved.');
    }

    public function autoNumber(Request $request, SaleCatalogue $catalogue)
    {
        $this->own($catalogue);
        $data = $request->validate([
            'start' => ['required', 'integer', 'min:1', 'max:99999'],
            'per_lot' => ['required', 'integer', 'min:1', 'max:26'],
            'order' => ['required', Rule::in(['current', 'visual_id', 'birth_date'])],
        ]);

        if ($data['order'] !== 'current') {
            $lots = $catalogue->lots()->with('animal')->get()
                ->sortBy(fn ($l) => $data['order'] === 'birth_date' ? ($l->animal->birth_date?->timestamp ?? PHP_INT_MAX) : $l->animal->visual_id, SORT_NATURAL)
                ->values();
            foreach ($lots as $i => $lot) {
                $lot->update(['position' => $i + 1]);
            }
        }

        $catalogue->autoNumber($data['start'], $data['per_lot']);

        return back()->with('status', 'Lots numbered.');
    }

    public function removeLot(SaleCatalogue $catalogue, SaleLot $lot)
    {
        $this->own($catalogue);
        abort_unless($lot->sale_catalogue_id === $catalogue->id, 404);
        $lot->delete();

        return back()->with('status', 'Lot removed.');
    }

    public function print(Request $request, SaleCatalogue $catalogue, PedigreeTier $tiers)
    {
        $this->own($catalogue);

        return view('rfid.catalogues.print', $this->printData($request, $catalogue, $tiers));
    }

    public function export(Request $request, SaleCatalogue $catalogue, PedigreeTier $tiers)
    {
        $this->own($catalogue);
        $d = $this->printData($request, $catalogue, $tiers);
        $ebvs = config('herd.ebvs');
        $rec = config('herd.dam_record');

        return response()->streamDownload(function () use ($d, $ebvs, $rec) {
            $out = fopen('php://output', 'w');
            $head = ['Lot', 'Animal ID', 'EID', 'Birth date', 'Birth type', 'Reg', 'Status', 'GEN'];
            foreach ($ebvs as $e) {
                array_push($head, $e['label'], $e['label'].' acc');
            }
            foreach ($rec as $label) {
                $head[] = 'Dam '.$label;
            }
            array_push($head, 'Sire', 'Dam', 'Sire sire', 'Sire dam', 'Dam sire', 'Dam dam', 'Comment');
            fputcsv($out, $head);

            foreach ($d['lots'] as $lot) {
                $a = $lot->animal;
                $row = [$lot->lot_number, $a->visual_id, $a->eid, $a->birth_date?->format('d/m/Y'), $a->birth_type, $a->registered ? 'REG' : '', $lot->tier, $a->gen_score];
                foreach (array_keys($ebvs) as $k) {
                    array_push($row, $a->ebvs[$k]['v'] ?? '', $a->ebvs[$k]['acc'] ?? '');
                }
                foreach (array_keys($rec) as $k) {
                    $row[] = $a->dam_record[$k] ?? '';
                }
                array_push($row, $a->sire?->visual_id, $a->dam?->visual_id, $a->sire?->sire?->visual_id, $a->sire?->dam?->visual_id,
                    $a->dam?->sire?->visual_id, $a->dam?->dam?->visual_id, $lot->comment);
                fputcsv($out, $row);
            }
            fclose($out);
        }, str($catalogue->title)->slug().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function printData(Request $request, SaleCatalogue $catalogue, PedigreeTier $tiers): array
    {
        $graph = PedigreeTier::graph($request->user()->id);
        $lots = $catalogue->lots()->get()->each(function ($l) use ($graph, $tiers) {
            $l->setRelation('animal', $graph->get($l->animal_id));
            $l->tier = $tiers->resolve($l->animal)->tier;
        });

        return ['catalogue' => $catalogue, 'lots' => $lots, 'user' => $request->user()];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'breed' => ['nullable', 'string', 'max:64'],
            'sale_date' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:32'],
            'breeder_line' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
