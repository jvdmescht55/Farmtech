@extends('layouts.rfid')
@section('title', 'Herd')
@section('eyebrow'){{ number_format($totals['all']) }} head · your herd book @endsection
@section('actions')
    <a href="{{ route('rfid.data') }}" class="btn-secondary">Import &amp; export</a>
    <a href="{{ route('rfid.animals.create') }}" class="btn-primary">+ Add animal</a>
@endsection
@php
    $sev = ['critical' => 'bg-[#B0452F]', 'warning' => 'bg-ochre', 'info' => 'bg-stone-light'];
@endphp

@section('content')
<form method="GET" class="flex flex-wrap items-center gap-2 mb-6" x-data="{ more: {{ request()->hasAny(['species', 'tier', 'status', 'sort']) ? 'true' : 'false' }} }">
    <div class="relative flex-1 min-w-[14rem]">
        <input name="q" aria-label="Search the herd" value="{{ request('q') }}" placeholder="Search tag, ID or name…" class="field pl-11">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    </div>
    <select name="sex" aria-label="Sex" class="field w-auto" onchange="this.form.submit()"><option value="">Male &amp; female</option><option value="F" @selected(request('sex') === 'F')>Female</option><option value="M" @selected(request('sex') === 'M')>Male</option></select>
    <button type="button" @click="more = !more" class="btn-line btn-sm h-11" x-text="more ? 'Fewer filters' : 'More filters'"></button>
    <div x-show="more" x-cloak class="w-full flex flex-wrap gap-2">
        <select name="species" aria-label="Species" class="field w-auto" onchange="this.form.submit()"><option value="">All species</option>@foreach (config('herd.species') as $k => $s)<option value="{{ $k }}" @selected(request('species') === $k)>{{ $s['plural'] }}</option>@endforeach</select>
        <select name="tier" aria-label="Tier" class="field w-auto" onchange="this.form.submit()"><option value="">All tiers</option>@foreach (array_reverse(config('herd.tiers')) as $t)<option @selected(request('tier') === $t)>{{ $t }}</option>@endforeach<option value="?" @selected(request('tier') === '?')>? incomplete</option></select>
        <select name="status" aria-label="Status" class="field w-auto" onchange="this.form.submit()">@foreach (['active' => 'Active', 'sold' => 'Sold', 'dead' => 'Dead', 'culled' => 'Culled', 'all' => 'Everything'] as $k => $v)<option value="{{ $k }}" @selected(request('status', 'active') === $k)>{{ $v }}</option>@endforeach</select>
        <select name="sort" aria-label="Sort" class="field w-auto" onchange="this.form.submit()"><option value="visual_id">By ID</option><option value="birth_date" @selected(request('sort') === 'birth_date')>Youngest first</option><option value="last_seen" @selected(request('sort') === 'last_seen')>Last scanned</option><option value="heaviest" @selected(request('sort') === 'heaviest')>Heaviest first</option><option value="lightest" @selected(request('sort') === 'lightest')>Lightest first</option></select>
        @if (request()->hasAny(['q', 'sex', 'tier', 'status', 'sort', 'species']))<a href="{{ route('rfid.animals.index') }}" class="text-sm text-stone hover:text-char px-2 self-center">Clear</a>@endif
    </div>
</form>

<div x-data="{ picking: false, ids: [], all: @js($animals->pluck('id')), toggle(id) { this.ids.includes(id) ? this.ids = this.ids.filter(i => i !== id) : this.ids.push(id) } }">
<div class="flex items-center justify-between gap-3 mb-3 text-sm">
    <span class="text-stone">{{ $animals->total() }} {{ \Illuminate\Support\Str::plural('animal', $animals->total()) }}{{ request('status', 'active') === 'active' ? ' in the active herd' : '' }}
        @if (request('status', 'active') === 'active' && $otherStatuses) · <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}" class="underline">show {{ $otherStatuses }} sold, dead &amp; culled</a>@endif
        · <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="underline">Export this list</a></span>
    <button type="button" @click="picking = !picking; ids = []" class="btn-line btn-sm" x-text="picking ? 'Done' : 'Select'"></button>
