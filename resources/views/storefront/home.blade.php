@extends('layouts.storefront')

@section('title', 'Farmtech — Professional Equipment, Sourced Globally, Delivered Locally')

@php
    $heroImage = \App\Enums\ProductCategory::heroFallbackImage();
@endphp

@section('content')

    {{-- Hero: exact 50/50 split — text left, one large photograph right. Search lives in the
         header now (see layouts.storefront), not duplicated here. --}}
    <section x-data class="bg-white border-b border-border overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 items-center min-h-[600px] lg:min-h-[700px] py-16 lg:py-0 gap-12">
            <div>
                <p :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" class="text-xs font-mono uppercase tracking-[0.25em] text-mint-dark mb-5">Professional Equipment</p>
                <h1 :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" style="animation-delay:80ms" class="font-display font-extrabold text-4xl sm:text-5xl text-charcoal leading-[1.1] mb-6">
                    Sourced globally.<br>Delivered locally.
                </h1>
                <p :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" style="animation-delay:140ms" class="text-ink-secondary text-base sm:text-lg max-w-md mb-10 leading-relaxed">
                    Verified agricultural and industrial technology for South African businesses and farms.
                </p>

                <div :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" style="animation-delay:200ms" class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('search.index') }}" class="inline-flex items-center gap-2 bg-mint hover:bg-mint-dark text-white text-sm font-semibold px-7 py-3.5 rounded-full transition-all hover:gap-3">
                        Explore equipment
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    <a href="{{ route('how-it-works') }}" class="inline-flex items-center gap-2 border border-border hover:border-charcoal/30 text-charcoal text-sm font-semibold px-7 py-3.5 rounded-full transition">
                        How Farmtech works
                    </a>
                </div>
            </div>

            <div :class="$store.intro.alreadyPlayed ? '' : 'animate-scale-in'" style="animation-delay:120ms" class="relative aspect-[4/3] lg:aspect-auto lg:h-full lg:min-h-[500px] rounded-xl overflow-hidden">
                <img src="{{ $heroImage['url'] }}" alt="Modern South African farm" class="absolute inset-0 w-full h-full object-cover">
            </div>
        </div>
    </section>

    {{-- Trust bar — white, 80-100px tall, items separated by a hairline vertical divider --}}
    <div class="bg-white border-b border-border">
        <div class="max-w-6xl mx-auto px-4 h-20 sm:h-24 flex flex-wrap items-center justify-center divide-x divide-border">
            @foreach ([
                'Verified suppliers',
                'VAT included',
                'Import costs shown',
                'Door-to-door delivery',
            ] as $item)
                <span class="flex items-center gap-2 px-4 sm:px-8 text-xs sm:text-sm font-semibold text-charcoal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-mint-dark flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ strtoupper($item) }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Shop by application — 3x2 large image cards. Replaces the earlier industry-card
         grid at this position per the exact-spec walkthrough (items 11-12); industry-level
         browsing is still reachable via the header's Equipment mega-menu and /industry/*. --}}
    <section id="shop-by-application" class="max-w-7xl mx-auto px-4 py-20 sm:py-24 scroll-mt-20">
        <div class="max-w-2xl mb-12">
            <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">Solutions</p>
            <h2 class="font-display font-bold text-3xl sm:text-4xl text-charcoal">What are you trying to achieve?</h2>
            <p class="text-ink-secondary mt-3">Find equipment by application instead of technical jargon.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach (\App\Support\Applications::all() as $i => $app)
                @php $appImage = \App\Support\Applications::image($app); @endphp
                <a href="{{ \App\Support\Applications::url($app) }}"
                   x-reveal.{{ $i * 90 }}
                   class="group relative rounded-xl overflow-hidden aspect-[4/3] flex items-end p-5">
                    <img src="{{ $appImage['url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover scale-100 group-hover:scale-105 transition-transform duration-700 ease-out">
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-950/90 via-brand-950/30 to-transparent group-hover:from-brand-950/95 transition-colors"></div>
                    <div class="relative">
                        <h3 class="font-display font-bold text-white text-lg uppercase tracking-wide mb-1">{{ $app['label'] }}</h3>
                        <p class="text-white/70 text-sm mb-3">{{ $app['description'] }}</p>
                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-mint-light group-hover:gap-2.5 transition-all">
                            Explore
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Popular equipment — real units-sold data, given its own proper section rather than a bolted-on strip --}}
    @if ($trending->isNotEmpty())
        <section class="bg-canvas border-y border-border py-20 sm:py-24">
            <div class="max-w-7xl mx-auto px-4">
                <div class="max-w-2xl mb-10">
                    <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">Popular Equipment</p>
                    <h2 class="font-display font-bold text-3xl text-charcoal mb-2">Equipment South African buyers are choosing</h2>
                    <p class="text-ink-secondary">Ranked by real units sold — not a promoted placement.</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                    @foreach ($trending as $i => $product)
                        @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Why Farmtech — the verification story as a real selling point --}}
    <section class="max-w-6xl mx-auto px-4 py-20 sm:py-24">
        <div class="max-w-2xl mb-12">
            <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">Why Farmtech</p>
            <h2 class="font-display font-bold text-3xl sm:text-4xl text-charcoal">Why buy through Farmtech?</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-10">
            @foreach ([
                ['n' => '01', 'title' => 'Supplier verified', 'body' => 'We screen suppliers before products are listed — trading history, verified-account status, trade assurance.'],
                ['n' => '02', 'title' => 'Product checked', 'body' => 'Specifications are checked against the relevant SA standard — ISO 11784/11785, ICASA, or NRCS — for the product\'s category.'],
                ['n' => '03', 'title' => 'Import calculated', 'body' => 'VAT, import duty and delivery are calculated per product and already included in the price you see.'],
                ['n' => '04', 'title' => 'Delivered to you', 'body' => 'Door-to-door delivery with real order tracking — see exactly where your equipment is.'],
            ] as $item)
                <div>
                    <span class="font-mono text-sm text-mint-dark font-bold">{{ $item['n'] }}</span>
                    <h3 class="font-display font-bold text-lg text-charcoal mt-1.5 mb-1.5">{{ $item['title'] }}</h3>
                    <p class="text-ink-secondary leading-relaxed text-sm">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
        <a href="{{ route('how-it-works') }}" class="inline-flex items-center gap-1.5 mt-10 text-sm font-semibold text-mint-dark hover:text-mint-darker transition">
            See the full process
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </section>

    {{-- How Farmtech Works — visually prominent, dark section for rhythm against the light sections either side --}}
    <section class="bg-brand-950 py-20 sm:py-24">
        <div class="max-w-6xl mx-auto px-4">
            <div class="max-w-2xl mx-auto text-center mb-14">
                <p class="text-xs uppercase tracking-[0.2em] text-mint-light font-semibold mb-3">How Farmtech Works</p>
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-white">Equipment without the import headache.</h2>
            </div>
            @php
                $howItWorksSteps = [
                    ['n' => '01', 'title' => 'Choose equipment'],
                    ['n' => '02', 'title' => 'See your complete price'],
                    ['n' => '03', 'title' => 'Place your order'],
                    ['n' => '04', 'title' => 'We handle the import'],
                    ['n' => '05', 'title' => 'Delivered to your door'],
                ];
            @endphp
            <div class="flex flex-col sm:flex-row items-stretch sm:items-start justify-between gap-6 sm:gap-2">
                @foreach ($howItWorksSteps as $step)
                    <div class="flex sm:flex-col items-center gap-4 sm:gap-3 sm:text-center flex-1">
                        <span class="font-mono text-2xl font-bold text-mint/50 flex-shrink-0">{{ $step['n'] }}</span>
                        <span class="text-white font-semibold text-sm">{{ $step['title'] }}</span>
                    </div>
                    @if (!$loop->last)
                        <div class="hidden sm:flex flex-shrink-0 items-center text-mint/30 -mx-1 mt-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="text-center mt-12">
                <a href="{{ route('how-it-works') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-mint-light hover:text-white transition">
                    See the full process
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4">
        {{-- Import pricing card — real decomposed numbers from one live listing, never a
             fabricated "vs local retail" figure. Duty is shown as the real per-product rate
             (it varies by HS code), not a universal claim — VAT genuinely is a flat 15% under
             South African law, so that line is the one that's honestly always the same. --}}
        @if ($costSample)
            @php
                $baseZar = $costSample->landed_cost_zar - ($costSample->intl_freight_zar ?? 0) - ($costSample->customs_vat_zar ?? 0) - ($costSample->domestic_delivery_zar ?? 0);
            @endphp
            <section class="my-20 sm:my-24" x-data="{ howOpen: false }" x-reveal>
                <div class="glass-card rounded-xl p-6 sm:p-8 grid lg:grid-cols-[1fr_auto] gap-8 items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-mint-dark mb-2">Your Price</p>
                        <h2 class="font-display font-bold text-xl text-charcoal mb-2">No import surprises.</h2>
                        <p class="text-sm text-ink-secondary leading-relaxed max-w-xl">
                            Using a real listing, <strong>{{ $costSample->title }}</strong>, as an example: import duty is
                            charged at {{ number_format($costSample->customs_duty_rate * 100, 1) }}% for this product's HS
                            code (duty varies by product — it's calculated per item, never a flat universal rate) and VAT
                            at the standard South African rate of {{ number_format($costSample->vat_rate * 100, 0) }}%.
                            Both are already folded into the price below.
                        </p>
                        <button type="button" @click="howOpen = !howOpen" class="inline-flex items-center gap-1 mt-4 text-sm font-semibold text-mint-dark hover:text-mint-darker transition">
                            How is this calculated?
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" :class="howOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <p x-show="howOpen" x-transition x-cloak class="text-xs text-ink-muted leading-relaxed mt-2 max-w-xl">
                            Import charges depend on the product's classification (HS code), its declared value and
                            weight, and applicable South African customs regulations. Farmtech calculates freight, duty
                            and VAT per product before it's ever listed, and folds the total into the single price
                            shown — you never receive a separate customs or clearing-agent invoice after checkout.
                        </p>
                    </div>
                    <div class="flex flex-col gap-2.5 min-w-[260px] font-mono">
                        <div class="flex items-baseline justify-between gap-6 text-sm text-ink-secondary">
                            <span class="font-body">Equipment</span>
                            <span class="font-tabular">R{{ number_format($baseZar, 0, '', ' ') }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 text-sm text-ink-secondary">
                            <span class="font-body">International freight</span>
                            <span class="font-tabular">{{ $costSample->intl_freight_zar ? 'R'.number_format($costSample->intl_freight_zar, 0, '', ' ') : 'Included' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 text-sm text-ink-secondary">
                            <span class="font-body">Import duty &amp; VAT</span>
                            <span class="font-tabular">{{ $costSample->customs_vat_zar ? 'R'.number_format($costSample->customs_vat_zar, 0, '', ' ') : 'Included' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 text-sm text-ink-secondary">
                            <span class="font-body">Delivery</span>
                            <span class="font-tabular">{{ $costSample->domestic_delivery_zar ? 'R'.number_format($costSample->domestic_delivery_zar, 0, '', ' ') : 'Included' }}</span>
                        </div>
                        <div class="scan-divider"></div>
                        <div class="flex items-baseline justify-between gap-6">
                            <span class="text-sm font-semibold text-charcoal font-body">TOTAL</span>
                            <span class="font-tabular text-lg font-bold text-charcoal">R{{ number_format($costSample->retail_price_zar, 0, '', ' ') }}</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Latest approved products --}}
        <section class="mb-20 sm:mb-24">
            <div class="flex items-baseline justify-between mb-6">
                <h2 class="font-display font-bold text-2xl sm:text-3xl text-charcoal">Latest Equipment</h2>
                <span class="text-xs text-ink-secondary font-mono">{{ $featured->count() }} listed</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @forelse ($featured as $i => $product)
                    @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
                @empty
                    <div class="col-span-full border border-dashed border-border rounded-xl p-10 text-center">
                        <p class="text-charcoal font-semibold">No products published yet.</p>
                        <p class="text-ink-secondary text-sm mt-1">Check back soon — new listings go through compliance checks before they appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
