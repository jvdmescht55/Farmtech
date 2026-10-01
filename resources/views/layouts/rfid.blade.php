@extends('layouts.herd', ['module' => 'rfid'])
@php
    $u = auth()->user();
    $alertCount = \Illuminate\Support\Facades\Cache::remember("alert-count:{$u->id}", 60, fn () => app(\App\Services\Herd\HerdAlerts::class)->forUser($u->id)->whereIn('severity', ['critical', 'warning'])->count());
    $is = fn (...$p) => request()->routeIs(...$p);
    $more = [
        [route('rfid.live'), 'Live view', $is('rfid.live')],
        [route('rfid.catalogues.index'), 'Auction books', $is('rfid.catalogues.*')],
        [route('help.index'), 'Help & guides', false],
        [route('rfid.readers.index'), 'Devices', $is('rfid.readers.*', 'rfid.sync.*')],
        [route('rfid.data'), 'Import & export', $is('rfid.data*', 'rfid.import.*')],
        [route('rfid.settings.edit'), 'Farm settings', $is('rfid.settings.*')],
    ];
    $moreOn = collect($more)->contains(fn ($m) => $m[2]);
@endphp

@section('tabs')
    @include('partials.tab', ['href' => route('rfid.dashboard'), 'label' => 'Overview', 'icon' => 'home', 'on' => $is('rfid.dashboard')])
    @include('partials.tab', ['href' => route('rfid.animals.index'), 'label' => 'Herd', 'icon' => 'herd', 'on' => $is('rfid.animals.*')])
    @include('partials.tab', ['href' => route('rfid.weighings.index'), 'label' => 'Weighing', 'icon' => 'scale', 'on' => $is('rfid.weighings.*', 'rfid.compare', 'rfid.draft*')])
    @include('partials.tab', ['href' => route('rfid.alerts'), 'label' => 'Alerts', 'icon' => 'bell', 'on' => $is('rfid.alerts*'), 'badge' => $alertCount ?: null])
    @include('partials.tab', ['href' => route('rfid.events.index'), 'label' => 'Records', 'icon' => 'note', 'on' => $is('rfid.events.*')])
    @include('partials.tab', ['href' => $moreOn ? route('rfid.dashboard', ['more' => 1]) : request()->fullUrlWithQuery(['more' => request()->boolean('more') ? null : 1]), 'label' => $moreOn ? collect($more)->first(fn ($m) => $m[2])[1] : 'More', 'icon' => 'more', 'on' => $moreOn || request()->boolean('more')])
@endsection

@section('subnav')
    {{-- "More" opens as a simple row of pills under the title — no fiddly dropdown on phones. --}}
    @if ($moreOn || request()->boolean('more'))
        <div class="flex flex-wrap gap-1 mb-8">
            @foreach ($more as [$href, $label, $on])
                <a href="{{ $href }}" class="rounded-full px-4 h-9 inline-flex items-center text-sm border {{ $on ? 'bg-char text-sand border-char' : 'bg-white border-hairline text-stone hover:text-char hover:border-char' }}">{{ $label }}</a>
            @endforeach
        </div>
    @endif
    @if ($is('rfid.weighings.*', 'rfid.compare', 'rfid.draft*'))
        <div class="inline-flex rounded-full bg-white border border-hairline p-1 mb-8">
            @foreach ([['rfid.weighings.index', 'rfid.weighings.*', 'Sessions'], ['rfid.compare', 'rfid.compare', 'Compare'], ['rfid.draft', 'rfid.draft*', 'Sort by weight']] as [$r, $p, $l])
                <a href="{{ route($r) }}" class="rounded-full px-5 h-9 inline-flex items-center text-sm {{ request()->routeIs($p) ? 'bg-char text-sand' : 'text-stone hover:text-char' }}">{{ $l }}</a>
            @endforeach
        </div>
    @endif
    @php($speciesIn = $is('rfid.dashboard', 'rfid.weighings.*', 'rfid.compare', 'rfid.draft') ? \App\Services\Herd\WeighStats::speciesIn($u->id) : collect())
    @if ($speciesIn->count() > 1)
        @php($curSpecies = request('species', $speciesIn->keys()->first()))
        <div class="flex flex-wrap gap-2 mb-8">
            @foreach ($speciesIn as $sp => $n)
                <a href="{{ request()->fullUrlWithQuery(['species' => $sp]) }}" class="chip h-9 px-4 border {{ $curSpecies === $sp ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char' }}">{{ config("herd.species.$sp.plural") }} <span class="opacity-60">{{ $n }}</span></a>
            @endforeach
        </div>
    @endif
@endsection
