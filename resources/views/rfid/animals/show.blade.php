@extends('layouts.rfid')
@section('title', $animal->visual_id)
@section('eyebrow'){{ $animal->bio('label') }} · {{ $animal->sexLabel() }} · {{ $animal->breed ?? 'Ras onbekend' }} @endsection
@section('actions')
    <a href="{{ route('rfid.events.index', ['animal' => $animal->id]) }}" class="btn-secondary">+ Logboek</a>
    <a href="{{ route('rfid.animals.edit', $animal) }}" class="btn-primary">Wysig</a>
@endsection
@php
    $sevCls = ['critical' => 'bg-[#B0452F]/8 border-[#B0452F]/25', 'warning' => 'bg-ochre/8 border-ochre/25', 'info' => 'bg-white border-hairline'];
    $sevDot = ['critical' => 'bg-[#B0452F]', 'warning' => 'bg-ochre', 'info' => 'bg-stone-light'];
@endphp

@section('content')
@if ($alerts->isNotEmpty())
    <div class="grid md:grid-cols-2 gap-3 mb-6">
        @foreach ($alerts as $a)
            <div class="rounded-2xl border {{ $sevCls[$a['severity']] }} p-5 flex gap-4">
                <span class="mt-1.5 w-2.5 h-2.5 shrink-0 rounded-full {{ $sevDot[$a['severity']] }}"></span>
                <div><div class="font-medium">{{ $a['title'] }}</div><p class="text-sm text-stone mt-0.5">{{ $a['detail'] }}</p>@if ($a['action'])<p class="text-sm mt-1.5">{{ $a['action'] }}</p>@endif</div>
            </div>
        @endforeach
    </div>
@endif

<div class="panel grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-hairline overflow-hidden">
    @foreach ([
        ['EID', $animal->eid ?? '—', true],
        ['Gebore', $animal->birth_date?->format('d/m/Y') ?? '—', true],
        ['Ouderdom', $animal->ageLabel() ?? '—', false],
        ['Geboortetipe', $animal->birth_type ? (config('herd.birth_types')[$animal->birth_type] ?? $animal->birth_type) : '—', false],
        ['Status', \App\Models\Animal::STATUSES[$animal->status].($animal->status_date ? ' · '.$animal->status_date->format('d/m/y') : ''), false],
        ['Laas gesien', $animal->last_seen_at?->diffForHumans() ?? 'Nooit', false],
    ] as [$l, $v, $mono])
        <div class="p-5"><div class="kpi-label">{{ $l }}</div><div class="mt-2 {{ $mono ? 'font-num text-sm' : '' }}">{{ $v }}</div></div>
    @endforeach
</div>

