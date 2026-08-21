@props(['products'])

@if ($products->isNotEmpty())
    {{--
        Classic seamless-loop marquee: the same row is rendered twice inside
        one flex container, animated translateX(0) -> translateX(-50%) (the
        existing 'ticker' keyframe, previously defined but unused) — when the
        first copy has fully scrolled off, the second is exactly where the
        first started, so the loop never visibly resets.
    --}}
    <section x-data class="bg-white border-b border-border py-5 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 mb-3">
            <p class="text-[11px] uppercase tracking-[0.2em] text-ink-muted font-semibold">Featured Equipment</p>
        </div>
        <div class="group/marquee relative">
            <div class="flex w-max gap-3 animate-ticker group-hover/marquee:[animation-play-state:paused]">
                @for ($copy = 0; $copy < 2; $copy++)
                    <div class="flex gap-3 pr-3" aria-hidden="{{ $copy === 1 ? 'true' : 'false' }}">
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
                                    class="flex items-center gap-2.5 flex-shrink-0 text-left border border-border rounded-full pl-1.5 pr-4 py-1.5 hover:border-mint/40 hover:bg-canvas transition group">
                                <div class="w-9 h-9 rounded-full bg-canvas border border-border overflow-hidden flex items-center justify-center flex-shrink-0">
                                    @if ($product->thumbnail)
                                        <img src="{{ $product->thumbnail->url }}" alt="{{ $product->title }}" loading="lazy"
                                             onerror="this.style.display='none'"
                                             class="object-contain max-h-full max-w-full p-1">
                                    @else
                                        <x-product-image-fallback :category="$product->category" icon-class="w-4 h-4" />
                                    @endif
                                </div>
                                <span class="min-w-0">
                                    <span class="block text-xs font-semibold text-charcoal truncate max-w-[180px]">{{ $product->title }}</span>
                                    <span class="block text-xs font-mono font-bold text-mint-dark">R{{ number_format($product->retail_price_zar, 0, '', ' ') }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endfor
            </div>
        </div>
    </section>
@endif
