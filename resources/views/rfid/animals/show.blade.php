@extends('layouts.rfid')
@section('title', $animal->visual_id)
@section('eyebrow'){{ $animal->bio('label') }} · {{ $animal->sexLabel() }}{{ $animal->breed ? ' · '.$animal->breed : '' }}{{ $animal->status !== 'active' ? ' · '.\App\Models\Animal::STATUSES[$animal->status] : '' }} @endsection
@section('actions')
    <a href="{{ route('rfid.events.index', ['animal' => $animal->id]) }}" class="btn-secondary">+ Record</a>
    <a href="{{ route('rfid.animals.edit', $animal) }}" class="btn-primary">Edit</a>
@endsection
@php
    $sevCls = ['critical' => 'bg-[#B0452F]/8 border-[#B0452F]/25', 'warning' => 'bg-ochre/8 border-ochre/25', 'info' => 'bg-white border-hairline'];
    $sevDot = ['critical' => 'bg-[#B0452F]', 'warning' => 'bg-ochre', 'info' => 'bg-stone-light'];
@endphp

@section('content')
@foreach ($alerts as $a)
    <div class="rounded-2xl border {{ $sevCls[$a['severity']] }} p-5 mb-3 flex gap-4">
        <span class="mt-1.5 w-2.5 h-2.5 shrink-0 rounded-full {{ $sevDot[$a['severity']] }}"></span>
        <div><span class="font-medium">{{ $a['title'] }}</span> <span class="text-stone">— {{ $a['detail'] }}</span>@if ($a['action'])<div class="text-sm mt-1">{{ $a['action'] }}</div>@endif</div>
    </div>
@endforeach

<div class="panel grid grid-cols-2 md:grid-cols-5 divide-x divide-y md:divide-y-0 divide-hairline overflow-hidden {{ $alerts->isNotEmpty() ? 'mt-6' : '' }}">
    <div class="p-5"><div class="kpi-label">Weight now</div><div class="mt-2 font-headline text-4xl num">{{ $weights->last()?->weight_kg ?? '—' }}<span class="text-sm text-stone font-ui ml-1">kg</span></div></div>
    <div class="p-5"><div class="kpi-label">Daily gain</div><div class="mt-2 font-headline text-4xl num {{ ($recentAdg ?? 0) < 0 ? 'down' : '' }}">{{ $recentAdg !== null ? ($recentAdg > 0 ? '+' : '').$recentAdg : '—' }}<span class="text-sm text-stone font-ui ml-1">g</span></div></div>
    <div class="p-5"><div class="kpi-label">Age</div><div class="mt-2 font-headline text-4xl">{{ $animal->ageLabel() ?? '—' }}</div></div>
    <div class="p-5"><div class="kpi-label">Tag (EID)</div><div class="mt-3 font-num text-sm">{{ $animal->eid ?? '—' }}</div></div>
    <div class="p-5 col-span-2 md:col-span-1"><div class="kpi-label">{{ $water ? 'Last drink' : 'Last seen' }}</div><div class="mt-3 text-sm">{{ $water ? $water->last_at->diffForHumans().' · '.$water->last_point : ($animal->last_seen_at?->diffForHumans() ?? 'Never') }}</div></div>
</div>

