@php
    $verdict = $product->complianceAudit?->audit_verdict;
    $complianceLabel = match ($verdict) {
        'PASS' => 'Verified & Cleared',
        'WARN' => 'AI Checked',
        default => null,
    };
@endphp

<a href="{{ route('products.show', $product) }}"
   @if(isset($delay)) x-reveal.{{ $delay }} @endif
   class="group h-full flex flex-col bg-white border border-slate-200 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-1 hover:border-mint/40 transition-all duration-300">
    <div class="flex items-center justify-between gap-1.5 px-3 pt-3">
        <span class="inline-block text-[9px] font-semibold uppercase tracking-wide text-mint-dark bg-mint/10 rounded px-1.5 py-0.5 truncate">{{ $product->category->industry()->badgeLabel() }}</span>
        @if ($complianceLabel)
            <span class="inline-flex items-center gap-1 text-[9px] font-semibold text-emerald-700 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $complianceLabel }}
            </span>
        @endif
    </div>

    <div class="px-3 pt-2">
        <x-product-image :product="$product" class="group-hover:scale-[1.03] transition-transform duration-500" />
    </div>

    <div class="p-4 pt-3 flex flex-col flex-1">
        <h3 class="font-display font-semibold text-sm line-clamp-2 text-brand-900">{{ $product->title }}</h3>
        <p class="mt-2 font-mono font-bold text-brand-900">R{{ number_format($product->retail_price_zar, 2) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">incl. duty &amp; VAT · {{ $product->lead_time_days }}</p>

        <div class="mt-auto pt-3 flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold {{ $product->stock_status === 'in_stock' ? 'text-emerald-700' : 'text-precision-dark' }}">
                <span class="relative flex w-1.5 h-1.5">
                    <span class="animate-ping absolute inline-flex w-full h-full rounded-full opacity-75 {{ $product->stock_status === 'in_stock' ? 'bg-mint' : 'bg-precision' }}"></span>
                    <span class="relative inline-flex rounded-full w-1.5 h-1.5 {{ $product->stock_status === 'in_stock' ? 'bg-mint' : 'bg-precision' }}"></span>
                </span>
                {{ $product->stock_status === 'in_stock' ? 'In Stock' : 'Express Air Import' }}
            </span>
            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-mint-dark group-hover:text-mint-darker group-hover:gap-1.5 transition-all">
                View Specs
                <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </span>
        </div>

        @if ($product->isLowStock())
            <p class="text-xs text-alert-dark font-semibold mt-2">Only {{ $product->stock_quantity }} left</p>
        @endif
    </div>
</a>
