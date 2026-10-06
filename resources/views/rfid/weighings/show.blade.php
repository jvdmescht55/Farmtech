@extends('layouts.rfid')
@section('title', \Carbon\Carbon::parse($session->date)->translatedFormat('j F Y'))
@section('eyebrow')Weigh day @endsection
@section('actions')
    @if ($older)<a href="{{ route('rfid.weighings.show', $older) }}" class="btn-secondary">← Earlier</a>@endif
    @if ($newer)<a href="{{ route('rfid.weighings.show', $newer) }}" class="btn-secondary">Later →</a>@endif
    @if ($session->prev_date)<a href="{{ route('rfid.compare', ['mode' => 'sessions', 'a' => $session->prev_date, 'b' => $session->date]) }}" class="btn-secondary">Compare with last time</a>@endif
    <a href="{{ route('rfid.weighings.export', $session->date) }}" class="btn-primary">CSV</a>
@endsection

@section('content')
<div class="panel grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-hairline overflow-hidden">
    @foreach ([
        ['Weighed', $session->kg['n'], 'head'],
        ['Average', $session->kg['mean'], 'kg'],
        ['Range', $session->kg['min'].'–'.$session->kg['max'], 'kg'],
        ['Daily gain', $session->adg['n'] ? (($session->adg['mean'] > 0 ? '+' : '').round($session->adg['mean'])) : '—', 'g'],
        ['Lost weight', $session->lost, 'head'],
    ] as [$l, $v, $u])
        <div class="p-6"><div class="kpi-label">{{ $l }}</div><div class="mt-3 font-headline text-4xl num">{{ $v }}<span class="text-base text-stone font-ui ml-1">{{ $u }}</span></div></div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Weight spread</div><span class="text-xs text-stone">SD {{ $session->kg['sd'] }} · CV {{ $session->kg['cv'] }}%</span></div>
        <div class="p-6 pt-10"><x-chart.histogram :bins="$histogram" /></div>
    </div>
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Gain spread</div><span class="text-xs text-stone">{{ $session->reweighed }} weighed before</span></div>
        <div class="p-6 pt-10"><x-chart.histogram :bins="$adgHistogram" unit="g/d" /></div>
    </div>
    <div class="panel">
        <div class="panel-head"><div class="panel-title">By sex</div></div>
        <table class="tbl">
            <thead><tr><th></th><th class="text-right">Head</th><th class="text-right">Average</th><th class="text-right">Range</th></tr></thead>
            <tbody>
            @foreach ($bySex as $k => $s)
                <tr><td>{{ $k }}</td><td class="text-right num">{{ $s['n'] }}</td><td class="text-right num">{{ $s['mean'] }} kg</td><td class="text-right num text-stone">{{ $s['min'] }}–{{ $s['max'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="panel mt-6 overflow-hidden">
    <div class="panel-head">
        <div class="panel-title">Animals</div>
        <div class="flex gap-1 text-sm">
            @foreach (['kg' => 'Heaviest', 'adg' => 'Best gain', 'change' => 'Losers first', 'id' => 'ID'] as $k => $l)
                <a href="?sort={{ $k }}" class="rounded-full px-3 h-8 inline-flex items-center {{ request('sort', 'kg') === $k ? 'bg-char text-sand' : 'text-stone hover:text-char' }}">{{ $l }}</a>
            @endforeach
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>#</th><th>Animal</th><th>Sex</th><th class="text-right">Weight</th><th class="text-right">Last time</th><th class="text-right">Days</th><th class="text-right">Change</th><th class="text-right">Gain</th><th class="w-40">vs average</th></tr></thead>
            <tbody>
            @php
                $mean = $session->kg['mean'];
                $spread = max(1, $session->kg['max'] - $session->kg['min']);
            @endphp
            @foreach ($rows as $i => $r)
                <tr>
                    <td class="num text-stone-light">{{ $i + 1 }}</td>
                    <td class="whitespace-nowrap"><a href="{{ route('rfid.animals.show', $r->animal) }}" class="font-num font-medium link-u">{{ $r->animal->visual_id }}</a></td>
                    <td class="text-stone">{{ $r->animal->sexLabel() }}</td>
                    <td class="text-right num font-medium">{{ $r->kg }} kg</td>
                    <td class="text-right num text-stone">{{ $r->prev_kg ?? '—' }}</td>
                    <td class="text-right num text-stone">{{ $r->days ?? '—' }}</td>
                    <td class="text-right num {{ ($r->change ?? 0) < 0 ? 'down' : 'up' }}">{{ $r->change !== null ? (($r->change > 0 ? '+' : '').$r->change.' kg') : '—' }}</td>
                    <td class="text-right num {{ ($r->adg ?? 0) < 0 ? 'down' : '' }}">@if ($r->adg !== null && abs($r->adg) > 700 && ($r->animal->species ?? 'sheep') !== 'cattle')<span class="text-ochre-dark" title="Probably a mis-typed or mis-read weight">check weight</span>@else{{ $r->adg !== null ? (($r->adg > 0 ? '+' : '').$r->adg.' g/day') : '—' }}@endif</td>
                    <td>
                        @php($d = $r->kg - $mean)
                        <div class="relative h-1.5 rounded-full bg-sand-deep">
                            <div class="absolute top-0 h-full w-px bg-stone-light left-1/2"></div>
                            <div class="absolute top-0 h-full rounded-full {{ $d >= 0 ? 'bg-char left-1/2' : 'bg-ochre right-1/2' }}" style="width: {{ min(50, abs($d) / $spread * 100) }}%"></div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="{{ route('rfid.weighings.destroy', $session->date) }}" class="mt-10 panel p-5 flex flex-wrap items-center justify-between gap-3" x-data="{ sure: false }" @submit="if (!sure) { $event.preventDefault(); sure = true }">
    @csrf @method('DELETE')
    <div class="text-sm"><span class="font-medium">Was this a test, or the wrong day?</span> <span class="text-stone">Delete every weight recorded on {{ \Carbon\Carbon::parse($session->date)->format('j M Y') }}. The animals stay.</span></div>
    <button class="btn btn-sm rounded-full px-5 bg-[#B0452F] text-white" x-text="sure ? 'Tap again to delete this weigh day' : 'Delete this weigh day'"></button>
</form>
@endsection
