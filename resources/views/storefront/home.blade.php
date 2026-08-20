@extends('layouts.storefront')

@section('title', 'Farmtech — AI-Vetted Agricultural Technology for South African Farms')

@section('content')

    {{-- Split hero --}}
    @if ($heroProducts->isNotEmpty())
        @php($hero = $heroProducts->first())
        <section class="relative bg-brand-950 overflow-hidden">
            <div class="absolute inset-0 bg-grain"></div>
            <div class="relative max-w-7xl mx-auto px-4 py-16 sm:py-24 grid lg:grid-cols-2 gap-10 items-center">
                <div>
                    <span class="inline-flex items-center gap-2 bg-white/10 border border-white/15 text-mint-light text-xs font-semibold uppercase tracking-[0.15em] px-3.5 py-1.5 rounded-full mb-6 animate-reveal-up">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
                        AI-Vetted Before It's Listed
                    </span>
                    <h1 class="font-display font-extrabold text-4xl sm:text-5xl text-white leading-[1.08] mb-5 animate-reveal-up" style="animation-delay:80ms">
                        Agricultural technology, sourced and cleared for South African farms.
                    </h1>
                    <p class="text-slate-300 text-base sm:text-lg max-w-lg mb-8 leading-relaxed animate-reveal-up" style="animation-delay:140ms">
                        Every listing is checked against ISO 11784/11785, ICASA or NRCS requirements before it goes live — with all-in ZAR pricing, so what you see is what you pay.
                    </p>
                    <div class="flex flex-wrap items-center gap-4 animate-reveal-up" style="animation-delay:200ms">
                        <a href="{{ route('products.show', $hero) }}" class="relative inline-flex items-center gap-2 bg-mint hover:bg-mint-dark text-white text-sm font-semibold px-6 py-3.5 rounded-full transition-all hover:gap-3 animate-glow-pulse">
                            Shop the {{ $hero->category_label }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                        <a href="{{ route('search.index') }}" class="text-sm font-semibold text-white/80 hover:text-white transition">Browse all products</a>
                    </div>
                </div>

                <div x-data="{
                        slide: 0,
                        total: {{ $heroProducts->count() }},
                        timer: null,
                        start() { this.timer = setInterval(() => this.next(), 5500); },
                        stop() { clearInterval(this.timer); },
                        next() { this.slide = (this.slide + 1) % this.total; },
                        prev() { this.slide = (this.slide - 1 + this.total) % this.total; },
                    }"
                    x-init="start()" @mouseenter="stop()" @mouseleave="start()"
                    class="relative animate-reveal-up" style="animation-delay:260ms">
                    <div class="relative aspect-[4/3] rounded-2xl overflow-hidden glass-card bg-white/10 shadow-2xl">
                        @foreach ($heroProducts as $i => $product)
                            <a href="{{ route('products.show', $product) }}"
                               x-show="slide === {{ $i }}" @if ($i > 0) x-cloak @endif
                               x-transition:enter="transition ease-out duration-700"
                               x-transition:enter-start="opacity-0 scale-105"
                               x-transition:enter-end="opacity-100 scale-100"
                               class="absolute inset-0 block group">
                                @if ($product->thumbnail)
                                    <img src="{{ $product->thumbnail->url }}" alt="{{ $product->title }}" class="w-full h-full object-cover scale-100 group-hover:scale-105 transition-transform duration-[3000ms] ease-out">
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-brand-950/90 via-brand-950/10 to-transparent"></div>
                                <div class="absolute bottom-0 left-0 right-0 p-5">
                                    <p class="text-white font-display font-semibold text-lg leading-tight mb-1">{{ $product->title }}</p>
                                    <p class="font-mono text-mint-light text-xl font-semibold">R{{ number_format($product->retail_price_zar, 2) }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    @if ($heroProducts->count() > 1)
                        <button @click="prev()" aria-label="Previous" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/15 hover:bg-white/30 hover:scale-110 text-white flex items-center justify-center backdrop-blur transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <button @click="next()" aria-label="Next" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/15 hover:bg-white/30 hover:scale-110 text-white flex items-center justify-center backdrop-blur transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                        <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
                            @foreach ($heroProducts as $i => $product)
                                <button @click="slide = {{ $i }}" :class="slide === {{ $i }} ? 'w-8 bg-mint' : 'w-2 bg-brand-900/20 hover:bg-brand-900/40'" class="h-2 rounded-full transition-all duration-300"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @else
        <section class="relative bg-brand-950 text-white overflow-hidden">
            <img src="{{ \App\Enums\ProductCategory::heroFallbackImage()['url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-25">
            <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/85 to-brand-950/60"></div>
            <div class="relative px-4 py-24 text-center">
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl mb-3 max-w-2xl mx-auto">Agricultural technology, sourced and checked for South African farms.</h1>
                <p class="text-slate-300 max-w-xl mx-auto">Products will appear here once the admin team approves the first listings.</p>
            </div>
        </section>
    @endif

    <x-trending-strip :products="$trending" />

    <div class="max-w-7xl mx-auto px-4">

        {{-- Trust bar --}}
        <section class="grid sm:grid-cols-4 gap-4 {{ $heroProducts->isNotEmpty() ? '-mt-10' : 'mt-10' }} relative z-10 mb-20">
            @foreach ([
                ['icon' => 'shield', 'title' => 'AI Compliance Checked', 'body' => 'Every listing is checked against the relevant SA standard — ISO 11784/11785, ICASA, or NRCS — before it goes live.'],
                ['icon' => 'receipt', 'title' => 'All-In Pricing', 'body' => 'Import duty & 15% VAT are already in the price. Nothing extra to pay on delivery.'],
                ['icon' => 'truck', 'title' => 'Free Express Delivery', 'body' => 'Free Express Door-to-Door Delivery Across South Africa (All Customs & Clearance Handled) — Direct Express air freight, 7–12 business days.'],
                ['icon' => 'lock', 'title' => 'Secure Checkout', 'body' => 'Card and EFT details are handled by PayFast, Ozow or Yoco — Farmtech never stores them.'],
            ] as $i => $item)
                <div x-reveal.{{ $i * 100 }} class="group glass-card rounded-2xl p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-mint/10 text-mint-dark flex items-center justify-center mb-3 group-hover:bg-mint group-hover:text-white transition-colors">
                        @if ($item['icon'] === 'shield')
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
                        @elseif ($item['icon'] === 'receipt')
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2z"/><line x1="9" y1="7" x2="15" y2="7"/><line x1="9" y1="11" x2="15" y2="11"/></svg>
                        @elseif ($item['icon'] === 'lock')
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="7" width="15" height="10"/><path d="M16 10h4l3 3v4h-7z"/><circle cx="5.5" cy="19.5" r="1.5"/><circle cx="18.5" cy="19.5" r="1.5"/></svg>
                        @endif
                    </div>
                    <p class="font-display font-semibold text-brand-900 text-sm mb-1">{{ $item['title'] }}</p>
                    <p class="text-xs text-slate-600 leading-relaxed">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </section>

        {{-- Category tiles --}}
        <section class="mb-20">
            <div class="flex items-baseline justify-between mb-5">
                <h2 class="font-display font-bold text-xl text-brand-900">Shop by Category</h2>
                <span class="text-xs text-slate-500 font-mono">{{ count($categories) }} ranges</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach ($categories as $i => $category)
                    @php($image = $category->image())
                    <a href="{{ route('category.show', $category) }}"
                       x-reveal.{{ $i * 70 }}
                       class="group relative rounded-2xl overflow-hidden aspect-[4/3] flex items-end p-5">
                        <img src="{{ $image['url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-700 ease-out">
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-950/90 via-brand-950/40 to-transparent group-hover:from-brand-950/95 transition-colors"></div>
                        <span class="absolute top-3 left-3 w-8 h-8 rounded-lg bg-white/15 backdrop-blur text-white flex items-center justify-center">
                            <x-category-icon :icon="$category->icon()" class="w-4 h-4" />
                        </span>
                        <div class="relative">
                            <span class="block font-display font-semibold text-white text-sm sm:text-base">{{ $category->shortLabel() }}</span>
                            <span class="flex items-center gap-1 mt-1 text-xs text-mint-light opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">
                                Shop range
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Landed-cost transparency widget — real numbers from one live listing, never a fabricated "vs local retail" figure. --}}
        @if ($costSample)
            <section class="mb-20" x-reveal>
                <div class="glass-card rounded-2xl p-6 sm:p-8 grid lg:grid-cols-[1fr_auto] gap-8 items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-mint-dark mb-2">Landed-Cost Transparency</p>
                        <h2 class="font-display font-bold text-xl text-brand-900 mb-2">See exactly what's in the price — no hidden markup.</h2>
                        <p class="text-sm text-slate-600 leading-relaxed max-w-xl">
                            Using a real listing, <strong>{{ $costSample->title }}</strong>, as an example: import duty is charged at {{ number_format($costSample->customs_duty_rate * 100, 1) }}% and VAT at {{ number_format($costSample->vat_rate * 100, 0) }}% under South African import rules. Both are already folded into the price you see — freight, clearing, duty and VAT included, before Farmtech's margin.
                        </p>
                    </div>
                    <div class="flex flex-col gap-3 min-w-[220px] font-mono">
                        <div class="flex items-baseline justify-between gap-6 text-sm text-slate-600">
                            <span>Landed cost (all-in)</span>
                            <span class="font-tabular">R{{ number_format($costSample->landed_cost_zar, 2) }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 text-sm text-slate-600">
                            <span>Import duty rate</span>
                            <span class="font-tabular">{{ number_format($costSample->customs_duty_rate * 100, 1) }}%</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-6 text-sm text-slate-600">
                            <span>VAT rate</span>
                            <span class="font-tabular">{{ number_format($costSample->vat_rate * 100, 0) }}%</span>
                        </div>
                        <div class="scan-divider"></div>
                        <div class="flex items-baseline justify-between gap-6">
                            <span class="text-sm font-semibold text-brand-900">You pay</span>
                            <span class="font-tabular text-lg font-bold text-brand-900">R{{ number_format($costSample->retail_price_zar, 2) }}</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Latest approved products --}}
        <section class="mb-20">
            <div class="flex items-baseline justify-between mb-6">
                <h2 class="font-display font-bold text-2xl text-brand-900">Latest Approved Products</h2>
                <span class="text-xs text-slate-500 font-mono">{{ $featured->count() }} listed</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @forelse ($featured as $i => $product)
                    @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
                @empty
                    <div class="col-span-full border border-dashed border-slate-300 rounded-2xl p-10 text-center">
                        <p class="text-brand-900 font-semibold">No products published yet.</p>
                        <p class="text-slate-600 text-sm mt-1">Check back soon — new listings go through AI compliance checks before they appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
