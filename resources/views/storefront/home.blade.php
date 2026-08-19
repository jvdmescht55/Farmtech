@extends('layouts.storefront')

@section('title', 'Farmtech — AI-Vetted Agricultural Technology for South African Farms')

@section('content')

    {{-- Featured spot slider --}}
    @if ($heroProducts->isNotEmpty())
        <section x-data="{
                slide: 0,
                total: {{ $heroProducts->count() }},
                timer: null,
                start() { this.timer = setInterval(() => this.next(), 5500); },
                stop() { clearInterval(this.timer); },
                next() { this.slide = (this.slide + 1) % this.total; },
                prev() { this.slide = (this.slide - 1 + this.total) % this.total; },
            }"
            x-init="start()"
            @mouseenter="stop()" @mouseleave="start()"
            class="relative bg-field-950 overflow-hidden">
            <div class="relative h-[420px] sm:h-[500px]">
                @foreach ($heroProducts as $i => $product)
                    <a href="{{ route('products.show', $product) }}"
                       x-show="slide === {{ $i }}"
                       @if ($i > 0) x-cloak @endif
                       x-transition:enter="transition ease-out duration-700"
                       x-transition:enter-start="opacity-0 scale-105"
                       x-transition:enter-end="opacity-100 scale-100"
                       x-transition:leave="transition ease-in duration-300"
                       x-transition:leave-start="opacity-100"
                       x-transition:leave-end="opacity-0"
                       class="absolute inset-0 block group">
                        <div class="absolute inset-0">
                            @if ($product->thumbnail)
                                <img src="{{ $product->thumbnail->url }}" alt="" class="w-full h-full object-cover opacity-40 scale-100 group-hover:scale-105 transition-transform duration-[4000ms] ease-out">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-field-950 via-field-950/70 to-field-950/20"></div>
                            <div class="absolute inset-0 bg-grain"></div>
                        </div>
                        <div class="relative h-full max-w-7xl mx-auto px-4 flex flex-col justify-end pb-16 sm:pb-20">
                            <p class="text-tag font-mono text-xs sm:text-sm uppercase tracking-[0.2em] mb-3 animate-reveal-up">Featured · {{ $product->category_label }}</p>
                            <h2 class="font-display font-bold text-3xl sm:text-5xl text-paper max-w-2xl leading-tight mb-4 animate-reveal-up" style="animation-delay:80ms">{{ $product->title }}</h2>
                            <div class="flex items-center gap-4 flex-wrap animate-reveal-up" style="animation-delay:160ms">
                                <span class="font-mono text-xl sm:text-2xl text-paper font-semibold">R{{ number_format($product->retail_price_zar, 2) }}</span>
                                <span class="text-steel-300 text-sm">incl. duty &amp; VAT</span>
                                <span class="inline-flex items-center gap-2 bg-tag text-white text-sm font-semibold px-5 py-2 rounded-full group-hover:bg-tag-dark group-hover:gap-3 transition-all">
                                    View product
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($heroProducts->count() > 1)
                <button @click="prev()" aria-label="Previous" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 hover:scale-110 text-white flex items-center justify-center backdrop-blur transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button @click="next()" aria-label="Next" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 hover:scale-110 text-white flex items-center justify-center backdrop-blur transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
                    @foreach ($heroProducts as $i => $product)
                        <button @click="slide = {{ $i }}" :class="slide === {{ $i }} ? 'w-8 bg-tag' : 'w-2 bg-white/40 hover:bg-white/70'" class="h-2 rounded-full transition-all duration-300"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="relative bg-field-950 text-paper overflow-hidden">
            <img src="{{ \App\Enums\ProductCategory::heroFallbackImage()['url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-35">
            <div class="absolute inset-0 bg-gradient-to-t from-field-950 via-field-950/80 to-field-950/50"></div>
            <div class="relative px-4 py-24 text-center">
                <h1 class="font-display font-bold text-3xl sm:text-4xl mb-3 max-w-2xl mx-auto">Agricultural technology, sourced and checked for South African farms.</h1>
                <p class="text-steel-300 max-w-xl mx-auto">Products will appear here once the admin team approves the first listings.</p>
            </div>
        </section>
    @endif

    <div class="max-w-7xl mx-auto px-4">

        {{-- Trust bar --}}
        <section class="grid sm:grid-cols-3 gap-4 -mt-8 relative z-10 mb-20">
            @foreach ([
                ['icon' => 'shield', 'title' => 'AI Compliance Checked', 'body' => 'Every listing is checked against the relevant SA standard — ISO 11784/11785, ICASA, or NRCS — before it goes live.'],
                ['icon' => 'receipt', 'title' => 'All-In Pricing', 'body' => 'Import duty & 15% VAT are already in the price. Nothing extra to pay on delivery.'],
                ['icon' => 'truck', 'title' => 'Tracked Delivery', 'body' => 'Direct Express air freight, door to door, in 7–12 business days.'],
            ] as $i => $item)
                <div x-reveal.{{ $i * 100 }} class="group bg-white border border-steel-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-tag/10 text-tag flex items-center justify-center mb-3 group-hover:bg-tag group-hover:text-white transition-colors">
                        @if ($item['icon'] === 'shield')
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
                        @elseif ($item['icon'] === 'receipt')
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2z"/><line x1="9" y1="7" x2="15" y2="7"/><line x1="9" y1="11" x2="15" y2="11"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="7" width="15" height="10"/><path d="M16 10h4l3 3v4h-7z"/><circle cx="5.5" cy="19.5" r="1.5"/><circle cx="18.5" cy="19.5" r="1.5"/></svg>
                        @endif
                    </div>
                    <p class="font-display font-semibold text-field-950 text-sm mb-1">{{ $item['title'] }}</p>
                    <p class="text-xs text-steel-700 leading-relaxed">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </section>

        {{-- Category tiles --}}
        <section class="mb-20">
            <div class="flex items-baseline justify-between mb-5">
                <h2 class="font-display font-bold text-xl text-field-950">Shop by Category</h2>
                <span class="text-xs text-steel-500 font-mono">{{ count($categories) }} ranges</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach ($categories as $i => $category)
                    @php($image = $category->image())
                    <a href="{{ route('category.show', $category) }}"
                       x-reveal.{{ $i * 70 }}
                       class="group relative rounded-xl overflow-hidden aspect-[4/3] flex items-end p-5">
                        <img src="{{ $image['url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-700 ease-out">
                        <div class="absolute inset-0 bg-gradient-to-t from-field-950/90 via-field-950/40 to-transparent group-hover:from-field-950/95 transition-colors"></div>
                        <div class="relative">
                            <span class="block font-display font-semibold text-paper text-sm sm:text-base">{{ $category->shortLabel() }}</span>
                            <span class="flex items-center gap-1 mt-1 text-xs text-tag-light opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">
                                Shop range
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Latest approved products --}}
        <section class="mb-20">
            <div class="flex items-baseline justify-between mb-6">
                <h2 class="font-display font-bold text-2xl text-field-950">Latest Approved Products</h2>
                <span class="text-xs text-steel-500 font-mono">{{ $featured->count() }} listed</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @forelse ($featured as $i => $product)
                    @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
                @empty
                    <div class="col-span-full border border-dashed border-steel-200 rounded-xl p-10 text-center">
                        <p class="text-field-900 font-semibold">No products published yet.</p>
                        <p class="text-steel-700 text-sm mt-1">Check back soon — new listings go through AI compliance checks before they appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
