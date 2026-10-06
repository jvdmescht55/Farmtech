@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Red meat prices this week — Farmtech')
@section('description', 'This week\'s South African red meat prices: lamb and beef carcass (A2/3, B2/3, C2/3), feeder lambs and weaner calves in R/kg, with weekly and yearly change. Free, from RPO\'s weekly figures.')
@section('hero_dark', '1')

@section('content')
<section class="relative overflow-hidden bg-char text-white">
    <img src="{{ Img::url('dorper-ram', true) }}" srcset="{{ Img::srcset('dorper-ram') }}" sizes="100vw" alt="{{ Img::alt('dorper-ram') }}" class="absolute inset-0 w-full h-full object-cover object-[50%_40%] opacity-55 img-grade kenburns">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/50 to-char/30"></div>
    <div class="relative wrap pt-40 pb-16 sm:pb-20">
        <p class="text-white/70 text-lg">Free · updated every week</p>
        <h1 class="h-display mt-3 text-[clamp(3.4rem,9vw,7.5rem)]">What's meat fetching this week?</h1>
        @if ($m)
            <p class="mt-6 text-lg text-white/80 max-w-xl">National average prices for the week ending <strong>{{ \Carbon\Carbon::parse($m['week'])->format('j F Y') }}</strong>, in rand per kilogram.@if ($m['fetched_at']) <span class="text-white/55">Checked {{ \Carbon\Carbon::parse($m['fetched_at'])->diffForHumans() }}.</span>@endif</p>
        @endif
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('site.calculator') }}" class="btn-light">Work out an auction price</a>
            <a href="#table" class="btn-line-light">Last 12 weeks</a>
        </div>
    </div>
</section>

@if (! $m)
    <section class="wrap py-24 text-center"><div class="font-headline text-4xl">Prices are on their way.</div><p class="text-stone mt-3">We couldn't load this week's figures yet. Try again in a while.</p></section>
