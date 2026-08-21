@php
    $verdict = $product->complianceAudit?->audit_verdict;
    // WARN stays visibly softer than PASS — a real, deliberate distinction
    // (see PipelineTest), not collapsed into one identical badge just to
    // match the "Farmtech Verified" branding request.
    $complianceLabel = match ($verdict) {
        'PASS' => 'Farmtech Verified',
        'WARN' => 'Farmtech Checked',
        default => null,
    };

    // One real, useful spec — the highlighted one if the pipeline flagged
    // one, otherwise just the first recorded spec. Never fabricated: no
    // spec rows means no key-spec line, not a placeholder.
    $keySpec = $product->specs->firstWhere('is_highlight', true) ?? $product->specs->first();

    $roundedPrice = 'R'.number_format($product->retail_price_zar, 0, '', ' ');

    // Snapshot for Quick View / Compare — Alpine reads this client-side, so
    // it's the same real, already-fetched data the card renders, not a
    // second query when the buttons are clicked.
    $quickViewPayload = [
        'id' => $product->id,
        'title' => $product->title,
        'category' => $product->category->industry()->badgeLabel(),
        'keySpec' => $keySpec?->spec_value,
        'price' => number_format((float) $product->retail_price_zar, 0, '', ' '),
        'image' => $product->thumbnail?->url,
        'url' => route('products.show', $product),
    ];
@endphp

{{--
    A <button> (the Farmtech Verified badge) can't validly nest inside an <a>
    — real HTML5 restriction, and confirmed live that browsers silently break
    Alpine's click binding on the nested button when it does. This uses the
    standard "stretched link" pattern instead: a real, crawlable <a> covers
    the whole card via absolute positioning, and the badge sits above it
    (higher z-index) as a sibling, not a descendant, so its own click handler
    works correctly and never triggers the card's navigation.
--}}
<div x-data @if(isset($delay)) x-reveal.{{ $delay }} @endif
     class="card group relative h-full flex flex-col overflow-hidden hover:border-mint/40">
    <a href="{{ route('products.show', $product) }}" class="absolute inset-0 z-0" aria-label="{{ $product->title }}"></a>

    <div class="px-3 pt-3 relative pointer-events-none">
        <x-product-image :product="$product" class="group-hover:scale-[1.03] transition-transform duration-500" />

        <div class="absolute top-2 right-2 z-10 flex flex-col gap-1.5 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 lg:focus-within:opacity-100 transition-opacity pointer-events-auto">
            <button type="button" @click.stop="$store.quickView.show(@js($quickViewPayload))"
                    aria-label="Quick view {{ $product->title }}"
                    class="w-8 h-8 rounded-full bg-white/95 border border-border shadow-sm flex items-center justify-center text-ink-secondary hover:text-brand-900 hover:border-mint/40 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button type="button" @click.stop="$store.compare.toggle(@js($quickViewPayload))"
                     :class="$store.compare.has({{ $product->id }}) ? 'bg-mint text-white border-mint' : 'bg-white/95 text-ink-secondary border-border hover:text-brand-900 hover:border-mint/40'"
                    aria-label="Add {{ $product->title }} to compare"
                    class="w-8 h-8 rounded-full border shadow-sm flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="18" rx="1"/><rect x="13" y="7" width="8" height="14" rx="1"/></svg>
            </button>
        </div>
    </div>

    <div class="p-4 pt-3 flex flex-col flex-1 relative">
        <div class="flex items-center justify-between gap-1.5 mb-2">
            <span class="inline-block text-[9px] font-semibold uppercase tracking-wide text-mint-dark bg-mint/10 rounded px-1.5 py-0.5 truncate pointer-events-none">{{ $product->category->industry()->badgeLabel() }}</span>
            @if ($complianceLabel)
                <button type="button" @click="$store.verifiedModal.open = true"
                        class="relative z-10 inline-flex items-center gap-1 text-[9px] font-semibold text-emerald-700 hover:text-emerald-800 flex-shrink-0 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ $complianceLabel }}
                </button>
            @endif
        </div>

        <h3 class="font-display font-semibold text-sm line-clamp-2 text-charcoal pointer-events-none">{{ $product->title }}</h3>

        @if ($keySpec)
            <p class="text-xs font-mono text-ink-secondary mt-1 pointer-events-none">{{ $keySpec->spec_value }}</p>
        @endif

        <p class="mt-2 font-mono font-bold text-lg text-charcoal pointer-events-none">{{ $roundedPrice }}</p>
        <p class="text-[11px] font-semibold text-mint-dark -mt-0.5 pointer-events-none">All-In Landed Pricing &middot; R0 Extra At Door</p>

        <div class="mt-2.5 space-y-1 text-[11px] text-ink-secondary pointer-events-none">
            <p class="flex items-center gap-1.5"><span class="text-mint-dark">✓</span> VAT &amp; duty included</p>
            <p class="flex items-center gap-1.5">⚡ Express Air-Import &middot; {{ $product->lead_time_days }}</p>
        </div>

        <div class="mt-auto pt-3 flex items-center justify-between gap-2 pointer-events-none">
            <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold {{ $product->stock_status === 'in_stock' ? 'text-emerald-700' : 'text-precision-dark' }}">
                <span class="relative flex w-1.5 h-1.5">
                    <span class="animate-ping absolute inline-flex w-full h-full rounded-full opacity-75 {{ $product->stock_status === 'in_stock' ? 'bg-mint' : 'bg-precision' }}"></span>
                    <span class="relative inline-flex rounded-full w-1.5 h-1.5 {{ $product->stock_status === 'in_stock' ? 'bg-mint' : 'bg-precision' }}"></span>
                </span>
                {{ $product->stock_status === 'in_stock' ? 'In Stock' : 'Express Air Import' }}
            </span>
            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-mint-dark group-hover:text-mint-darker group-hover:gap-1.5 transition-all">
                View equipment
                <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </span>
        </div>

        @if ($product->isLowStock())
            <p class="text-xs text-alert-dark font-semibold mt-2 pointer-events-none">Only {{ $product->stock_quantity }} left</p>
        @endif
    </div>
</div>
