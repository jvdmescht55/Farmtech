@extends('layouts.rfid')
@section('title', $animal->exists ? 'Edit '.$animal->visual_id : 'Add an animal')
@section('content')
@php
    $v = fn ($k, $d = null) => old($k, $d);
    $s = $animal->sire; $d = $animal->dam;
@endphp
<form method="POST" action="{{ $animal->exists ? route('rfid.animals.update', $animal) : route('rfid.animals.store') }}" class="space-y-6 max-w-5xl">
    @csrf
    @if ($animal->exists) @method('PUT') @endif

    <datalist id="known-ids">@foreach ($parents as $p)<option value="{{ $p->visual_id }}">@endforeach</datalist>

    <div class="app-card p-6">
        <h2 class="font-headline text-3xl mb-5">The basics</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div><label class="app-label">Animal ID (management tag) *</label><input name="visual_id" value="{{ $v('visual_id', $animal->visual_id) }}" required placeholder="DVS 25 5082" class="app-input font-mono"></div>
            <div><label class="app-label">Tag number (EID, 15 digits)</label><input name="eid" value="{{ $v('eid', $animal->eid) }}" inputmode="numeric" placeholder="982000123456789" class="app-input font-mono"></div>
            <div><label class="app-label">Name</label><input name="name" value="{{ $v('name', $animal->name) }}" class="app-input"></div>
            <div><label class="app-label">Breed</label><input name="breed" value="{{ $v('breed', $animal->breed) }}" class="app-input"></div>
            <div><label class="app-label">Species</label>
                <select name="species" class="app-input">@foreach (config('herd.species') as $k => $sp)<option value="{{ $k }}" @selected($v('species', $animal->species ?? 'sheep') === $k)>{{ $sp['label'] }}</option>@endforeach</select></div>
            <div><label class="app-label">Sex</label>
                <select name="sex" class="app-input"><option value="">—</option><option value="F" @selected($v('sex', $animal->sex)==='F')>Female</option><option value="M" @selected($v('sex', $animal->sex)==='M')>Male</option></select></div>
            <div><label class="app-label">Birth date</label><input type="date" name="birth_date" value="{{ $v('birth_date', $animal->birth_date?->toDateString()) }}" class="app-input"></div>
            <div><label class="app-label">Born as</label>
                <select name="birth_type" class="app-input"><option value="">—</option>@foreach (config('herd.birth_types') as $k => $l)<option value="{{ $k }}" @selected($v('birth_type', $animal->birth_type)===$k)>{{ $k }} · {{ $l }}</option>@endforeach</select></div>
            <div><label class="app-label">Status</label>
                <select name="status" class="app-input">@foreach (\App\Models\Animal::STATUSES as $k => $l)<option value="{{ $k }}" @selected($v('status', $animal->status)===$k)>{{ $l }}</option>@endforeach</select></div>
        </div>
    </div>

    <div class="app-card p-6">
        <h2 class="font-headline text-3xl">Registration &amp; pedigree <span class="text-stone text-xl">— optional</span></h2>
        <p class="text-sm text-stone mt-1 mb-5">Type IDs exactly as on the stud certificate. Ancestors that aren't in your herd are kept as pedigree references. IDs starting with <span class="font-num">CC</span> count as commercial stock.</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <label class="flex items-center gap-2 text-sm pt-6"><input type="hidden" name="registered" value="0"><input type="checkbox" name="registered" value="1" @checked($v('registered', $animal->registered)) class="rounded border-hairline text-char"> Registered (REG)</label>
            <label class="flex items-center gap-2 text-sm pt-6"><input type="hidden" name="is_commercial" value="0"><input type="checkbox" name="is_commercial" value="1" @checked($v('is_commercial', $animal->is_commercial)) class="rounded border-hairline text-char"> Commercial / foundation</label>
            <div><label class="app-label">Official tier (optional)</label>
                <select name="tier" class="app-input"><option value="">Work it out from the pedigree</option>@foreach (array_reverse(config('herd.tiers')) as $t)<option value="{{ $t }}" @selected($v('tier', $animal->tier)===$t)>{{ config('herd.tier_labels')[$t] }}</option>@endforeach</select></div>
            <div><label class="app-label">GEN</label><input type="number" name="gen_score" value="{{ $v('gen_score', $animal->gen_score) }}" class="app-input"></div>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            @foreach (['sire' => [$s, 'Sire'], 'dam' => [$d, 'Dam']] as $side => [$p, $label])
                <div class="rounded-lg border border-hairline p-4 space-y-3">
                    <div><label class="app-label">{{ $label }}</label><input name="{{ $side }}" list="known-ids" value="{{ $v($side, $p?->visual_id) }}" class="app-input font-mono"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="app-label">Its sire</label><input name="{{ $side }}_sire" list="known-ids" value="{{ $v($side.'_sire', $p?->sire?->visual_id) }}" class="app-input font-mono text-xs" @if($p?->in_herd) disabled title="Edit this on that animal's own page" @endif></div>
                        <div><label class="app-label">Its dam</label><input name="{{ $side }}_dam" list="known-ids" value="{{ $v($side.'_dam', $p?->dam?->visual_id) }}" class="app-input font-mono text-xs" @if($p?->in_herd) disabled title="Edit this on that animal's own page" @endif></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="app-card p-6">
        <h2 class="font-headline text-3xl mb-5">Breeding values (EBV) <span class="text-stone text-xl">— optional</span></h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach (config('herd.ebvs') as $k => $e)
                <div class="flex gap-2">
                    <div class="flex-1"><label class="app-label">{{ $e['label'] }}</label><input type="number" step="any" name="ebvs[{{ $k }}][v]" value="{{ old("ebvs.$k.v", $animal->ebvs[$k]['v'] ?? '') }}" class="app-input font-mono"></div>
                    <div class="w-20"><label class="app-label">Acc. %</label><input type="number" name="ebvs[{{ $k }}][acc]" value="{{ old("ebvs.$k.acc", $animal->ebvs[$k]['acc'] ?? '') }}" class="app-input font-mono"></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="app-card p-6">
        <h2 class="font-headline text-3xl mb-1">Lambing record <span class="text-stone text-xl">— optional</span></h2>
        <p class="text-sm text-stone mb-5">For ewes: the lambing record printed on sale catalogues.</p>
        <div class="grid grid-cols-3 sm:grid-cols-7 gap-3">
            @foreach (config('herd.dam_record') as $k => $label)
                <div><label class="app-label">{{ $label }}</label><input name="dam_record[{{ $k }}]" value="{{ old("dam_record.$k", $animal->dam_record[$k] ?? '') }}" class="app-input font-mono"></div>
            @endforeach
        </div>
    </div>

    <div class="app-card p-6">
        <label class="app-label">Note</label>
        <textarea name="notes" rows="2" placeholder="e.g. Moontlik dragtig van DVS 23 3369" class="app-input">{{ $v('notes', $animal->notes) }}</textarea>
        <p class="text-xs text-stone-light mt-1">Copied onto the lot when the animal goes into a sale catalogue.</p>
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">{{ $animal->exists ? 'Save' : 'Add animal' }}</button>
        <a href="{{ $animal->exists ? route('rfid.animals.show', $animal) : route('rfid.animals.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
@endsection
