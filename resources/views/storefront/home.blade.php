@extends('layouts.storefront')

@section('title', 'Farmtech — Professional Equipment, Sourced Globally, Delivered Locally')

@php
    $heroImage = \App\Enums\ProductCategory::heroFallbackImage();
    $whatsapp = \App\Models\Setting::get('support_whatsapp', '');
    $whatsappUrl = $whatsapp ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $whatsapp) : null;
@endphp

@section('content')

    {{-- Hero: exact 50/50 split — text left, one large photograph right. Search lives in the
         header now (see layouts.storefront), not duplicated here. --}}
    <section x-data class="bg-white border-b border-border overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 grid lg:grid-cols-2 items-center min-h-[600px] lg:min-h-[700px] py-16 lg:py-0 gap-12">
            <div>
                <p :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" class="text-xs font-mono uppercase tracking-[0.25em] text-mint-dark mb-5">Direct Farm-Tech Procurement</p>
                <h1 :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" style="animation-delay:80ms" class="font-display font-bold text-4xl sm:text-5xl lg:text-6xl text-charcoal leading-[1.08] mb-6">
                    Specialist Farm &amp; Industrial Tech.<br><span class="italic font-medium text-brand-900">Direct from Tier-1 Manufacturers to Your Farm Gate.</span>
                </h1>
                <p :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" style="animation-delay:140ms" class="text-ink-secondary text-base sm:text-lg max-w-md mb-10 leading-relaxed">
                    We source, vet, and land high-precision livestock, solar, telemetry, and security equipment directly from global manufacturers — cutting out heavy retail markups while backing everything locally.
                </p>

                <div :class="$store.intro.alreadyPlayed ? '' : 'animate-reveal-up'" style="animation-delay:200ms" class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('search.index') }}" class="inline-flex items-center gap-2 bg-mint hover:bg-mint-dark text-white text-sm font-semibold px-7 py-3.5 rounded-full transition-all hover:gap-3">
                        Browse Vetted Equipment
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    @if ($whatsappUrl ?? null)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 border border-border hover:border-charcoal/30 text-charcoal text-sm font-semibold px-7 py-3.5 rounded-full transition">
                            Talk to Technical Service
                        </a>
                    @else
                        <a href="{{ route('equipment.index') }}" class="inline-flex items-center gap-2 border border-border hover:border-charcoal/30 text-charcoal text-sm font-semibold px-7 py-3.5 rounded-full transition">
                            Talk to Technical Service
                        </a>
                    @endif
                </div>
            </div>

            <div :class="$store.intro.alreadyPlayed ? '' : 'animate-scale-in'" style="animation-delay:120ms" class="relative aspect-[4/3] lg:aspect-auto lg:h-full lg:min-h-[500px] rounded-xl overflow-hidden">
                <img src="{{ $heroImage['url'] }}" alt="Agricultural drone monitoring crop rows" class="absolute inset-0 w-full h-full object-cover animate-ken-burns">
                <div class="absolute inset-0 ring-1 ring-inset ring-black/5 rounded-xl pointer-events-none"></div>
            </div>
        </div>
    </section>

    {{-- 4-Pillar trust grid — the core value propositions, stated as concrete
         cards rather than a thin one-line ribbon. --}}
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 py-14 sm:py-16">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['icon' => '🛡️', 'title' => '1-Year Local Warranty', 'body' => 'Fast South African replacement swap-outs with zero overseas return hassles.'],
                    ['icon' => '📦', 'title' => '100% Door-to-Farm Logistics', 'body' => 'All import duties, SARS customs clearance, and courier delivery fully included.'],
                    ['icon' => '⚡', 'title' => 'Vetted for African Conditions', 'body' => 'Compatible with 220V/50Hz mains, 12V/24V off-grid solar, and harsh veld environments.'],
                    ['icon' => '💬', 'title' => 'Dedicated Technical Support', 'body' => 'Direct setup, calibration, and wiring guidance via WhatsApp and phone.'],
                ] as $pillar)
                    <div class="border border-border rounded-xl p-5 hover:border-mint/40 hover:shadow-sm transition-all">
                        <span class="text-2xl">{{ $pillar['icon'] }}</span>
                        <h3 class="font-display font-bold text-sm text-charcoal mt-3 mb-1.5">{{ $pillar['title'] }}</h3>
                        <p class="text-ink-secondary text-xs leading-relaxed">{{ $pillar['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <x-featured-marquee :products="$marqueeProducts" />

    {{-- Featured Farm Tech — top-scoring listings by the real curation algorithm
         (margin, compactness, photo/description richness, reviews, verified-supplier
         trust, units sold, click-throughs — see Product::computeFeaturedScore() and
         `products:rank-featured`), never a hand-picked or fabricated "editor's pick". --}}
    @if ($featured->isNotEmpty())
        <section class="bg-white py-20 sm:py-24">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-wrap items-end justify-between gap-4 mb-10">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">Featured Farm Tech</p>
                        <h2 class="font-display font-bold text-3xl text-charcoal mb-2">Top equipment for South African farms</h2>
                        <p class="text-ink-secondary">Ranked on real margin, media quality, reviews, and verified-supplier trust &mdash; not a promoted placement.</p>
                    </div>
                    <a href="{{ route('equipment.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-mint-dark hover:text-brand-900 transition flex-shrink-0">
                        View all equipment
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($featured->take(8) as $i => $product)
                        @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Shop by application — large image cards, 3 per row. Replaces the earlier
         industry-card grid at this position per the exact-spec walkthrough (items
         11-12); industry-level browsing is still reachable via the header's
         Equipment mega-menu and /industry/*. --}}
    <section id="shop-by-application" class="bg-white scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 py-20 sm:py-24">
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
                    <img src="{{ $appImage['url'] }}" alt="{{ $app['label'] }}" class="absolute inset-0 w-full h-full object-cover scale-100 group-hover:scale-105 transition-transform duration-700 ease-out">
                    {{-- Full-height scrim (darkest at the bottom where the text sits, fading to
                         nothing at the top) so white text/button stay crisp and readable against
                         any photo, without fully washing the image out. --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent group-hover:from-black/85 transition-colors"></div>
                    <div class="relative">
                        <h3 class="font-display font-bold text-white text-lg uppercase tracking-wide mb-1 [text-shadow:0_1px_3px_rgba(0,0,0,0.4)]">{{ $app['label'] }}</h3>
                        <p class="text-white/85 text-sm mb-3 [text-shadow:0_1px_2px_rgba(0,0,0,0.4)]">{{ $app['description'] }}</p>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-white/15 backdrop-blur-sm border border-white/25 rounded-full px-3 py-1.5 group-hover:bg-white/25 transition-all">
                            Explore
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
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

    {{-- Why Farmtech + How It Works, merged into one flow — was two consecutive step
         sections telling overlapping stories (verification vs. process); this is the
         single real sequence a buyer actually goes through. --}}
    <section class="relative bg-brand-950 py-20 sm:py-24 overflow-hidden">
        <div class="absolute inset-0 bg-grain pointer-events-none"></div>
        <div class="relative max-w-6xl mx-auto px-4">
            <div class="max-w-2xl mx-auto text-center mb-14">
                <p class="text-xs uppercase tracking-[0.2em] text-mint-light font-semibold mb-3">Why Farmtech</p>
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-white">Equipment without the import headache.</h2>
            </div>
            @php
                $processSteps = [
                    ['n' => '01', 'title' => 'Select verified equipment', 'body' => 'Every listing is supplier-screened and specification-checked against the relevant SA standard — ISO 11784/11785, ICASA, or NRCS — before it goes live.'],
                    ['n' => '02', 'title' => 'See your complete price', 'body' => 'VAT, import duty and delivery are calculated per product and already included — the price you see is the price you pay.'],
                    ['n' => '03', 'title' => 'We handle customs clearance', 'body' => 'Place your order and Farmtech manages the import end to end — no separate customs bill, no clearing-agent surprises.'],
                    ['n' => '04', 'title' => 'Delivered to your gate', 'body' => 'Door-to-door delivery with real order tracking, so you always know exactly where your equipment is.'],
                ];
            @endphp
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-10">
                @foreach ($processSteps as $step)
                    <div>
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-mint/15 border border-mint/30 text-mint-light font-mono text-sm font-bold mb-3">{{ $step['n'] }}</span>
                        <h3 class="font-display font-bold text-lg text-white mb-1.5">{{ $step['title'] }}</h3>
                        <p class="text-white/70 leading-relaxed text-sm">{{ $step['body'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-14">
                <a href="{{ route('how-it-works') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-mint-light hover:text-white transition">
                    See the full process
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- Import pricing card — real decomposed numbers from one live listing, never a
         fabricated "vs local retail" figure. Duty is shown as the real per-product rate
         (it varies by HS code), not a universal claim — VAT genuinely is a flat 15% under
         South African law, so that line is the one that's honestly always the same.
         A full section with its own background (not a floating card in a bare div), so it
         reads as part of the page's flow rather than dropped between unrelated blocks. --}}
    @if ($costSample)
        @php
            $baseZar = $costSample->landed_cost_zar - ($costSample->intl_freight_zar ?? 0) - ($costSample->customs_vat_zar ?? 0) - ($costSample->domestic_delivery_zar ?? 0);
        @endphp
        <section class="bg-white border-b border-border py-20 sm:py-24" x-data="{ howOpen: false }" x-reveal>
            <div class="max-w-7xl mx-auto px-4">
                <div class="rounded-xl border border-border p-6 sm:p-8 grid lg:grid-cols-[1fr_auto] gap-8 items-center">
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
            </div>
        </section>
    @endif

    {{-- Latest approved products --}}
    <section class="py-20 sm:py-24">
        <div class="max-w-7xl mx-auto px-4">
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
        </div>
    </section>
@endsection
