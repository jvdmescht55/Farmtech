@extends('layouts.rfid')
@section('title', 'Compare')
@section('eyebrow')Who's really pulling ahead? @endsection

@section('content')
<div class="inline-flex rounded-full bg-white border border-hairline p-1 mb-8">
    <a href="{{ route('rfid.compare') }}" class="rounded-full px-5 h-9 inline-flex items-center text-sm {{ $mode !== 'sessions' ? 'bg-char text-sand' : 'text-stone hover:text-char' }}">Groups</a>
    <a href="{{ route('rfid.compare', ['mode' => 'sessions']) }}" class="rounded-full px-5 h-9 inline-flex items-center text-sm {{ $mode === 'sessions' ? 'bg-char text-sand' : 'text-stone hover:text-char' }}">Weigh day vs weigh day</a>
</div>

@if ($mode !== 'sessions')
    <form method="GET" class="panel p-5 flex flex-wrap items-end gap-4">
        <div><label class="field-label">Compare by</label>
            <select name="by" class="field w-52" onchange="this.form.submit()">@foreach (\App\Services\Herd\WeighStats::DIMENSIONS as $k => $l)<option value="{{ $k }}" @selected($by === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="field-label">Look at</label>
            <select name="metric" class="field w-52" onchange="this.form.submit()">@foreach (\App\Services\Herd\WeighStats::METRICS as $k => [$l, $u])<option value="{{ $k }}" @selected($metric === $k)>{{ $l }} ({{ $u }})</option>@endforeach</select></div>
        <div><label class="field-label">Animals</label>
            <select name="sex" class="field w-36" onchange="this.form.submit()"><option value="">All</option><option value="F" @selected($sex === 'F')>Female</option><option value="M" @selected($sex === 'M')>Male</option></select></div>
        <noscript><button class="btn-primary">Show</button></noscript>
    </form>

    @php
        [$mLabel, $unit] = \App\Services\Herd\WeighStats::METRICS[$metric];
        $maxMean = max(1, $groups->max(fn ($g) => abs($g['mean'] ?? 0)));
    @endphp

    <div class="grid lg:grid-cols-3 gap-6 mt-6">
        <div class="panel lg:col-span-2 overflow-hidden">
            <div class="panel-head"><div class="panel-title">{{ $mLabel }} by {{ strtolower(\App\Services\Herd\WeighStats::DIMENSIONS[$by]) }}</div><span class="text-xs text-stone">herd average {{ $herd['mean'] ?? '—' }} {{ $unit }}</span></div>
            @forelse ($groups as $i => $g)
                <div class="px-6 py-5 border-b border-hairline last:border-0 grid grid-cols-[1fr_auto] gap-x-6 gap-y-3 items-center">
                    <div class="flex items-center gap-3">
                        @if ($i === 0 && $groups->count() > 1)<span class="chip bg-ochre/15 text-ochre-dark">Best</span>@endif
                        <span class="font-medium">{{ $g['key'] }}</span>
                        <span class="text-xs text-stone">n = {{ $g['n'] }}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-headline text-3xl num">{{ $g['mean'] }}</span><span class="text-sm text-stone ml-1">{{ $unit }}</span>
                        @if ($g['vs_herd'] !== null)<span class="ml-2 num text-sm {{ $g['vs_herd'] >= 0 ? 'up' : 'down' }}">{{ $g['vs_herd'] >= 0 ? '+' : '' }}{{ $g['vs_herd'] }}%</span>@endif
                    </div>
                    <div class="col-span-2 h-2 rounded-full bg-sand-deep overflow-hidden"><div class="h-full rounded-full {{ $i === 0 ? 'bg-ochre' : 'bg-char/80' }}" style="width: {{ abs($g['mean']) / $maxMean * 100 }}%"></div></div>
                    <div class="col-span-2 flex flex-wrap gap-x-6 gap-y-1 text-xs text-stone num">
                        <span>lowest {{ $g['min'] }}</span><span>highest {{ $g['max'] }}</span>
                        @if ($g['best'])<span>top: <a href="{{ route('rfid.animals.show', $g['best']['animal']) }}" class="text-char link-u">{{ $g['best']['animal']->visual_id }}</a> ({{ $g['best']['v'] }})</span>@endif
                    </div>
                </div>
            @empty
                <p class="px-6 py-16 text-center text-stone">Not enough data for this yet. {{ in_array($metric, ['adg', 'adg_life']) ? 'Gain needs at least two weighings per animal.' : '' }}</p>
            @endforelse
        </div>
        <div class="panel p-6 self-start text-sm text-stone leading-relaxed space-y-3">
            <div class="panel-title text-char">How to read this</div>
            <p><strong class="text-char">Lifetime gain</strong> is the average grams per day from first to last weighing. The best single way to compare rams by their lambs.</p>
            <p><strong class="text-char">100-day weight</strong> is worked out from the weighings either side of day 100, so lambs of different ages compare fairly.</p>
            <p>Small groups (fewer than 5) can fool you. A wide spread means the average isn't the whole story.</p>
        </div>
    </div>
@else
    <form method="GET" class="panel p-5 flex flex-wrap items-end gap-4">
        <input type="hidden" name="mode" value="sessions">
        <div><label class="field-label">Earlier weigh day</label><select name="a" class="field w-48">@foreach ($sessionDates as $d)<option value="{{ $d }}" @selected($a === $d)>{{ \Carbon\Carbon::parse($d)->format('j M Y') }}</option>@endforeach</select></div>
        <div><label class="field-label">Later weigh day</label><select name="b" class="field w-48">@foreach ($sessionDates as $d)<option value="{{ $d }}" @selected($b === $d)>{{ \Carbon\Carbon::parse($d)->format('j M Y') }}</option>@endforeach</select></div>
        <button class="btn-primary">Compare</button>
    </form>

    @if ($sa && $sb)
        <div class="panel mt-6 grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-hairline overflow-hidden">
            @foreach ([
                ['Days between', $days, 'days'],
                ['In both', $matched->count(), 'head'],
                ['Average change', ($changeSummary['mean'] > 0 ? '+' : '').$changeSummary['mean'], 'kg'],
                ['Daily gain', ($adgSummary['mean'] > 0 ? '+' : '').round($adgSummary['mean'] ?? 0), 'g'],
                ['Lost weight', $matched->where('change', '<', 0)->count(), 'head'],
            ] as [$l, $v, $u])
                <div class="p-6"><div class="kpi-label">{{ $l }}</div><div class="mt-3 font-headline text-4xl num">{{ $v }}<span class="text-base text-stone font-ui ml-1">{{ $u }}</span></div></div>
            @endforeach
        </div>
        <div class="grid md:grid-cols-2 gap-6 mt-6">
            @foreach ([[$sa, $a, 'A'], [$sb, $b, 'B']] as [$s, $d, $lbl])
                <div class="panel p-6 flex items-center justify-between gap-6">
                    <div><div class="kpi-label">{{ $lbl === 'A' ? 'Earlier' : 'Later' }} · {{ \Carbon\Carbon::parse($d)->format('j M Y') }}</div><div class="mt-2 font-headline text-4xl num">{{ $s->kg['mean'] }} <span class="text-base text-stone font-ui">kg average</span></div></div>
                    <div class="text-right text-sm text-stone num">{{ $s->kg['n'] }} weighed<br>{{ $s->kg['min'] }}–{{ $s->kg['max'] }} kg</div>
                </div>
            @endforeach
        </div>
        @if ($onlyA || $onlyB)<p class="mt-4 text-sm text-stone">{{ $onlyA }} only in the earlier one, {{ $onlyB }} only in the later one. They're left out.</p>@endif

        <div class="panel mt-6 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Animal</th><th>Sex</th><th class="text-right">Earlier</th><th class="text-right">Later</th><th class="text-right">Change</th><th class="text-right">Gain</th></tr></thead>
                    <tbody>
                    @foreach ($matched as $m)
                        <tr>
                            <td><a href="{{ route('rfid.animals.show', $m->animal) }}" class="font-num font-medium link-u">{{ $m->animal->visual_id }}</a></td>
                            <td class="text-stone">{{ $m->animal->sexLabel() }}</td>
                            <td class="text-right num text-stone">{{ $m->a }}</td>
                            <td class="text-right num">{{ $m->b }}</td>
                            <td class="text-right num {{ $m->change < 0 ? 'down' : 'up' }}">{{ $m->change > 0 ? '+' : '' }}{{ $m->change }} kg</td>
                            <td class="text-right num {{ $m->adg < 0 ? 'down' : '' }}">@if (abs($m->adg) > 700 && ($m->animal->species ?? 'sheep') !== 'cattle')<span class="text-ochre-dark" title="Probably a mis-typed or mis-read weight">check weight</span>@else{{ $m->adg > 0 ? '+' : '' }}{{ $m->adg }} g/day @endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="mt-8 text-stone">You need at least two weigh days to compare.</p>
    @endif
@endif
@endsection
