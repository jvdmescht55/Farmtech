<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rfid\Concerns\OwnsRecords;
use App\Models\Animal;
use App\Models\SaleLot;
use App\Models\Scan;
use App\Services\Herd\PedigreeTier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class AnimalController extends Controller
{
    use OwnsRecords;

    public function index(Request $request, PedigreeTier $tiers)
    {
        $graph = PedigreeTier::graph($request->user()->id);
        $status = $request->input('status', 'active');

        $animals = $graph->where('in_herd', true)
            ->when($status !== 'all', fn ($c) => $c->where('status', $status))
            ->when($request->filled('sex'), fn ($c) => $c->where('sex', $request->input('sex')))
            ->when($request->filled('species'), fn ($c) => $c->where('species', $request->input('species')))
            ->when($request->filled('q'), function ($c) use ($request) {
                $q = mb_strtoupper(trim($request->input('q')));
                $digits = preg_replace('/\D/', '', $q);

                return $c->filter(fn ($a) => str_contains(mb_strtoupper($a->visual_id), $q)
                    || ($digits !== '' && $a->eid && str_contains($a->eid, $digits))
                    || ($a->name && str_contains(mb_strtoupper($a->name), $q)));
            })
            ->map(function ($a) use ($tiers) {
                $a->computed_tier = $tiers->resolve($a)->tier;

                return $a;
            })
            ->when($request->filled('tier'), fn ($c) => $c->filter(fn ($a) => ($a->computed_tier ?? '?') === $request->input('tier')));

        $sort = $request->input('sort', 'visual_id');
        $animals = (match ($sort) {
            'birth_date' => $animals->sortByDesc(fn ($a) => $a->birth_date?->timestamp ?? 0),
            'last_seen' => $animals->sortByDesc(fn ($a) => $a->last_seen_at?->timestamp ?? 0),
            default => $animals->sortBy('visual_id', SORT_NATURAL),
        })->values();

        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator($animals->forPage($page, $perPage), $animals->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $latestWeights = Scan::whereIn('animal_id', $paginator->pluck('id'))
            ->whereNotNull('weight_kg')
            ->orderByDesc('scanned_at')
            ->get(['animal_id', 'weight_kg', 'scanned_at'])
            ->unique('animal_id')
            ->keyBy('animal_id');

        $alertMap = app(\App\Services\Herd\HerdAlerts::class)->forUser($request->user()->id)
            ->filter(fn ($a) => $a['animal'])->groupBy(fn ($a) => $a['animal']->id)
            ->map(fn ($g) => $g->sortBy(fn ($a) => ['critical' => 0, 'warning' => 1, 'info' => 2][$a['severity']])->first());

        return view('rfid.animals.index', ['animals' => $paginator, 'latestWeights' => $latestWeights, 'graph' => $graph, 'alertMap' => $alertMap,
            'totals' => ['all' => $graph->where('in_herd', true)->where('status', 'active')->count()]]);
    }

    public function create(Request $request)
    {
        return view('rfid.animals.form', ['animal' => new Animal(['status' => 'active', 'breed' => $request->user()->breed, 'species' => $request->user()->species ?: 'sheep']), 'parents' => $this->parentOptions($request)]);
    }

    public function store(Request $request)
    {
        $animal = new Animal(['user_id' => $request->user()->id, 'in_herd' => true]);
        $this->save($request, $animal);

        return redirect()->route('rfid.animals.show', $animal)->with('status', "{$animal->visual_id} added to the herd.");
    }

    public function show(Request $request, Animal $animal, PedigreeTier $tiers)
    {
        $this->own($animal);
        $graph = PedigreeTier::graph($request->user()->id);
        $animal = $graph->get($animal->id);

        $weights = $animal->scans()->whereNotNull('weight_kg')->orderBy('scanned_at')->get();
        $gains = [];
        foreach ($weights->values() as $i => $w) {
            $prev = $weights->values()[$i - 1] ?? null;
            $days = $prev ? max(1, $prev->scanned_at->diffInDays($w->scanned_at)) : null;
            $gains[$w->id] = $prev ? round((($w->weight_kg - $prev->weight_kg) * 1000) / $days) : null;
        }

        $target = (float) $request->input('target', 0) ?: null;
        $last = $weights->last();
        $recentAdg = $last && $gains[$last->id] !== null ? $gains[$last->id] : null;

        return view('rfid.animals.show', [
            'animal' => $animal,
            'weightPoints' => $weights->map(fn ($w) => ['label' => $w->scanned_at->toDateString(), 'value' => (float) $w->weight_kg])->values()->all(),
            'target' => $target,
            'daysToTarget' => $target && $last ? \App\Services\Herd\WeighStats::daysToTarget((float) $last->weight_kg, $recentAdg, $target) : null,
            'recentAdg' => $recentAdg,
            'lifeAdg' => $weights->count() > 1 ? (int) round(($last->weight_kg - $weights->first()->weight_kg) * 1000 / max(1, $weights->first()->scanned_at->diffInDays($last->scanned_at))) : null,
            'kg100' => \App\Services\Herd\WeighStats::weightAtAge($weights->map(fn ($w) => (object) ['date' => $w->scanned_at->toDateString(), 'kg' => (float) $w->weight_kg]), $animal->birth_date, 100),
            'tier' => $tiers->resolve($animal),
            'computed' => $tiers->computed($animal),
            'weights' => $weights,
            'gains' => $gains,
            'scans' => $animal->scans()->with('sync.reader')->limit(25)->get(),
            'offspring' => $graph->filter(fn ($a) => $a->sire_id === $animal->id || $a->dam_id === $animal->id)->sortByDesc('birth_date'),
            'lots' => SaleLot::with('catalogue')->where('animal_id', $animal->id)->get(),
            'alerts' => app(\App\Services\Herd\HerdAlerts::class)->forAnimal($request->user()->id, $animal->id),
            'events' => $animal->events()->with('mate')->get(),
        ]);
    }

    public function edit(Request $request, Animal $animal)
    {
        $this->own($animal);

        return view('rfid.animals.form', ['animal' => $animal->load('sire.sire', 'sire.dam', 'dam.sire', 'dam.dam'), 'parents' => $this->parentOptions($request)]);
    }

    public function update(Request $request, Animal $animal)
    {
        $this->own($animal);
        $this->save($request, $animal);

        return redirect()->route('rfid.animals.show', $animal)->with('status', 'Saved.');
    }

    public function addWeight(Request $request, Animal $animal)
    {
        $this->own($animal);
        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'weigh_type' => ['nullable', Rule::in(array_keys(Scan::WEIGH_TYPES))],
            'scanned_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        Scan::create($data + ['user_id' => $animal->user_id, 'animal_id' => $animal->id, 'eid' => $animal->eid, 'visual_id' => $animal->visual_id]);
        $animal->update(['last_seen_at' => max($animal->last_seen_at, now()->parse($data['scanned_at']))]);

        return back()->with('status', 'Weight recorded.');
    }

    private function save(Request $request, Animal $animal): void
    {
        $userId = $request->user()->id;
        $request->merge(['visual_id' => Animal::normalizeVisualId($request->input('visual_id')), 'eid' => preg_replace('/\D/', '', (string) $request->input('eid')) ?: null]);

        $data = $request->validate([
            'visual_id' => ['required', 'string', 'max:32', Rule::unique('animals')->where('user_id', $userId)->ignore($animal->id)],
            'eid' => ['nullable', 'digits_between:8,20', Rule::unique('animals')->where('user_id', $userId)->ignore($animal->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'in:M,F'],
            'species' => ['required', Rule::in(array_keys(config('herd.species')))],
            'breed' => ['nullable', 'string', 'max:64'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_type' => ['nullable', Rule::in(array_keys(config('herd.birth_types')))],
            'registered' => ['boolean'],
            'is_commercial' => ['boolean'],
            'tier' => ['nullable', Rule::in(config('herd.tiers'))],
            'gen_score' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', Rule::in(array_keys(Animal::STATUSES))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sire' => ['nullable', 'string', 'max:32'],
            'dam' => ['nullable', 'string', 'max:32'],
            'sire_sire' => ['nullable', 'string', 'max:32'],
            'sire_dam' => ['nullable', 'string', 'max:32'],
            'dam_sire' => ['nullable', 'string', 'max:32'],
            'dam_dam' => ['nullable', 'string', 'max:32'],
            'ebvs' => ['nullable', 'array'],
            'ebvs.*.v' => ['nullable', 'numeric'],
            'ebvs.*.acc' => ['nullable', 'integer', 'min:0', 'max:100'],
            'dam_record' => ['nullable', 'array'],
            'dam_record.*' => ['nullable', 'string', 'max:16'],
        ]);

        $ebvs = collect($data['ebvs'] ?? [])
            ->only(array_keys(config('herd.ebvs')))
            ->filter(fn ($e) => ($e['v'] ?? null) !== null && $e['v'] !== '')
            ->map(fn ($e) => ['v' => (float) $e['v'], 'acc' => isset($e['acc']) && $e['acc'] !== '' ? (int) $e['acc'] : null])
            ->all();
        $record = collect($data['dam_record'] ?? [])->only(array_keys(config('herd.dam_record')))->filter(fn ($v) => $v !== null && $v !== '')->all();

        $animal->fill([
            'visual_id' => $data['visual_id'],
            'eid' => $data['eid'],
            'name' => $data['name'] ?? null,
            'sex' => $data['sex'] ?? null,
            'species' => $data['species'],
            'breed' => $data['breed'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'birth_type' => $data['birth_type'] ?? null,
            'registered' => $request->boolean('registered'),
            'is_commercial' => $request->boolean('is_commercial'),
            'tier' => $data['tier'] ?? null,
            'gen_score' => $data['gen_score'] ?? null,
            'status' => $data['status'],
            'status_date' => $data['status'] === 'active' ? null : ($animal->status === $data['status'] ? $animal->status_date : now()->toDateString()),
            'notes' => $data['notes'] ?? null,
            'ebvs' => $ebvs ?: null,
            'dam_record' => $record ?: null,
            'in_herd' => true,
        ])->save();

        foreach (['sire', 'dam'] as $side) {
            $parent = Animal::findOrReference($userId, $data[$side] ?? null);
            if ($parent && $parent->id === $animal->id) {
                $parent = null;
            }
            $animal->{$side.'_id'} = $parent?->id;

            // Grandparents are only written onto reference ancestors; a parent
            // that is itself in this herd keeps its own recorded pedigree.
            if ($parent && ! $parent->in_herd) {
                $gs = Animal::findOrReference($userId, $data[$side.'_sire'] ?? null);
                $gd = Animal::findOrReference($userId, $data[$side.'_dam'] ?? null);
                $parent->update(['sire_id' => $gs?->id ?? $parent->sire_id, 'dam_id' => $gd?->id ?? $parent->dam_id]);
            }
        }
        $animal->save();
    }

    private function parentOptions(Request $request)
    {
        return Animal::where('user_id', $request->user()->id)->orderBy('visual_id')->limit(2000)->get(['visual_id', 'sex']);
    }
}
