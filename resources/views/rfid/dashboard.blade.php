@extends('layouts.rfid')
@section('title', 'Howzit, '.\Illuminate\Support\Str::before($user->name, ' '))
@section('eyebrow'){{ now()->format('l j F') }} · KraalTrac Pro @endsection
@section('actions')
    <a href="{{ route('rfid.live') }}" class="btn-primary">Start weighing</a>
@endsection

@section('content')
@if ($herdCount === 0)
    <div class="panel p-8 sm:p-10">
        <h2 class="font-headline text-4xl">Let's get your herd in, oom.</h2>
        <p class="text-stone mt-2 max-w-2xl">Pick whichever is easiest — you can mix and match later.</p>
        <div class="grid md:grid-cols-3 gap-4 mt-8">
            @foreach ([[route('rfid.readers.index'), 'Pair your KraalTrac', 'Type the 6-digit code on its screen. Then just scan.'], [route('rfid.data'), 'Bring your records', 'Upload a CSV from Logix or a spreadsheet.'], [route('rfid.animals.create'), 'Add a few by hand', 'Handy for a couple of rams or a new purchase.']] as $i => [$href, $t, $d])
                <a href="{{ $href }}" class="rounded-2xl border border-hairline p-6 hover:border-char transition"><div class="font-num text-xs text-stone-light">0{{ $i + 1 }}</div><div class="mt-3 font-medium text-lg">{{ $t }}</div><p class="text-sm text-stone mt-1">{{ $d }}</p></a>
            @endforeach
        </div>
    </div>
@else
    <div class="panel grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-hairline overflow-hidden">
        <div class="p-6 sm:p-7">
            <div class="kpi-label">Head in the herd</div>
            <div class="kpi-num mt-3">{{ number_format($herdCount) }}</div>
            <div class="text-sm text-stone mt-2">{{ $ewes }} female · {{ $rams }} male</div>
        </div>
        <div class="p-6 sm:p-7">
            <div class="kpi-label">Average weight</div>
            <div class="kpi-num mt-3">{{ $avgKg['mean'] ?? '—' }}<span class="text-lg text-stone font-ui ml-1">kg</span></div>
            <div class="text-sm text-stone mt-2">{{ $avgKg['n'] }} weighed lately</div>
        </div>
        <div class="p-6 sm:p-7">
            <div class="kpi-label">Daily gain</div>
            <div class="kpi-num mt-3 {{ ($avgAdg['mean'] ?? 0) < 0 ? 'down' : '' }}">{{ $avgAdg['mean'] !== null ? ($avgAdg['mean'] > 0 ? '+' : '').round($avgAdg['mean']) : '—' }}<span class="text-lg text-stone font-ui ml-1">g</span></div>
            <div class="text-sm text-stone mt-2">per animal per day</div>
        </div>
        <div class="p-6 sm:p-7">
            <div class="kpi-label">Last weigh day</div>
            @if ($latestSession)
                <div class="kpi-num mt-3">{{ \Carbon\Carbon::parse($latestSession->date)->format('j M') }}</div>
                <div class="text-sm text-stone mt-2">{{ $latestSession->kg['n'] }} weighed @if ($latestSession->mean_change !== null)· <span class="{{ $latestSession->mean_change >= 0 ? 'up' : 'down' }}">{{ $latestSession->mean_change >= 0 ? '+' : '' }}{{ $latestSession->mean_change }} kg</span>@endif</div>
            @else
                <div class="kpi-num mt-3 text-stone-light">—</div><div class="text-sm text-stone mt-2">nothing yet</div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6 mt-6">
        <div class="panel lg:col-span-3 overflow-hidden">
            <div class="panel-head"><div class="panel-title">Needs your attention</div><a href="{{ route('rfid.alerts') }}" class="text-sm link-u">All alerts</a></div>
            @php
                $dot = ['critical' => 'bg-[#B0452F]', 'warning' => 'bg-ochre', 'info' => 'bg-stone-light'];
            @endphp
            <ul class="divide-y divide-hairline">
                @forelse ($alerts as $a)
                    <li class="px-6 py-4 flex gap-4">
                        <span class="mt-2 w-2 h-2 shrink-0 rounded-full {{ $dot[$a['severity']] }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-2"><span class="font-medium">{{ $a['title'] }}</span>@if ($a['animal'])<a href="{{ route('rfid.animals.show', $a['animal']) }}" class="font-num text-sm link-u">{{ $a['animal']->visual_id }}</a>@endif</div>
                            <p class="text-sm text-stone">{{ $a['detail'] }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center"><div class="font-headline text-3xl">All lekker.</div><p class="text-stone text-sm mt-1">Nothing needs you right now.</p></li>
                @endforelse
            </ul>
        </div>
        <div class="panel lg:col-span-2">
            <div class="panel-head"><div class="panel-title">Average weight</div><a href="{{ route('rfid.weighings.index') }}" class="text-sm link-u">Weigh days</a></div>
            <div class="p-6"><x-chart.line :points="$trend" unit="kg" :height="260" /></div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mt-6">
        @foreach ([['Growing best', $gainers], ['Falling behind', $laggers]] as [$title, $list])
            <div class="panel">
                <div class="panel-head"><div class="panel-title">{{ $title }}</div><span class="text-xs text-stone">since last weighing</span></div>
                <ul class="divide-y divide-hairline">
                    @forelse ($list as [$a, $l])
                        <li class="px-6 py-3 flex items-center gap-3">
                            <a href="{{ route('rfid.animals.show', $a) }}" class="font-num text-sm font-medium link-u">{{ $a->visual_id }}</a>
                            <span class="text-xs text-stone">{{ $a->sexLabel() }}</span>
                            <span class="ml-auto num text-sm text-stone">{{ $l->kg }} kg</span>
                            <span class="num text-sm w-20 text-right {{ $l->adg >= 0 ? 'up' : 'down' }}">{{ $l->adg > 0 ? '+' : '' }}{{ $l->adg }} g</span>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-sm text-stone">Weigh twice and this fills in.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>
@endif
@endsection