</div>
{{-- Phones: one tidy card per animal, the numbers that matter up front --}}
<div class="sm:hidden panel divide-y divide-hairline overflow-hidden">
    @forelse ($animals as $a)
        @php
            $w = $latestWeights->get($a->id);
            $alert = $alertMap->get($a->id);
        @endphp
        <a href="{{ route('rfid.animals.show', $a) }}" @click="if (picking) { $event.preventDefault(); toggle({{ $a->id }}) }" class="flex items-center gap-3 px-4 py-3.5 active:bg-sand-light" :class="ids.includes({{ $a->id }}) && 'bg-ochre/10'">
            <span x-show="picking" x-cloak class="w-5 h-5 shrink-0 rounded border-2 grid place-items-center text-[11px]" :class="ids.includes({{ $a->id }}) ? 'bg-char border-char text-sand' : 'border-hairline'"><span x-show="ids.includes({{ $a->id }})">✓</span></span>
            <span class="w-2 h-2 shrink-0 rounded-full {{ $alert ? $sev[$alert['severity']] : 'bg-transparent' }}"></span>
            <span class="flex-1 min-w-0">
                <span class="block font-num font-medium truncate">{{ $a->visual_id }}</span>
                <span class="block text-xs text-stone truncate">{{ $a->sexLabel() }}{{ $a->birth_date ? ' · '.$a->birth_date->format('M Y') : '' }}{{ $a->status !== 'active' ? ' · '.\App\Models\Animal::STATUSES[$a->status] : '' }}{{ $alert ? ' · '.$alert['title'] : '' }}</span>
            </span>
            <x-tier :tier="$a->computed_tier" />
            <span class="w-16 text-right font-num text-sm">{{ $w ? $w->weight_kg.' kg' : '—' }}</span>
        </a>
    @empty
        @if (request()->hasAny(['q', 'sex', 'tier', 'species']) || request('status', 'active') !== 'active')
            <p class="py-16 text-center text-stone">No animals match these filters. <a class="link-u text-char" href="{{ route('rfid.animals.index') }}">Clear filters</a></p>
        @else
        <p class="py-16 text-center text-stone">Nothing here yet. <a class="link-u text-char" href="{{ route('rfid.data') }}">Bring your herd in</a> or just start scanning.</p>
        @endif
    @endforelse
</div>

<div class="hidden sm:block panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th class="w-6"><input x-show="picking" x-cloak type="checkbox" aria-label="Select all on this page" @change="ids = $event.target.checked ? [...all] : []" class="rounded border-hairline"></th><th>Animal</th><th>Tag (EID)</th><th>Sex</th><th>Born</th><th>Tier</th><th class="text-right">Weight</th><th>Last seen</th></tr></thead>
            <tbody>
            @forelse ($animals as $a)
                @php
                    $w = $latestWeights->get($a->id);
                    $alert = $alertMap->get($a->id);
                @endphp
                <tr class="cursor-pointer" :class="ids.includes({{ $a->id }}) && 'bg-ochre/10'" @click="if (event.target.closest('a,input')) return; picking ? toggle({{ $a->id }}) : location.href='{{ route('rfid.animals.show', $a) }}'">
                    <td><input x-show="picking" x-cloak type="checkbox" aria-label="Select {{ $a->visual_id }}" :checked="ids.includes({{ $a->id }})" @change="toggle({{ $a->id }})" class="rounded border-hairline">@if ($alert)<span x-show="!picking" class="block w-2 h-2 rounded-full {{ $sev[$alert['severity']] }}" title="{{ $alert['title'] }}"></span>@endif</td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('rfid.animals.show', $a) }}" class="font-num font-medium link-u">{{ $a->visual_id }}</a>
                        @if ($a->status !== 'active')<span class="ml-2 chip bg-sand-deep text-stone">{{ \App\Models\Animal::STATUSES[$a->status] }}</span>@endif
                    </td>
                    <td class="font-num text-xs text-stone whitespace-nowrap">{{ $a->eid ?? '—' }}</td>
                    <td class="text-stone">{{ $a->sexLabel() }}</td>
                    <td class="whitespace-nowrap num text-sm">{{ $a->birth_date?->format('d/m/Y') ?? '—' }}</td>
                    <td><x-tier :tier="$a->computed_tier" /></td>
                    <td class="text-right num whitespace-nowrap">{{ $w ? $w->weight_kg.' kg' : '—' }}</td>
                    <td class="text-stone whitespace-nowrap text-sm">{{ $a->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-16 text-center text-stone">@if (request()->hasAny(['q', 'sex', 'tier', 'species']) || request('status', 'active') !== 'active')No animals match these filters. <a class="link-u text-char" href="{{ route('rfid.animals.index') }}">Clear filters</a>@else Nothing here yet. <a class="link-u text-char" href="{{ route('rfid.data') }}">Bring your herd in</a> or just start scanning.@endif</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $animals->links() }}</div>

{{-- Action bar for selected animals --}}
<form method="POST" action="{{ route('rfid.animals.bulk') }}" x-show="picking && ids.length" x-cloak x-transition
      class="fixed inset-x-3 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 bottom-20 lg:bottom-6 z-40 rounded-2xl bg-char text-sand shadow-2xl p-3 flex flex-wrap items-center gap-2"
      x-data="{ sure: false }" @submit="if ($event.submitter.value === 'delete' && !sure) { $event.preventDefault(); sure = true }">
    @csrf
    <template x-for="id in ids" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
    <span class="px-2 text-sm"><span x-text="ids.length"></span> selected</span>
    <button name="action" value="sold" class="rounded-full px-4 h-9 text-sm bg-white/10 hover:bg-white/20">Sold</button>
    <button name="action" value="dead" class="rounded-full px-4 h-9 text-sm bg-white/10 hover:bg-white/20">Died</button>
    @if (request('status', 'active') !== 'active')<button name="action" value="active" class="rounded-full px-4 h-9 text-sm bg-white/10 hover:bg-white/20">Back to active</button>@endif
    <button name="action" value="delete" class="rounded-full px-4 h-9 text-sm bg-[#B0452F] hover:brightness-110" x-text="sure ? 'Tap again: delete for good' : 'Delete (test animals)'"></button>
</form>
</div>
@endsection
