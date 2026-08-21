@props(['products'])

@if ($products->isNotEmpty())
    {{--
        Classic seamless-loop marquee: the same row is rendered twice inside
        one flex container, animated translateX(0) -> translateX(-50%) (the
        existing 'ticker' keyframe, previously defined but unused) — when the
        first copy has fully scrolled off, the second is exactly where the
        first started, so the loop never visibly resets.
    --}}
    <section x-data class="bg-white border-b border-border py-10 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 mb-5">
            <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold">Featured Innovation &amp; Tech</p>
        </div>
        <div class="group/marquee relative">
            <div class="flex w-max gap-4 animate-ticker group-hover/marquee:[animation-play-state:paused]">
                @for ($copy = 0; $copy < 2; $copy++)
                    <div class="flex gap-4 pr-4" aria-hidden="{{ $copy === 1 ? 'true' : 'false' }}">
                        @foreach ($products as $product)
                            @php
                                $marqueeSpec = $product->specs->firstWhere('is_highlight', true) ?? $product->specs->first();
                                $marqueePayload = [
                                    'id' => $product->id,
                                    'title' => $product->title,
                                    'category' => $product->category->industry()->badgeLabel(),
                                    'keySpec' => $marqueeSpec?->spec_value,
                                    'price' => number_format((float) $product->retail_price_zar, 0, '', ' '),
                                    'image' => $product->thumbnail?->url,
                                    'url' => route('products.show', $product),
                                ];
                            @endphp
                            <button type="button" @click="$store.quickView.show(@js($marqueePayload))"
                                    tabindex="{{ $copy === 1 ? '-1' : '0' }}"
                                    class="card w-56 flex-shrink-0 text-left p-3 group">
                                <div class="aspect-square bg-canvas border border-border rounded-lg overflow-hidden flex items-center justify-center relative">
                                    @if ($product->thumbnail)
                                        <img src="{{ $product->thumbnail->url }}" alt="{{ $product->title }}" loading="lazy"
                                             onerror="this.style.display='none'"
                                             class="object-contain max-h-full max-w-full p-3 group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <x-product-image-fallback :category="$product->category" icon-class="w-8 h-8" />
                                    @endif
                                </div>
                                <span class="inline-block text-[9px] font-semibold uppercase tracking-wide text-mint-dark bg-mint/10 rounded px-1.5 py-0.5 mt-2.5 truncate max-w-full">{{ $product->category->industry()->badgeLabel() }}</span>
                                <p class="font-display font-semibold text-sm text-charcoal mt-1.5 line-clamp-2">{{ $product->title }}</p>
                                <p class="font-mono font-bold text-charcoal mt-1.5">R{{ number_format($product->retail_price_zar, 0, '', ' ') }}</p>
                                <p class="text-[10px] text-ink-muted">VAT &amp; Express Delivery Included</p>
                            </button>
                        @endforeach
                    </div>
                @endfor
            </div>
        </div>
    </section>
@endif
