@extends('layouts.rfid')
@section('title', 'Herd')
@section('eyebrow'){{ number_format($totals['all']) }} head · your herd book @endsection
@section('actions')
    <a href="{{ route('rfid.data') }}" class="btn-secondary">Import / export</a>
    <a href="{{ route('rfid.animals.create') }}" class="btn-primary">+ Add animal</a>
@endsection
@php
    $sev = ['critical' => 'bg-[#B0452F]', 'warning' => 'bg-ochre', 'info' => 'bg-stone-light'];
@endphp

@section('content')
<form method="GET" class="flex flex-wrap items-center gap-2 mb-6" x-data="{ more: {{ request()->hasAny(['species', 'tier', 'status', 'sort']) ? 'true' : 'false' }} }">
    <div class="relative flex-1 min-w-[14rem]">
        <input name="q" value="{{ request('q') }}" placeholder="Search tag, ID or name…" class="field pl-11">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    </div>
    <select name="sex" class="field w-auto" onchange="this.form.submit()"><option value="">Male &amp; female</option><option value="F" @selected(request('sex') === 'F')>Female</option><option value="M" @selected(request('sex') === 'M')>Male</option></select>
    <button type="button" @click="more = !more" class="btn-line btn-sm h-11" x-text="more ? 'Fewer filters' : 'More filters'"></button>
    <div x-show="more" x-cloak class="w-full flex flex-wrap gap-2">
        <select name="species" class="field w-auto" onchange="this.form.submit()"><option value="">All species</option>@foreach (config('herd.species') as $k => $s)<option value="{{ $k }}" @selected(request('species') === $k)>{{ $s['plural'] }}</option>@endforeach</select>
        <select name="tier" class="field w-auto" onchange="this.form.submit()"><option value="">All tiers</option>@foreach (array_reverse(config('herd.tiers')) as $t)<option @selected(request('tier') === $t)>{{ $t }}</option>@endforeach<option value="?" @selected(request('tier') === '?')>? incomplete</option></select>
        <select name="status" class="field w-auto" onchange="this.form.submit()">@foreach (['active' => 'Active', 'sold' => 'Sold', 'dead' => 'Dead', 'culled' => 'Culled', 'all' => 'Everything'] as $k => $v)<option value="{{ $k }}" @selected(request('status', 'active') === $k)>{{ $v }}</option>@endforeach</select>
        <select name="sort" class="field w-auto" onchange="this.form.submit()"><option value="visual_id">By ID</option><option value="birth_date" @selected(request('sort') === 'birth_date')>Youngest first</option><option value="last_seen" @selected(request('sort') === 'last_seen')>Last scanned</option></select>
        @if (request()->hasAny(['q', 'sex', 'tier', 'status', 'sort', 'species']))<a href="{{ route('rfid.animals.index') }}" class="text-sm text-stone hover:text-char px-2 self-center">Clear</a>@endif
    </div>
</form>

<div class="panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th class="w-6"></th><th>Animal</th><th>Tag (EID)</th><th>Sex</th><th>Born</th><th>Tier</th><th class="text-right">Weight</th><th>Last seen</th></tr></thead>
            <tbody>
            @forelse ($animals as $a)
                @php
                    $w = $latestWeights->get($a->id);
                    $alert = $alertMap->get($a->id);
                @endphp
                <tr class="cursor-pointer" onclick="if (!event.target.closest('a')) location.href='{{ route('rfid.animals.show', $a) }}'">
                    <td>@if ($alert)<span class="block w-2 h-2 rounded-full {{ $sev[$alert['severity']] }}" title="{{ $alert['title'] }}"></span>@endif</td>
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
                <tr><td colspan="8" class="py-16 text-center text-stone">Nothing here yet. <a class="link-u text-char" href="{{ route('rfid.data') }}">Bring your herd in</a> or just start scanning.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $animals->links() }}</div>
@endsection
