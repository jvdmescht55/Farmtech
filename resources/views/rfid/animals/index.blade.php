@extends('layouts.rfid')
@section('title', 'Kudde')
@section('eyebrow')Jou kuddeboek @endsection
@section('actions')
    <a href="{{ route('rfid.import.create') }}" class="btn-secondary">Voer in (CSV)</a>
    <a href="{{ route('rfid.animals.create') }}" class="btn-primary">+ Dier</a>
@endsection
@section('content')
<form method="GET" class="app-card p-4 flex flex-wrap items-end gap-3 mb-6">
    <div class="flex-1 min-w-[12rem]">
        <label class="app-label">Search</label>
        <input name="q" value="{{ request('q') }}" placeholder="Visual ID, EID or name" class="app-input">
    </div>
    <div>
        <label class="app-label">Sex</label>
        <select name="sex" class="app-input"><option value="">All</option><option value="F" @selected(request('sex')==='F')>Ewes</option><option value="M" @selected(request('sex')==='M')>Rams</option></select>
    </div>
    <div>
        <label class="app-label">Tier</label>
        <select name="tier" class="app-input">
            <option value="">All</option>
            @foreach (array_reverse(config('herd.tiers')) as $t)<option @selected(request('tier')===$t)>{{ $t }}</option>@endforeach
            <option value="?" @selected(request('tier')==='?')>? (incomplete)</option>
        </select>
    </div>
    <div>
        <label class="app-label">Status</label>
        <select name="status" class="app-input">
            @foreach (['active' => 'Active'] + \App\Models\Animal::STATUSES + ['all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected(request('status','active')===$k)>{{ $v }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="app-label">Sort</label>
        <select name="sort" class="app-input">
            <option value="visual_id">ID</option>
            <option value="birth_date" @selected(request('sort')==='birth_date')>Youngest first</option>
            <option value="last_seen" @selected(request('sort')==='last_seen')>Last scanned</option>
        </select>
    </div>
    <button class="btn-primary">Filter</button>
    @if (request()->hasAny(['q','sex','tier','status','sort']))<a href="{{ route('rfid.animals.index') }}" class="btn-secondary">Clear</a>@endif
</form>

<div class="app-card overflow-hidden">
    <div class="px-5 py-3 text-sm text-stone border-b border-hairline">{{ number_format($animals->total()) }} animals</div>
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead><tr><th>Animal ID</th><th>EID</th><th>Sex</th><th>Born</th><th>Tier</th><th>Sire</th><th>Dam</th><th class="text-right">Last weight</th><th>Last seen</th></tr></thead>
            <tbody>
            @forelse ($animals as $a)
                @php($w = $latestWeights->get($a->id))
                <tr>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('rfid.animals.show', $a) }}" class="font-mono font-medium text-char hover:underline">{{ $a->visual_id }}</a>
                        @if ($a->registered)<span class="ml-1 text-[10px] font-bold text-stone-light">REG</span>@endif
                        @if ($a->status !== 'active')<span class="ml-1 text-[10px] uppercase font-medium text-[#B0452F]">{{ $a->status }}</span>@endif
                    </td>
                    <td class="font-mono text-xs text-stone whitespace-nowrap">{{ $a->eid ?? '—' }}</td>
                    <td>{{ $a->sexLabel() }}</td>
                    <td class="whitespace-nowrap">{{ $a->birth_date?->format('d/m/Y') ?? '—' }}@if($a->birth_type)<sup class="text-stone-light ml-0.5">{{ $a->birth_type }}</sup>@endif</td>
                    <td><x-tier :tier="$a->computed_tier" /></td>
                    <td class="font-mono text-xs whitespace-nowrap">{{ $a->sire?->visual_id ?? '—' }}</td>
                    <td class="font-mono text-xs whitespace-nowrap">{{ $a->dam?->visual_id ?? '—' }}</td>
                    <td class="text-right font-mono whitespace-nowrap">{{ $w ? $w->weight_kg.' kg' : '—' }}</td>
                    <td class="text-stone whitespace-nowrap text-xs">{{ $a->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-12 text-stone">No animals match. <a class="text-char font-medium" href="{{ route('rfid.import.create') }}">Import your herd</a> or sync a reader.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $animals->links() }}</div>
@endsection
