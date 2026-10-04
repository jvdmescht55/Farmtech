@extends('layouts.rfid')
@section('title', 'Weighing')
@section('eyebrow')Every weigh day, summed up @endsection
@section('actions')<a href="{{ route('rfid.readers.index') }}" class="btn-primary">Upload a session</a>@endsection

@section('content')
<div class="panel">
    <div class="panel-head"><div class="panel-title">Average weight over time</div></div>
    <div class="p-6"><x-chart.line :points="$trend" unit="kg" :height="240" /></div>
</div>

<div class="panel mt-6 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr>
                <th>Date</th><th class="text-right">Weighed</th><th class="text-right">Average</th><th class="text-right">Range</th><th class="text-right">Daily gain</th><th class="text-right">vs last time</th><th class="text-right">Lost weight</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($sessions as $s)
                <tr>
                    <td class="whitespace-nowrap"><a href="{{ route('rfid.weighings.show', $s->date) }}" class="font-medium link-u">{{ \Carbon\Carbon::parse($s->date)->translatedFormat('D j M Y') }}</a></td>
                    <td class="text-right num">{{ $s->kg['n'] }}</td>
                    <td class="text-right num font-medium">{{ $s->kg['mean'] }} kg</td>
                                        <td class="text-right num text-stone whitespace-nowrap">{{ $s->kg['min'] }} – {{ $s->kg['max'] }}</td>
                                        <td class="text-right num {{ ($s->adg['mean'] ?? 0) < 0 ? 'down' : 'up' }}">{{ $s->adg['n'] ? (($s->adg['mean'] > 0 ? '+' : '').round($s->adg['mean']).' g') : '—' }}</td>
                    <td class="text-right num {{ ($s->mean_change ?? 0) < 0 ? 'down' : 'up' }}">{{ $s->mean_change !== null ? (($s->mean_change > 0 ? '+' : '').$s->mean_change.' kg') : '—' }}</td>
                    <td class="text-right num {{ $s->lost ? 'down' : 'text-stone-light' }}">{{ $s->lost }}</td>
                    <td class="text-right"><a href="{{ route('rfid.weighings.show', $s->date) }}" class="text-sm text-stone hover:text-char">Open →</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-16 text-center text-stone"><div class="font-headline text-3xl text-char">No weigh days yet.</div><p class="mt-2">Weigh with your KraalTrac and they land here by themselves.</p><a href="{{ route('rfid.data.scale') }}" class="btn-dark btn-sm mt-5">Sync the scale</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
