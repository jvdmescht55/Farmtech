{{--
    This week's red meat prices (RPO). <x-market-strip /> — dark / light via :dark.
    Shows the four prices farmers ask about most, with the weekly move.
--}}
@props(['dark' => false, 'keys' => ['lamb_a', 'feeder_lamb', 'beef_a', 'weaner']])
@php($m = app(\App\Services\MarketPrices::class)->summary())
@if ($m)
    <div {{ $attributes->merge(['class' => 'rounded-[24px] border '.($dark ? 'bg-white/5 border-white/10 text-sand' : 'bg-white border-hairline')]) }}>
        <div class="flex flex-wrap items-center justify-between gap-2 px-5 sm:px-6 pt-4">
            <div class="text-sm font-medium inline-flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#3F7A3A] animate-pulse"></span>Market prices · week ending {{ \Carbon\Carbon::parse($m['week'])->format('j M') }}</div>
            <span class="text-sm {{ $dark ? 'text-sand/70' : 'text-stone' }}"><a href="{{ route('site.calculator') }}" class="underline">Auction calculator</a> · <a href="{{ route('site.prices') }}" class="underline">All prices →</a></span>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4">
            @foreach ($keys as $k)
                @php($s = $m['series'][$k])
                <a href="{{ route('site.prices') }}#{{ $k }}" class="px-5 sm:px-6 py-4 {{ $loop->index % 2 ? '' : '' }} hover:{{ $dark ? 'bg-white/5' : 'bg-sand-light' }} transition">
                    <div class="text-[13px] {{ $dark ? 'text-sand/60' : 'text-stone' }}">{{ $s['label'] }}</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="font-headline text-3xl">R{{ number_format($s['value'], 2) }}</span><span class="text-xs {{ $dark ? 'text-sand/50' : 'text-stone' }}">/kg</span>
                    </div>
                    @if ($s['week_change'] !== null)
                        <div class="text-xs mt-0.5 {{ $s['week_change'] > 0 ? 'text-[#3F7A3A]' : ($s['week_change'] < 0 ? 'text-[#B0452F]' : ($dark ? 'text-sand/50' : 'text-stone')) }}">{{ $s['week_change'] > 0 ? '▲' : ($s['week_change'] < 0 ? '▼' : '•') }} {{ abs($s['week_change']) }}% this week</div>
                    @endif
                </a>
            @endforeach
        </div>
        <div class="px-5 sm:px-6 pb-3 text-[11px] {{ $dark ? 'text-sand/40' : 'text-stone-light' }}">Source: <a href="{{ \App\Services\MarketPrices::SOURCE_URL }}" target="_blank" rel="noopener" class="underline">RPO weekly carcass prices</a>. National averages, updated weekly.</div>
    </div>
@endif