<div class="grid xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="panel overflow-hidden">
            <div class="panel-head">
                <div class="panel-title">Growth</div>
                <form method="GET" class="flex items-center gap-2 text-sm">
                    <input name="target" type="number" step="0.5" value="{{ $target }}" placeholder="Target kg" class="field !h-9 w-28 font-num">
                    <button class="btn-line btn-sm">When?</button>
                </form>
            </div>
            <div class="p-6"><x-chart.line :points="$weightPoints" unit="kg" :height="240" :target="$target" /></div>
            @if ($target)
                <div class="px-6 pb-6 -mt-2 text-sm">
                    @if ($daysToTarget === 0)<span class="chip bg-ochre/15 text-ochre-dark">Ready — already over {{ $target }} kg</span>
                    @elseif ($daysToTarget === null)<span class="chip bg-[#B0452F]/10 text-[#B0452F]">Not growing right now — can't say when</span>
                    @else<span class="chip bg-sand-deep text-char">Hits {{ $target }} kg in about {{ $daysToTarget }} days · {{ now()->addDays($daysToTarget)->format('j M Y') }}</span>@endif
                </div>
            @endif
            <div class="grid grid-cols-2 divide-x divide-hairline border-t border-hairline text-sm">
                <div class="px-6 py-4"><span class="text-stone">Lifetime gain</span> <span class="num ml-2">{{ $lifeAdg !== null ? $lifeAdg.' g/day' : '—' }}</span></div>
                <div class="px-6 py-4"><span class="text-stone">Weight at 100 days</span> <span class="num ml-2">{{ $kg100 !== null ? $kg100.' kg' : '—' }}</span></div>
            </div>
            <details class="border-t border-hairline">
                <summary class="px-6 py-4 cursor-pointer text-sm font-medium">All weighings ({{ $weights->count() }}) · add one</summary>
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead><tr><th>Date</th><th>Type</th><th class="text-right">Weight</th><th class="text-right">Gain</th><th>From</th></tr></thead>
                        <tbody>
                        @foreach ($weights->reverse() as $w)
                            <tr><td class="num">{{ $w->scanned_at->format('d/m/Y') }}</td><td class="text-stone">{{ \App\Models\Scan::WEIGH_TYPES[$w->weigh_type] ?? '—' }}</td><td class="text-right num">{{ $w->weight_kg }} kg</td><td class="text-right num {{ ($gains[$w->id] ?? 0) < 0 ? 'down' : '' }}">{{ $gains[$w->id] !== null ? (($gains[$w->id] > 0 ? '+' : '').$gains[$w->id].' g/day') : '—' }}</td><td class="text-xs text-stone">{{ $w->reader_sync_id ? 'KraalTrac' : 'By hand' }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('rfid.animals.weights.store', $animal) }}" class="px-6 py-5 bg-sand-light flex flex-wrap items-end gap-3">
                    @csrf
                    <div><label class="field-label">Weight (kg)</label><input name="weight_kg" type="number" step="0.1" required class="field w-28 font-num"></div>
                    <div><label class="field-label">Type</label><select name="weigh_type" class="field">@foreach (\App\Models\Scan::WEIGH_TYPES as $k => $v)<option value="{{ $k }}" @selected($k === 'routine')>{{ $v }}</option>@endforeach</select></div>
                    <div><label class="field-label">Date</label><input name="scanned_at" type="date" value="{{ now()->toDateString() }}" required class="field"></div>
                    <button class="btn-dark">Save</button>
                </form>
            </details>
        </div>

        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Records</div><a href="{{ route('rfid.events.index', ['animal' => $animal->id]) }}" class="text-sm link-u">+ Add</a></div>
            <ul class="divide-y divide-hairline">
                @forelse ($events as $e)
                    <li class="px-6 py-3.5 grid grid-cols-[6rem_1fr] gap-4 text-sm">
                        <span class="num text-stone">{{ $e->date->format('d/m/Y') }}</span>
                        <div><span class="font-medium">{{ $e->label() }}</span> <span class="text-stone">{{ collect([$e->product, $e->dose, $e->mate ? 'with '.$e->mate->visual_id : null, $e->count !== null ? $e->count.' born' : null, $e->result ? (config("herd.event_types.pregnancy_scan.result.{$e->result}") ?? $e->result) : null, $e->withdrawal_until ? 'withdrawal until '.$e->withdrawal_until->format('d/m/Y') : null, $e->notes])->filter()->implode(' · ') }}</span></div>
                    </li>
                @empty
                    <li class="px-6 py-8 text-sm text-stone">No treatments, matings or births recorded yet.</li>
                @endforelse
            </ul>
        </div>

        <details class="panel overflow-hidden" {{ $animal->sire_id || $animal->dam_id ? 'open' : '' }}>
            <summary class="panel-head cursor-pointer"><span class="panel-title">Pedigree</span><x-tier :tier="$tier->tier" /></summary>
            <div class="p-6 overflow-x-auto">
                <div class="grid grid-cols-3 gap-3 min-w-[640px] items-center">
                    @php
                        $s = $animal->sire; $d = $animal->dam;
                    @endphp
                    <div class="row-span-4 flex flex-col justify-center">@include('rfid.animals._node', ['a' => $animal, 'role' => 'Animal', 'class' => '!border-char'])</div>
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $s, 'role' => 'Sire'])</div>
                    @include('rfid.animals._node', ['a' => $s?->sire, 'role' => "Sire's sire"])
                    @include('rfid.animals._node', ['a' => $s?->dam, 'role' => "Sire's dam"])
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $d, 'role' => 'Dam'])</div>
                    @include('rfid.animals._node', ['a' => $d?->sire, 'role' => "Dam's sire"])
                    @include('rfid.animals._node', ['a' => $d?->dam, 'role' => "Dam's dam"])
                </div>
                <div class="mt-5 rounded-xl bg-sand-light px-5 py-4 text-sm">
                    <div class="font-medium">Why {{ $tier->tier ?? 'unknown' }}?</div>
                    <ol class="mt-1 list-decimal list-inside text-stone space-y-0.5">@foreach ($tier->reasons as $r)<li>{{ $r }}</li>@endforeach</ol>
                    @if ($tier->official && $computed->tier && $computed->tier !== $tier->tier)<p class="mt-2 text-ochre-dark">The pedigree on its own gives <strong>{{ $computed->tier }}</strong> — double-check the recorded tier.</p>@endif
                </div>
            </div>
        </details>
    </div>

    <div class="space-y-6">
        @if ($animal->notes)<div class="panel p-6 text-sm"><div class="kpi-label mb-2">Note</div>{{ $animal->notes }}</div>@endif
        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Breeding values (EBV)</div></div>
            <table class="tbl">
                <tbody>
                @foreach (config('herd.ebvs') as $k => $e)
                    @php($v = $animal->ebv($k))
                    <tr><td>{{ $e['label'] }}</td><td class="text-right num">{{ $v['v'] ?? '—' }}</td><td class="text-right num text-stone w-16">{{ isset($v['acc']) ? $v['acc'].'%' : '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($animal->sex !== 'M' && $animal->dam_record)
            <div class="panel overflow-hidden">
                <div class="panel-head"><div class="panel-title">Lambing record</div></div>
                <div class="grid grid-cols-4 gap-px bg-hairline">
                    @foreach (config('herd.dam_record') as $k => $label)
                        <div class="bg-white px-4 py-3"><div class="text-[10px] uppercase tracking-wider text-stone-light">{{ $label }}</div><div class="num mt-0.5">{{ $animal->dam_record[$k] ?? '—' }}</div></div>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Offspring</div><span class="text-xs text-stone">{{ $offspring->count() }}</span></div>
            <ul class="divide-y divide-hairline max-h-96 overflow-y-auto">
                @forelse ($offspring->take(40) as $o)
                    <li class="px-6 py-2.5 flex items-center gap-3 text-sm">
                        @if ($o->in_herd)<a href="{{ route('rfid.animals.show', $o) }}" class="font-num link-u">{{ $o->visual_id }}</a>@else<span class="font-num">{{ $o->visual_id }}</span>@endif
                        <span class="text-xs text-stone">{{ $o->sexLabel() }} · {{ $o->birth_date?->format('Y') }}</span>
                        <x-tier :tier="$o->tierResult()->tier" class="ml-auto" />
                    </li>
                @empty
                    <li class="px-6 py-6 text-sm text-stone">None recorded.</li>
                @endforelse
            </ul>
        </div>
        @if ($lots->isNotEmpty())
            <div class="panel p-6 text-sm"><div class="kpi-label mb-2">In auction books</div>@foreach ($lots as $l)<a href="{{ route('rfid.catalogues.show', $l->catalogue) }}" class="block link-u">{{ $l->catalogue->title }} · lot {{ $l->lot_number ?: '—' }}</a>@endforeach</div>
        @endif
    </div>
</div>
@endsection
