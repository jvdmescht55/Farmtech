@extends('layouts.rfid')
@section('title', 'Oorsig')
@section('eyebrow')Goeiedag, {{ \Illuminate\Support\Str::before($user->name, ' ') }} · {{ now()->translatedFormat('l j F') }}@endsection
@section('actions')
    <a href="{{ route('rfid.readers.index') }}" class="btn-secondary">Laai skanderings op</a>
    <a href="{{ route('rfid.animals.create') }}" class="btn-primary">+ Dier</a>
@endsection

@section('content')
@if ($herdCount === 0)
    <div class="panel p-10 mb-8">
        <h2 class="font-headline text-4xl">Kom ons kry jou kudde in.</h2>
        <p class="text-stone mt-2 max-w-2xl">Drie maniere — kies wat jy by die hand het.</p>
        <div class="grid md:grid-cols-3 gap-4 mt-8">
            @foreach ([[route('rfid.import.create'), 'Voer stamregister in', 'CSV uit Logix of \'n spreadsheet.'], [route('rfid.readers.index'), 'Sinchroniseer die leser', 'Laai die sessielêer op.'], [route('rfid.animals.create'), 'Voeg met die hand by', 'Vir \'n paar ramme of \'n nuwe aankoop.']] as $i => [$href, $t, $d])
                <a href="{{ $href }}" class="rounded-2xl border border-hairline p-6 hover:border-char transition"><div class="font-num text-xs text-stone-light">0{{ $i + 1 }}</div><div class="mt-3 font-medium">{{ $t }}</div><p class="text-sm text-stone mt-1">{{ $d }}</p></a>
            @endforeach
        </div>
    </div>
@endif

{{-- KPI strip --}}
<div class="panel grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-hairline overflow-hidden">
    <div class="p-6 sm:p-8">
        <div class="kpi-label">Aktiewe kudde</div>
        <div class="kpi-num mt-4">{{ number_format($herdCount) }}</div>
        <div class="text-sm text-stone mt-2">{{ $ewes }} ooie · {{ $rams }} ramme</div>
    </div>
    <div class="p-6 sm:p-8">
        <div class="kpi-label">Gemiddelde gewig</div>
        <div class="kpi-num mt-4">{{ $avgKg['mean'] ?? '—' }}<span class="text-lg text-stone font-ui ml-1">kg</span></div>
        <div class="text-sm text-stone mt-2">{{ $avgKg['n'] }} diere · {{ $avgKg['min'] ?? '—' }}–{{ $avgKg['max'] ?? '—' }} kg</div>
    </div>
    <div class="p-6 sm:p-8">
        <div class="kpi-label">Groei (ADG)</div>
        <div class="kpi-num mt-4 {{ ($avgAdg['mean'] ?? 0) < 0 ? 'down' : '' }}">{{ $avgAdg['mean'] !== null ? ($avgAdg['mean'] > 0 ? '+' : '').round($avgAdg['mean']) : '—' }}<span class="text-lg text-stone font-ui ml-1">g/dag</span></div>
        <div class="text-sm text-stone mt-2">sedert vorige weging</div>
    </div>
    <div class="p-6 sm:p-8">
        <div class="kpi-label">Laaste sessie</div>
        @if ($latestSession)
            <div class="kpi-num mt-4">{{ \Carbon\Carbon::parse($latestSession->date)->format('j M') }}</div>
            <div class="text-sm text-stone mt-2">{{ $latestSession->kg['n'] }} geweeg ·
                @if ($latestSession->mean_change !== null)<span class="{{ $latestSession->mean_change >= 0 ? 'up' : 'down' }}">{{ $latestSession->mean_change >= 0 ? '+' : '' }}{{ $latestSession->mean_change }} kg gem.</span>@else eerste sessie @endif</div>
        @else
            <div class="kpi-num mt-4 text-stone-light">—</div><div class="text-sm text-stone mt-2">Nog geen wegings</div>
        @endif
    </div>
</div>

