@extends('layouts.rfid')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Howzit, '.\Illuminate\Support\Str::before($user->name, ' '))
@section('eyebrow'){{ now()->format('l j F') }} · KraalTrac Pro @endsection

@section('content')
@php
    $urgent = $alerts->whereIn('severity', ['critical', 'warning'])->count();
    $summary = $herdCount === 0
        ? 'Nothing in the herd book yet — start with one of the tiles below.'
        : ($urgent ? $urgent.' '.\Illuminate\Support\Str::plural('thing', $urgent).' need you today.' : 'Nothing urgent today. Lekker.')
            .($latestSession ? ' Last weigh day '.\Carbon\Carbon::parse($latestSession->date)->format('j M').'.' : '');
    $tiles = [
        [route('rfid.live'), 'live', 'Start weighing', 'Scan and see each animal pop up', 'start'],
        [route('rfid.animals.create'), 'plus', 'Add an animal', 'Or let the scale do it', null],
        [route('rfid.catalogues.index'), 'book', 'Auction book', $bookCount ? $bookCount.' made so far' : 'Logix layout, ready to print', null],
        [route('rfid.data'), 'upload', 'Bring records in', 'Excel, CSV or straight off the scale', null],
    ];
@endphp

<section class="relative overflow-hidden rounded-[28px] bg-char text-sand">
    <img src="{{ Img::url('karoo-mist', true) }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-50 img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char via-char/80 to-char/20"></div>
    <div class="relative p-7 sm:p-10">
        <p class="text-lg sm:text-2xl max-w-2xl leading-snug font-headline">{{ $summary }}</p>
        <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($tiles as [$href, $icon, $title, $sub, $tour])
                <a href="{{ $href }}" @if ($tour) data-tour="{{ $tour }}" @endif class="group rounded-2xl bg-white/8 hover:bg-white/15 border border-white/10 backdrop-blur p-4 sm:p-5 transition">
                    <span class="w-10 h-10 rounded-xl bg-sand text-char grid place-items-center group-hover:scale-105 transition">@include('partials.icon', ['name' => $icon, 'class' => 'w-5 h-5'])</span>
                    <div class="mt-4 font-medium">{{ $title }}</div>
                    <div class="text-xs text-sand/60 mt-0.5">{{ $sub }}</div>
                </a>
            @endforeach
        </div>
    </div>
</section>

@if ($herdCount === 0)
    <div class="panel p-8 mt-6 grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="font-headline text-3xl">New here? Start with the 10-minute guide.</div>
            <p class="text-stone mt-2">Pair your KraalTrac, bring your records in from Excel, and do your first weigh day — step by step.</p>
        </div>
        <div class="flex md:justify-end items-center gap-2">
            <a href="{{ route('help.show', 'getting-started') }}" class="btn-dark">Open the guide</a>
        </div>
    </div>
