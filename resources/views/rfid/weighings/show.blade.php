@extends('layouts.rfid')
@section('title', \Carbon\Carbon::parse($session->date)->translatedFormat('j F Y'))
@section('eyebrow')Weegsessie @endsection
@section('actions')
    @if ($older)<a href="{{ route('rfid.weighings.show', $older) }}" class="btn-secondary">← Vorige</a>@endif
    @if ($newer)<a href="{{ route('rfid.weighings.show', $newer) }}" class="btn-secondary">Volgende →</a>@endif
    @if ($session->prev_date)<a href="{{ route('rfid.compare', ['mode' => 'sessions', 'a' => $session->prev_date, 'b' => $session->date]) }}" class="btn-secondary">Vergelyk met vorige</a>@endif
    <a href="{{ route('rfid.weighings.export', $session->date) }}" class="btn-primary">CSV</a>
@endsection

@section('content')
<div class="panel grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-hairline overflow-hidden">
    @foreach ([
        ['Geweeg', $session->kg['n'], 'diere'],
        ['Gemiddeld', $session->kg['mean'], 'kg'],
        ['Spreiding', $session->kg['min'].'–'.$session->kg['max'], 'kg'],
        ['Gem. groei', $session->adg['n'] ? (($session->adg['mean'] > 0 ? '+' : '').round($session->adg['mean'])) : '—', 'g/dag'],
        ['Verloor gewig', $session->lost, 'diere'],
    ] as [$l, $v, $u])
        <div class="p-6"><div class="kpi-label">{{ $l }}</div><div class="mt-3 font-headline text-4xl num">{{ $v }}<span class="text-base text-stone font-ui ml-1">{{ $u }}</span></div></div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Gewigverspreiding</div><span class="text-xs text-stone">SD {{ $session->kg['sd'] }} · CV {{ $session->kg['cv'] }}%</span></div>
        <div class="p-6 pt-10"><x-chart.histogram :bins="$histogram" /></div>
    </div>
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Groeiverspreiding</div><span class="text-xs text-stone">{{ $session->reweighed }} herweeg</span></div>
        <div class="p-6 pt-10"><x-chart.histogram :bins="$adgHistogram" unit="g/d" /></div>
    </div>
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Volgens geslag</div></div>
        <table class="tbl">
            <thead><tr><th>Groep</th><th class="text-right">n</th><th class="text-right">Gem.</th><th class="text-right">Min–Maks</th></tr></thead>
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
        <div class="panel-title">Diere</div>
        <div class="flex gap-1 text-sm">
            @foreach (['kg' => 'Swaarste', 'adg' => 'Beste groei', 'change' => 'Verloor eerste', 'id' => 'ID'] as $k => $l)
                <a href="?sort={{ $k }}" class="rounded-full px-3 h-8 inline-flex items-center {{ request('sort', 'kg') === $k ? 'bg-char text-sand' : 'text-stone hover:text-char' }}">{{ $l }}</a>
            @endforeach
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>#</th><th>Dier</th><th>Geslag</th><th class="text-right">Gewig</th><th class="text-right">Vorige</th><th class="text-right">Dae</th><th class="text-right">Verandering</th><th class="text-right">Groei</th><th class="w-40">vs sessie gem.</th></tr></thead>
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
                    <td class="text-right num {{ ($r->adg ?? 0) < 0 ? 'down' : '' }}">{{ $r->adg !== null ? (($r->adg > 0 ? '+' : '').$r->adg.' g/d') : '—' }}</td>
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
@endsection
