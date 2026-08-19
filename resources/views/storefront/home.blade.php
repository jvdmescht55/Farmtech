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
                       class="absolute inset-0 block">
                        <div class="absolute inset-0">
                            @if ($product->thumbnail)
                                <img src="{{ $product->thumbnail->url }}" alt="" class="w-full h-full object-cover opacity-40">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-field-950 via-field-950/70 to-field-950/20"></div>
                            <div class="absolute inset-0 bg-grain"></div>
                        </div>
                        <div class="relative h-full max-w-7xl mx-auto px-4 flex flex-col justify-end pb-16 sm:pb-20">
                            <p class="text-tag font-mono text-xs sm:text-sm uppercase tracking-[0.2em] mb-3">Featured · {{ $product->category_label }}</p>
                            <h2 class="font-display font-bold text-3xl sm:text-5xl text-paper max-w-2xl leading-tight mb-4">{{ $product->title }}</h2>
                            <div class="flex items-center gap-4 flex-wrap">
                                <span class="font-mono text-xl sm:text-2xl text-paper font-semibold">R{{ number_format($product->retail_price_zar, 2) }}</span>
                                <span class="text-steel-300 text-sm">incl. duty &amp; VAT</span>
                                <span class="inline-flex items-center gap-2 bg-tag text-white text-sm font-semibold px-5 py-2 rounded-full group-hover:bg-tag-dark">
                                    View product
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($heroProducts->count() > 1)
                <button @click="prev()" aria-label="Previous" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button @click="next()" aria-label="Next" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
                    @foreach ($heroProducts as $i => $product)
                        <button @click="slide = {{ $i }}" :class="slide === {{ $i }} ? 'w-8 bg-tag' : 'w-2 bg-white/40'" class="h-2 rounded-full transition-all duration-300"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="bg-field-900 text-paper px-4 py-20 text-center">
            <h1 class="font-display font-bold text-3xl sm:text-4xl mb-3">Compliance-checked livestock tech, imported for South Africa.</h1>
            <p class="text-steel-300 max-w-xl mx-auto">Products will appear here once the admin team approves the first listings.</p>
        </section>
    @endif

    <div class="max-w-7xl mx-auto px-4">

        {{-- Trust bar --}}
        <section class="grid sm:grid-cols-3 gap-4 -mt-8 relative z-10 mb-16">
            @foreach ([
                ['icon' => 'shield', 'title' => 'AI Compliance Checked', 'body' => 'Every listing is checked against ISO 11784/11785, ICASA & SARS rules before it goes live.'],
                ['icon' => 'receipt', 'title' => 'All-In Pricing', 'body' => 'Import duty & 15% VAT are already in the price. Nothing extra to pay on delivery.'],
                ['icon' => 'truck', 'title' => 'Tracked Delivery', 'body' => 'Direct Express air freight, door to door, in 7–12 business days.'],
            ] as $i => $item)
                <div x-reveal.{{ $i * 100 }} class="bg-white border border-steel-300 rounded-xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-lg bg-tag/10 text-tag flex items-center justify-center mb-3">
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
        <section class="mb-16">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($categories as $i => $category)
                    <a href="{{ route('category.show', $category) }}"
                       x-reveal.{{ $i * 80 }}
                       class="group relative bg-field-900 rounded-xl p-6 text-center overflow-hidden hover:bg-field-800 transition-colors">
                        <span class="relative font-display font-semibold text-paper text-sm">
                            {{ match($category) {
                                'scales' => 'Livestock Scales',
                                'ultrasound' => 'Ultrasound Scanners',
                                'rfid' => 'RFID & Ear Tagging',
                                'accessories' => 'Probes & Accessories',
                            } }}
                        </span>
                        <span class="block mt-2 text-xs text-tag-light group-hover:translate-x-1 transition-transform inline-block">Shop range →</span>
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
                    <p class="text-steel-500 col-span-4">No products published yet — check back soon.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