@else
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <div class="panel p-6">
            <div class="kpi-label">Head in the herd</div>
            <div class="kpi-num mt-3" x-data x-countup>{{ $herdCount }}</div>
            <div class="text-sm text-stone mt-2">{{ $ewes }} female · {{ $rams }} male</div>
        </div>
        <div class="panel p-6">
            <div class="kpi-label">Average weight</div>
            <div class="kpi-num mt-3"><span x-data x-countup>{{ $avgKg['mean'] ?? '—' }}</span><span class="text-lg text-stone font-ui ml-1">kg</span></div>
            @php
                $spark = collect($trend)->pluck('value')->take(-8)->values();
                $sMin = $spark->min(); $sMax = $spark->max(); $sRange = max(0.1, $sMax - $sMin);
                $sPts = $spark->map(fn ($v, $i) => round($i * 100 / max(1, $spark->count() - 1), 1).','.round(28 - ($v - $sMin) / $sRange * 24, 1))->implode(' ');
            @endphp
            @if ($spark->count() > 1)<svg viewBox="0 0 100 30" class="mt-2 w-full h-7" preserveAspectRatio="none"><polyline points="{{ $sPts }}" fill="none" stroke="#B8732E" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg>@else<div class="text-sm text-stone mt-2">{{ $avgKg['n'] }} weighed lately</div>@endif
        </div>
        <div class="panel p-6">
            <div class="kpi-label">Daily gain</div>
            <div class="kpi-num mt-3 {{ ($avgAdg['mean'] ?? 0) < 0 ? 'down' : '' }}"><span x-data x-countup>{{ $avgAdg['mean'] !== null ? ($avgAdg['mean'] > 0 ? '+' : '').round($avgAdg['mean']) : '—' }}</span><span class="text-lg text-stone font-ui ml-1">g</span></div>
            <div class="text-sm text-stone mt-2">per animal, per day</div>
        </div>
        <div class="panel p-6">
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
        <div class="panel lg:col-span-3 overflow-hidden" data-tour="attention">
            <div class="panel-head"><div class="panel-title">Needs your attention</div><a href="{{ route('rfid.alerts') }}" class="text-sm link-u">All alerts</a></div>
            @php
                $dot = ['critical' => 'bg-[#B0452F]', 'warning' => 'bg-ochre', 'info' => 'bg-stone-light'];
            @endphp
            <ul class="divide-y divide-hairline">
                @forelse ($alerts as $a)
                    <li class="px-6 py-4 flex gap-4 hover:bg-sand-light transition-colors">
                        <span class="mt-2 w-2 h-2 shrink-0 rounded-full {{ $dot[$a['severity']] }} {{ $a['severity'] === 'critical' ? 'animate-pulse' : '' }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-2"><span class="font-medium">{{ $a['title'] }}</span>@if ($a['animal'])<a href="{{ route('rfid.animals.show', $a['animal']) }}" class="font-num text-sm link-u">{{ $a['animal']->visual_id }}</a>@endif</div>
                            <p class="text-sm text-stone">{{ $a['detail'] }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-14 text-center"><div class="font-headline text-4xl">All lekker.</div><p class="text-stone text-sm mt-1">Nothing needs you right now.</p></li>
                @endforelse
            </ul>
        </div>
        <div class="panel lg:col-span-2">
            <div class="panel-head"><div class="panel-title">Average weight</div><a href="{{ route('rfid.weighings.index') }}" class="text-sm link-u">Weigh days</a></div>
            <div class="p-6"><x-chart.line :points="$trend" unit="kg" :height="260" /></div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mt-6">
        @foreach ([['Growing best', $gainers, 'up'], ['Falling behind', $laggers, 'down']] as [$title, $list, $tone])
            <div class="panel">
                <div class="panel-head"><div class="panel-title">{{ $title }}</div><span class="text-xs text-stone">since last weighing</span></div>
                <ul class="divide-y divide-hairline">
                    @forelse ($list as [$a, $l])
                        <li><a href="{{ route('rfid.animals.show', $a) }}" class="px-6 py-3 flex items-center gap-3 hover:bg-sand-light transition-colors">
                            <span class="font-num text-sm font-medium">{{ $a->visual_id }}</span>
                            <span class="text-xs text-stone">{{ $a->sexLabel() }}</span>
                            <span class="ml-auto num text-sm text-stone">{{ $l->kg }} kg</span>
                            <span class="num text-sm w-20 text-right {{ $l->adg >= 0 ? 'up' : 'down' }}">{{ $l->adg > 0 ? '+' : '' }}{{ $l->adg }} g</span>
                        </a></li>
                    @empty
                        <li class="px-6 py-8 text-sm text-stone">Weigh twice and this fills in.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>
@endif
@endsection

@section('tour')
    <x-tour key="rfid" :auto="! $tourSeen || request()->boolean('tour')" :steps="[
        ['target' => null, 'title' => 'Welcome to KraalTrac Pro, '.\Illuminate\Support\Str::before($user->name, ' ').'!', 'body' => 'Let\'s take a one-minute look around. You can skip any time, and replay this from the <strong>?</strong> button.'],
        ['target' => 'tabs', 'title' => 'Everything lives in these tabs', 'body' => '<strong>Overview</strong> is today. <strong>Herd</strong> is your herd book. <strong>Weighing</strong> has every weigh day, comparisons and sorting. <strong>Alerts</strong> and <strong>Records</strong> do what they say. The rest is under <strong>More</strong>.'],
        ['target' => 'start', 'title' => 'Weigh day? Start here.', 'body' => 'Opens the live view. Scan with your KraalTrac and each animal pops up with its weight, gain and any warnings.'],
        ['target' => 'attention', 'title' => 'We keep an eye out for you', 'body' => 'Weight loss, missed drinks, lambs due, low birth weights, withdrawal dates — anything that needs you lands here.'],
        ['target' => 'switcher', 'title' => 'All your devices', 'body' => 'Switch between KraalTrac Pro, KraalTrac Watch and your own gadgets. They all share one herd book.'],
        ['target' => 'help', 'title' => 'Stuck? Tap the ?', 'body' => 'Step-by-step guides for Excel uploads, connecting the scale, auction books and more — and you can replay this tour.', 'cta' => 'Lekker, let\'s go'],
    ]" />
@endsection
