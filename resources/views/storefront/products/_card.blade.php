@php
    $verdict = $product->complianceAudit?->audit_verdict;
    $stampLabel = match ($verdict) {
        'PASS' => 'Verified & Cleared',
        'WARN' => 'AI Checked',
        default => null,
    };
@endphp

<a href="{{ route('products.show', $product) }}"
   @if(isset($delay)) x-reveal.{{ $delay }} @endif
   class="group bg-white border border-slate-200 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-1 hover:border-mint/40 transition-all duration-300">
    <div class="relative aspect-square bg-slate-100 flex items-center justify-center overflow-hidden">
        @if ($product->thumbnail)
            <img src="{{ $product->thumbnail->url }}" alt="{{ $product->title }}" loading="lazy"
                 class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500">
        @else
            <x-product-image-fallback :category="$product->category" />
        @endif

        @if ($stampLabel)
            <span class="verified-stamp absolute top-2 right-2 !w-14 !h-14 !text-[8px] bg-white/90 shadow-sm">
                {{ $stampLabel }}
            </span>
        @endif
    </div>
    <div class="p-4">
        <p class="text-[11px] uppercase tracking-widest text-mint-dark font-semibold">{{ $product->category_label }}</p>
        <h3 class="font-display font-semibold text-sm mt-1 line-clamp-2 text-brand-900">{{ $product->title }}</h3>
        <p class="mt-2 font-mono font-semibold text-brand-900">R{{ number_format($product->retail_price_zar, 2) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">incl. duty &amp; VAT · {{ $product->lead_time_days }}</p>
        @if ($product->isLowStock())
            <p class="text-xs text-alert-dark font-semibold mt-1 flex items-center gap-1.5">
                <span class="relative flex w-1.5 h-1.5">
                    <span class="animate-ping absolute inline-flex w-full h-full rounded-full bg-alert opacity-75"></span>
                    <span class="relative inline-flex rounded-full w-1.5 h-1.5 bg-alert"></span>
                </span>
                Only {{ $product->stock_quantity }} left
            </p>
        @endif
    </div>
</a>