@else
    @php
        $spark = function (array $h) {
            if (count($h) < 2) return '';
            $min = min($h); $max = max($h); $r = max(0.01, $max - $min); $n = count($h) - 1;
            return collect($h)->map(fn ($v, $i) => round($i / $n * 300, 1).','.round(56 - ($v - $min) / $r * 52, 1))->implode(' ');
        };
    @endphp
    @foreach (['mutton' => ['Sheep & lambs', 'Lamb is A-grade; B and C are older mutton. Feeder lambs are live-weight prices for lambs going to the feedlot.'], 'beef' => ['Cattle', 'A2/3 is young beef. Weaner calf prices are live-weight, for bull calves going to the feedlot.']] as $group => [$title, $note])
        <section class="wrap py-16 sm:py-20 {{ $loop->first ? '' : 'pt-0 sm:pt-0' }}">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="h-display text-[clamp(2.4rem,5vw,3.8rem)]">{{ $title }}</h2>
                <p class="text-stone max-w-md text-sm">{{ $note }}</p>
            </div>
            <div class="mt-8 grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach (collect($m['series'])->where('group', $group) as $key => $s)
                    <div id="{{ $key }}" class="rounded-[24px] bg-white border border-hairline p-6 scroll-mt-28">
                        <div class="text-sm text-stone">{{ $s['label'] }}</div>
                        <div class="mt-2 flex items-baseline gap-1.5"><span class="font-headline text-5xl">R{{ number_format($s['value'], 2) }}</span><span class="text-stone text-sm">/kg</span></div>
                        <div class="mt-2 flex gap-4 text-xs">
                            @foreach (['week_change' => 'this week', 'year_change' => 'on a year ago'] as $f => $label)
                                @if ($s[$f] !== null)<span class="{{ $s[$f] > 0 ? 'text-[#3F7A3A]' : ($s[$f] < 0 ? 'text-[#B0452F]' : 'text-stone') }}">{{ $s[$f] > 0 ? '▲' : ($s[$f] < 0 ? '▼' : '•') }} {{ abs($s[$f]) }}% {{ $label }}</span>@endif
                            @endforeach
                        </div>
                        <svg viewBox="0 0 300 60" class="w-full h-14 mt-4" preserveAspectRatio="none" aria-label="Last 52 weeks"><polyline points="{{ $spark($s['history']) }}" fill="none" stroke="#B8732E" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"/></svg>
                        <div class="flex justify-between text-[11px] text-stone-light"><span>52 weeks ago</span><span>now</span></div>
                        @php($calcKind = ['lamb_a' => 'lambs', 'feeder_lamb' => 'lambs', 'mutton_b' => 'sheep', 'mutton_c' => 'sheep', 'beef_a' => 'cattle', 'beef_b' => 'cattle', 'beef_c' => 'cattle', 'weaner' => 'weaners'][$key] ?? null)
                        @if ($calcKind)<a href="{{ route('site.calculator', ['kind' => $calcKind]) }}" class="mt-3 inline-block text-xs underline text-stone hover:text-char">Work out an auction price →</a>@endif
                        @if ($s['history'])<div class="mt-2 text-xs text-stone">52-week low R{{ number_format(min($s['history']), 2) }} · high R{{ number_format(max($s['history']), 2) }}</div>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section id="table" class="bg-sand-deep/60 border-y border-hairline scroll-mt-20">
        <div class="wrap py-16 sm:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="h-display text-[clamp(2.2rem,4.5vw,3.4rem)]">The last 12 weeks</h2>
                <a href="{{ route('site.prices', ['download' => 'csv']) }}" class="btn-line btn-sm">Download all weeks (CSV, opens in Excel)</a>
            </div>
            <div class="mt-8 overflow-x-auto rounded-[24px] bg-white border border-hairline">
                <table class="tbl min-w-[760px]">
                    <thead><tr><th>Week ending</th>@foreach ($m['series'] as $s)<th class="text-right">{{ $s['label'] }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach (array_slice($m['rows'], 0, 12) as $r)
                            <tr><td class="whitespace-nowrap">{{ \Carbon\Carbon::parse($r['week'])->format('j M Y') }}</td>@foreach (array_keys($m['series']) as $k)<td class="text-right num">{{ $r[$k] !== null ? number_format($r[$k], 2) : '—' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-5 text-sm text-stone max-w-3xl">All prices in R/kg. Carcass prices are what abattoirs paid per kg of carcass; feeder lamb and weaner calf prices are per kg live weight. Source: <a href="{{ \App\Services\MarketPrices::SOURCE_URL }}" target="_blank" rel="noopener" class="underline">Red Meat Producers Organisation (RPO), weekly carcass prices</a>. We copy their published figures every day; they publish once a week. For guidance only.</p>
        </div>
    </section>

    <section class="wrap py-20 sm:py-24 grid lg:grid-cols-2 gap-10 items-center">
        <div>
            <h2 class="h-display text-[clamp(2.4rem,5vw,4rem)]">Going to the auction?</h2>
            <p class="mt-5 text-lg text-stone max-w-md">Our free auction calculator turns a price per head into rand per kg (and back), takes off commission and transport, and compares it with this week's carcass price.</p>
            <a href="{{ route('site.calculator') }}" class="btn-dark mt-8">Open the calculator</a>
        </div>
        <div class="rounded-[28px] bg-char text-sand p-8">
            <div class="text-sand/60 text-sm">Example</div>
            <div class="mt-2 text-lg">40 lambs × 38 kg at R2 050 a head</div>
            <div class="mt-4 font-headline text-5xl">R53.95/kg live</div>
            <div class="mt-2 text-sand/60">≈ R112/kg carcass at 48% dressing, vs R{{ number_format($m['series']['lamb_a']['value'], 2) }} this week</div>
        </div>
    </section>
@endif
@endsection

@section('credits')<x-photo-credits :keys="['dorper-ram']" dark />@endsection