{{-- Alerts --}}
@if ($alerts->isNotEmpty())
    @php
        $dot = ['critical' => 'bg-[#B0452F]', 'warning' => 'bg-ochre', 'info' => 'bg-stone-light'];
    @endphp
    <div class="panel mt-6 overflow-hidden">
        <div class="panel-head"><div class="panel-title">Aandag nodig</div><a href="{{ route('rfid.alerts') }}" class="text-sm link-u">Alle waarskuwings →</a></div>
        <ul class="grid md:grid-cols-2 divide-y md:divide-y-0 divide-hairline">
            @foreach ($alerts as $a)
                <li class="px-6 py-4 flex gap-4 md:[&:nth-child(n+3)]:border-t md:border-hairline md:[&:nth-child(even)]:border-l">
                    <span class="mt-2 w-2 h-2 shrink-0 rounded-full {{ $dot[$a['severity']] }}"></span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-baseline gap-x-2"><span class="font-medium">{{ $a['title'] }}</span>@if ($a['animal'])<a href="{{ route('rfid.animals.show', $a['animal']) }}" class="font-num text-sm link-u">{{ $a['animal']->visual_id }}</a>@endif</div>
                        <p class="text-sm text-stone truncate">{{ $a['detail'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid xl:grid-cols-3 gap-6 mt-6">
    <div class="panel xl:col-span-2">
        <div class="panel-head">
            <div><div class="panel-title">Gemiddelde gewig per sessie</div><div class="text-xs text-stone mt-0.5">{{ $sessionCount }} weegsessies</div></div>
            <a href="{{ route('rfid.weighings.index') }}" class="text-sm link-u">Alle sessies</a>
        </div>
        <div class="p-6"><x-chart.line :points="$trend" unit="kg" :height="260" /></div>
    </div>
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Verspreiding · huidige gewigte</div></div>
        <div class="p-6 pt-10"><x-chart.histogram :bins="$histogram" :height="200" /></div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    @foreach ([['Voorlopers', 'Hoogste groei sedert vorige weging', $gainers], ['Agterlopers', 'Laagste groei — hou dop', $laggers]] as [$title, $sub, $list])
        <div class="panel">
            <div class="panel-head"><div><div class="panel-title">{{ $title }}</div><div class="text-xs text-stone mt-0.5">{{ $sub }}</div></div></div>
            <ul class="divide-y divide-hairline">
                @forelse ($list as [$a, $l])
                    <li class="px-6 py-3 flex items-center gap-3">
                        <a href="{{ route('rfid.animals.show', $a) }}" class="font-num text-sm font-medium link-u">{{ $a->visual_id }}</a>
                        <span class="text-xs text-stone">{{ $a->sexLabel() }}</span>
                        <span class="ml-auto num text-sm text-stone">{{ $l->kg }} kg</span>
                        <span class="num text-sm w-20 text-right {{ $l->adg >= 0 ? 'up' : 'down' }}">{{ $l->adg > 0 ? '+' : '' }}{{ $l->adg }} g</span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-sm text-stone">Weeg twee keer om groei te sien.</li>
                @endforelse
            </ul>
        </div>
    @endforeach

    <div class="panel">
        <div class="panel-head"><div><div class="panel-title">Genetiese vlakke</div><div class="text-xs text-stone mt-0.5">uit die stamboom</div></div></div>
        @php($maxTier = max(1, $tierCounts->max()))
        <div class="p-6 space-y-3.5">
            @foreach ($tierCounts as $t => $n)
                <a href="{{ route('rfid.animals.index', ['tier' => $t]) }}" class="flex items-center gap-3 group">
                    <x-tier :tier="$t === '?' ? null : $t" class="w-11" />
                    <div class="flex-1 h-2 rounded-full bg-sand-deep overflow-hidden"><div class="h-full rounded-full {{ $t === 'SP' ? 'bg-char' : ($t === '?' ? 'bg-stone-light/40' : 'bg-ochre') }}" style="width: {{ $n / $maxTier * 100 }}%"></div></div>
                    <span class="w-8 text-right num text-sm">{{ $n }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-6">
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Groeitendens (g/dag)</div></div>
        <div class="p-6"><x-chart.line :points="$adgTrend" unit="g/dag" :height="200" /></div>
    </div>
    <div class="panel">
        <div class="panel-head"><div class="panel-title">Onlangse sinchronisasies</div><span class="text-xs text-stone">{{ number_format($scans30) }} skanderings · 30 dae</span></div>
        <ul class="divide-y divide-hairline">
            @forelse ($syncs as $s)
                <li><a href="{{ route('rfid.sync.show', $s) }}" class="px-6 py-3.5 flex justify-between gap-3 text-sm hover:bg-sand-light">
                    <span>{{ $s->reader?->name ?? strtoupper($s->source) }} <span class="text-stone">· {{ $s->scan_count }} skanderings{{ $s->new_count ? ' · '.$s->new_count.' nuut' : '' }}</span></span>
                    <span class="text-stone num text-xs">{{ $s->created_at->format('d M H:i') }}</span>
                </a></li>
            @empty
                <li class="px-6 py-8 text-sm text-stone">Nog niks gesinchroniseer nie.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
