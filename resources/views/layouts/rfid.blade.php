@extends('layouts.herd', ['module' => 'rfid'])
@php
    $u = auth()->user();
    $alertCount = \Illuminate\Support\Facades\Cache::remember("alert-count:{$u->id}", 60, fn () => app(\App\Services\Herd\HerdAlerts::class)->forUser($u->id)->whereIn('severity', ['critical', 'warning'])->count());
    $is = fn (...$p) => request()->routeIs(...$p);
    // Everything is visible: main tabs (first 4 also sit in the phone's bottom bar), then the rest.
    $nav = [
        ['href' => route('rfid.dashboard'), 'label' => 'Overview', 'icon' => 'home', 'on' => $is('rfid.dashboard')],
        ['href' => route('rfid.animals.index'), 'label' => 'Herd', 'icon' => 'herd', 'on' => $is('rfid.animals.*')],
        ['href' => route('rfid.weighings.index'), 'label' => 'Weighing', 'icon' => 'scale', 'on' => $is('rfid.weighings.*', 'rfid.compare', 'rfid.draft*')],
        ['href' => route('rfid.alerts'), 'label' => 'Alerts', 'icon' => 'bell', 'on' => $is('rfid.alerts*'), 'badge' => $alertCount ?: null],
        ['href' => route('rfid.events.index'), 'label' => 'Records', 'icon' => 'note', 'on' => $is('rfid.events.*')],
        ['href' => route('rfid.catalogues.index'), 'label' => 'Auction books', 'icon' => 'book', 'on' => $is('rfid.catalogues.*')],
        ['href' => route('rfid.data.scale'), 'label' => 'Sync the scale', 'icon' => 'usb', 'on' => $is('rfid.data.scale')],
    ];
    $navMore = [
        ['href' => route('rfid.live'), 'label' => 'Live view', 'icon' => 'live', 'on' => $is('rfid.live')],
        ['href' => route('rfid.data'), 'label' => 'Import & export', 'icon' => 'table', 'on' => $is('rfid.data', 'rfid.data.preview', 'rfid.import.*')],
        ['href' => route('rfid.readers.index'), 'label' => 'Devices', 'icon' => 'chip', 'on' => $is('rfid.readers.*', 'rfid.sync.*')],
        ['href' => route('rfid.settings.edit'), 'label' => 'Farm settings', 'icon' => 'gear', 'on' => $is('rfid.settings.*')],
        ['href' => route('site.prices'), 'label' => 'Market prices', 'icon' => 'compare', 'on' => false],
        ['href' => route('site.calculator'), 'label' => 'Auction calculator', 'icon' => 'table', 'on' => false],
    ];
@endphp

@section('subnav')
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
