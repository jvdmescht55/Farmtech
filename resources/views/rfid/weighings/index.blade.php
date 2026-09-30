@extends('layouts.rfid')
@section('title', 'Weegsessies')
@section('eyebrow')Elke weegdag, opgesom @endsection
@section('actions')<a href="{{ route('rfid.readers.index') }}" class="btn-primary">Laai sessie op</a>@endsection

@section('content')
<div class="panel">
    <div class="panel-head"><div class="panel-title">Gemiddelde gewig oor tyd</div></div>
    <div class="p-6"><x-chart.line :points="$trend" unit="kg" :height="240" /></div>
</div>

<div class="panel mt-6 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr>
                <th>Datum</th><th class="text-right">Geweeg</th><th class="text-right">Gemiddeld</th><th class="text-right">Mediaan</th><th class="text-right">Min – Maks</th><th class="text-right">SD</th><th class="text-right">Gem. groei</th><th class="text-right">Δ vs vorige</th><th class="text-right">Verloor</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($sessions as $s)
                <tr>
                    <td class="whitespace-nowrap"><a href="{{ route('rfid.weighings.show', $s->date) }}" class="font-medium link-u">{{ \Carbon\Carbon::parse($s->date)->translatedFormat('D j M Y') }}</a></td>
                    <td class="text-right num">{{ $s->kg['n'] }}</td>
                    <td class="text-right num font-medium">{{ $s->kg['mean'] }} kg</td>
                    <td class="text-right num text-stone">{{ $s->kg['median'] }}</td>
                    <td class="text-right num text-stone whitespace-nowrap">{{ $s->kg['min'] }} – {{ $s->kg['max'] }}</td>
                    <td class="text-right num text-stone">{{ $s->kg['sd'] }}</td>
                    <td class="text-right num {{ ($s->adg['mean'] ?? 0) < 0 ? 'down' : 'up' }}">{{ $s->adg['n'] ? (($s->adg['mean'] > 0 ? '+' : '').round($s->adg['mean']).' g/d') : '—' }}</td>
                    <td class="text-right num {{ ($s->mean_change ?? 0) < 0 ? 'down' : 'up' }}">{{ $s->mean_change !== null ? (($s->mean_change > 0 ? '+' : '').$s->mean_change.' kg') : '—' }}</td>
                    <td class="text-right num {{ $s->lost ? 'down' : 'text-stone-light' }}">{{ $s->lost }}</td>
                    <td class="text-right"><a href="{{ route('rfid.weighings.show', $s->date) }}" class="text-sm text-stone hover:text-char">Oop →</a></td>
                </tr>
            @empty
                <tr><td colspan="10" class="py-16 text-center text-stone">Nog geen wegings nie. Laai 'n sessielêer op met gewigte, of teken 'n gewig op 'n dier se bladsy aan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