<div class="grid xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="panel overflow-hidden">
            <div class="panel-head">
                <div class="panel-title">Groeikurwe</div>
                <form method="GET" class="flex items-center gap-2 text-sm">
                    <label for="t" class="text-stone">Teiken</label>
                    <input id="t" name="target" type="number" step="0.5" value="{{ $target }}" placeholder="kg" class="field !h-9 w-24 font-num">
                    <button class="btn-line btn-sm">Projekteer</button>
                </form>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-hairline border-b border-hairline">
                <div class="p-5"><div class="kpi-label">Huidig</div><div class="mt-2 font-headline text-4xl num">{{ $weights->last()?->weight_kg ?? '—' }}<span class="text-sm text-stone font-ui ml-1">kg</span></div></div>
                <div class="p-5"><div class="kpi-label">Onlangse groei</div><div class="mt-2 font-headline text-4xl num {{ ($recentAdg ?? 0) < 0 ? 'down' : '' }}">{{ $recentAdg !== null ? ($recentAdg > 0 ? '+' : '').$recentAdg : '—' }}<span class="text-sm text-stone font-ui ml-1">g/d</span></div></div>
                <div class="p-5"><div class="kpi-label">Lewenslank</div><div class="mt-2 font-headline text-4xl num">{{ $lifeAdg ?? '—' }}<span class="text-sm text-stone font-ui ml-1">g/d</span></div></div>
                <div class="p-5"><div class="kpi-label">100-dag</div><div class="mt-2 font-headline text-4xl num">{{ $kg100 ?? '—' }}<span class="text-sm text-stone font-ui ml-1">kg</span></div></div>
            </div>
            <div class="p-6"><x-chart.line :points="$weightPoints" unit="kg" :height="240" :target="$target" /></div>
            @if ($target)
                <div class="px-6 pb-6 -mt-2 text-sm">
                    @if ($daysToTarget === 0)<span class="chip bg-ochre/15 text-ochre-dark">Reg vir die mark — al oor {{ $target }} kg</span>
                    @elseif ($daysToTarget === null)<span class="chip bg-[#B0452F]/10 text-[#B0452F]">Groei staan stil — kan nie projekteer nie</span>
                    @else<span class="chip bg-sand-deep text-char">Bereik {{ $target }} kg oor ongeveer {{ $daysToTarget }} dae · {{ now()->addDays($daysToTarget)->format('j M Y') }}</span>@endif
                </div>
            @endif
            <details class="border-t border-hairline">
                <summary class="px-6 py-4 cursor-pointer text-sm font-medium">Alle wegings ({{ $weights->count() }}) &amp; nuwe weging</summary>
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead><tr><th>Datum</th><th>Tipe</th><th class="text-right">Gewig</th><th class="text-right">Groei</th><th>Bron</th></tr></thead>
                        <tbody>
                        @foreach ($weights->reverse() as $w)
                            <tr><td class="num">{{ $w->scanned_at->format('d/m/Y') }}</td><td class="text-stone">{{ \App\Models\Scan::WEIGH_TYPES[$w->weigh_type] ?? '—' }}</td><td class="text-right num">{{ $w->weight_kg }} kg</td><td class="text-right num {{ ($gains[$w->id] ?? 0) < 0 ? 'down' : '' }}">{{ $gains[$w->id] !== null ? (($gains[$w->id] > 0 ? '+' : '').$gains[$w->id].' g/d') : '—' }}</td><td class="text-xs text-stone">{{ $w->reader_sync_id ? 'Skandeerder' : 'Met die hand' }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('rfid.animals.weights.store', $animal) }}" class="px-6 py-5 bg-sand-light flex flex-wrap items-end gap-3">
                    @csrf
                    <div><label class="field-label">Gewig (kg)</label><input name="weight_kg" type="number" step="0.1" required class="field w-28 font-num"></div>
                    <div><label class="field-label">Tipe</label><select name="weigh_type" class="field">@foreach (\App\Models\Scan::WEIGH_TYPES as $k => $v)<option value="{{ $k }}" @selected($k === 'routine')>{{ $v }}</option>@endforeach</select></div>
                    <div><label class="field-label">Datum</label><input name="scanned_at" type="date" value="{{ now()->toDateString() }}" required class="field"></div>
                    <button class="btn-dark">Stoor</button>
                </form>
            </details>
        </div>

        <div class="panel">
            <div class="panel-head"><div class="panel-title">Stamboom</div><x-tier :tier="$tier->tier" /></div>
            <div class="p-6 overflow-x-auto">
                <div class="grid grid-cols-3 gap-3 min-w-[640px] items-center">
                    @php
                        $s = $animal->sire; $d = $animal->dam;
                    @endphp
                    <div class="row-span-4 flex flex-col justify-center">@include('rfid.animals._node', ['a' => $animal, 'role' => 'Dier', 'class' => '!border-char'])</div>
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $s, 'role' => 'Vaar'])</div>
                    @include('rfid.animals._node', ['a' => $s?->sire, 'role' => 'Vaar se vaar'])
                    @include('rfid.animals._node', ['a' => $s?->dam, 'role' => 'Vaar se moer'])
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $d, 'role' => 'Moer'])</div>
                    @include('rfid.animals._node', ['a' => $d?->sire, 'role' => 'Moer se vaar'])
                    @include('rfid.animals._node', ['a' => $d?->dam, 'role' => 'Moer se moer'])
                </div>
                <div class="mt-5 rounded-xl bg-sand-light px-5 py-4 text-sm">
                    <div class="font-medium">Hoekom {{ $tier->tier ?? 'onbekend' }}?</div>
                    <ol class="mt-1 list-decimal list-inside text-stone space-y-0.5">@foreach ($tier->reasons as $r)<li>{{ $r }}</li>@endforeach</ol>
                    @if ($tier->official && $computed->tier && $computed->tier !== $tier->tier)<p class="mt-2 text-ochre-dark">Die stamboom alleen gee <strong>{{ $computed->tier }}</strong> — kontroleer die aangetekende vlak.</p>@endif
                </div>
            </div>
        </div>

        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Logboek</div><a href="{{ route('rfid.events.index', ['animal' => $animal->id]) }}" class="text-sm link-u">+ Nuwe inskrywing</a></div>
            <ul class="divide-y divide-hairline">
                @forelse ($events as $e)
                    <li class="px-6 py-3.5 grid grid-cols-[6rem_1fr] gap-4 text-sm">
                        <span class="num text-stone">{{ $e->date->format('d/m/Y') }}</span>
                        <div><span class="font-medium">{{ $e->label() }}</span> <span class="text-stone">{{ collect([$e->product, $e->dose, $e->mate ? 'met '.$e->mate->visual_id : null, $e->count !== null ? $e->count.' gebore' : null, $e->result ? (config("herd.event_types.pregnancy_scan.result.{$e->result}") ?? $e->result) : null, $e->withdrawal_until ? 'onttrekking tot '.$e->withdrawal_until->format('d/m/Y') : null, $e->notes])->filter()->implode(' · ') }}</span></div>
                    </li>
                @empty
                    <li class="px-6 py-8 text-sm text-stone">Nog niks aangeteken nie.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="space-y-6">
        @if ($animal->notes)<div class="panel p-6 text-sm"><div class="kpi-label mb-2">Opmerking</div>{{ $animal->notes }}</div>@endif
        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Teelwaardes (EBV)</div></div>
            <table class="tbl">
                <thead><tr><th>Eienskap</th><th class="text-right">EBV</th><th class="text-right">Akk.</th></tr></thead>
                <tbody>
                @foreach (config('herd.ebvs') as $k => $e)
                    @php($v = $animal->ebv($k))
                    <tr><td>{{ $e['af'] }}</td><td class="text-right num">{{ $v['v'] ?? '—' }}</td><td class="text-right num text-stone">{{ $v['acc'] ?? '' }}{{ isset($v['acc']) ? '%' : '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($animal->sex !== 'M')
            <div class="panel overflow-hidden">
                <div class="panel-head"><div class="panel-title">Lamrekord</div></div>
                <div class="grid grid-cols-4 gap-px bg-hairline">
                    @foreach (config('herd.dam_record') as $k => $label)
                        <div class="bg-white px-4 py-3"><div class="text-[10px] uppercase tracking-wider text-stone-light">{{ $label }}</div><div class="num mt-0.5">{{ $animal->dam_record[$k] ?? '—' }}</div></div>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Nageslag</div><span class="text-xs text-stone">{{ $offspring->count() }}</span></div>
            <ul class="divide-y divide-hairline max-h-96 overflow-y-auto">
                @forelse ($offspring->take(40) as $o)
                    <li class="px-6 py-2.5 flex items-center gap-3 text-sm">
                        @if ($o->in_herd)<a href="{{ route('rfid.animals.show', $o) }}" class="font-num link-u">{{ $o->visual_id }}</a>@else<span class="font-num">{{ $o->visual_id }}</span>@endif
                        <span class="text-xs text-stone">{{ $o->sexLabel() }} · {{ $o->birth_date?->format('Y') }}</span>
                        <x-tier :tier="$o->tierResult()->tier" class="ml-auto" />
                    </li>
                @empty
                    <li class="px-6 py-6 text-sm text-stone">Geen aangeteken nie.</li>
                @endforelse
            </ul>
        </div>
        @if ($lots->isNotEmpty())
            <div class="panel p-6 text-sm"><div class="kpi-label mb-2">In katalogusse</div>@foreach ($lots as $l)<a href="{{ route('rfid.catalogues.show', $l->catalogue) }}" class="block link-u">{{ $l->catalogue->title }} · lot {{ $l->lot_number ?: '—' }}</a>@endforeach</div>
        @endif
    </div>
</div>
@endsection
