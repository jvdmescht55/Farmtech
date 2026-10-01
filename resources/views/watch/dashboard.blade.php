@extends('layouts.watch')
@section('title', 'Who came to drink?')
@section('eyebrow')KraalTrac Watch · {{ now()->format('l j F') }} @endsection
@section('actions')<a href="{{ route('watch.points') }}" class="btn-secondary">+ Water point</a>@endsection

@section('content')
@if ($points->isEmpty())
    <div class="panel p-8 sm:p-10">
        <h2 class="font-headline text-4xl">Put your first Watch up, oom.</h2>
        <p class="text-stone mt-2 max-w-2xl">Mount the KraalTrac Watch at a trough or gate, switch it on, and pair it with the 6-digit code on its screen. Every tagged animal that walks past gets counted — and if one skips the water, you'll hear about it.</p>
        <a href="{{ route('watch.points') }}" class="btn-dark mt-8">Pair a Watch</a>
    </div>
@else
    <div class="panel grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-hairline overflow-hidden">
        <div class="p-6 sm:p-7"><div class="kpi-label">Came to drink today</div><div class="kpi-num mt-3">{{ $seenToday }}<span class="text-lg text-stone font-ui ml-1">/ {{ $expected }}</span></div><div class="text-sm text-stone mt-2">regulars from the last 2 weeks</div></div>
        <div class="p-6 sm:p-7"><div class="kpi-label">Missed their drink</div><div class="kpi-num mt-3 {{ $missed->count() ? 'down' : '' }}">{{ $missed->count() }}</div><div class="text-sm text-stone mt-2">past the time limit</div></div>
        <div class="p-6 sm:p-7"><div class="kpi-label">Visits today</div><div class="kpi-num mt-3">{{ $points->sum('visits_today') }}</div><div class="text-sm text-stone mt-2">across {{ $points->count() }} {{ \Illuminate\Support\Str::plural('point', $points->count()) }}</div></div>
        <div class="p-6 sm:p-7"><div class="kpi-label">Points online</div><div class="kpi-num mt-3">{{ $points->filter(fn ($p) => $p->point->isOnline())->count() }}<span class="text-lg text-stone font-ui ml-1">/ {{ $points->count() }}</span></div><div class="text-sm text-stone mt-2">heard from in 15 min</div></div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6 mt-6">
        <div class="panel lg:col-span-3 overflow-hidden">
            <div class="panel-head"><div class="panel-title">Hasn't been to drink</div><a href="{{ route('watch.animals', ['show' => 'missed']) }}" class="text-sm link-u">See all</a></div>
            <ul class="divide-y divide-hairline">
                @forelse ($missed->take(8) as $w)
                    <li class="px-6 py-3.5 flex items-center gap-4">
                        <span class="w-2 h-2 rounded-full {{ $w->hours_since > $w->limit * 2 ? 'bg-[#B0452F]' : 'bg-ochre' }}"></span>
                        <a href="{{ route('rfid.animals.show', $w->animal) }}" class="font-num font-medium link-u">{{ $w->animal->visual_id }}</a>
                        <span class="text-sm text-stone">{{ $w->animal->sexLabel() }}</span>
                        <span class="ml-auto text-sm text-stone">last at {{ $w->last_point }}</span>
                        <span class="text-sm num w-24 text-right down">{{ round($w->hours_since) }} h ago</span>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center"><div class="font-headline text-3xl">Everyone's had a drink.</div><p class="text-stone text-sm mt-1">Lekker — nothing to chase today.</p></li>
                @endforelse
            </ul>
        </div>
        <div class="panel lg:col-span-2">
            <div class="panel-head"><div class="panel-title">Head count per day</div></div>
            <div class="p-6"><x-chart.line :points="$headcount" unit="head" :height="240" /></div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-6">
        <div class="panel">
            <div class="panel-head"><div class="panel-title">When they drink</div><span class="text-xs text-stone">visits per hour · last 7 days</span></div>
            @php
                $maxH = max(1, max($hourly));
            @endphp
            <div class="p-6">
                <div class="flex items-end gap-[3px] h-40">
                    @foreach ($hourly as $h => $n)
                        <div class="group relative flex-1 h-full flex items-end"><div class="w-full rounded-t-[3px] bg-char/85 group-hover:bg-ochre" style="height: {{ max(2, $n / $maxH * 100) }}%"></div><div class="pointer-events-none absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-char px-1.5 py-0.5 text-[10px] text-sand opacity-0 group-hover:opacity-100">{{ sprintf('%02d:00', $h) }} · {{ $n }}</div></div>
                    @endforeach
                </div>
                <div class="mt-2 flex justify-between font-num text-[11px] text-stone-light"><span>00:00</span><span>06:00</span><span>12:00</span><span>18:00</span><span>23:00</span></div>
            </div>
        </div>
        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Water points</div><a href="{{ route('watch.points') }}" class="text-sm link-u">Manage</a></div>
            <ul class="divide-y divide-hairline">
                @foreach ($points as $p)
                    <li class="px-6 py-3.5 flex items-center gap-3 text-sm">
                        <span class="w-2 h-2 rounded-full {{ $p->point->isOnline() ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span>
                        <span class="font-medium">{{ $p->point->location ?: $p->point->name }}</span>
                        <span class="ml-auto text-stone">{{ $p->animals_today }} head · {{ $p->visits_today }} visits</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
@endsection

@section('tour')
    <x-tour key="watch" :auto="! auth()->user()->hasSeenTour('watch') || request()->boolean('tour')" :steps="[
        ['target' => null, 'title' => 'KraalTrac Watch', 'body' => 'It counts every tagged animal that comes to the water — and tells you when one hasn\'t.'],
        ['target' => 'tabs', 'title' => 'Three tabs, that\'s it', 'body' => '<strong>Today</strong> is who came to drink. <strong>Animals</strong> lists every regular. <strong>Water points</strong> is where you pair and name each Watch.'],
        ['target' => 'switcher', 'title' => 'Same herd book', 'body' => 'Jump back to KraalTrac Pro here — water visits show on each animal\'s page too.', 'cta' => 'Lekker'],
    ]" />
@endsection
