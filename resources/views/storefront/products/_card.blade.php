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
   class="group bg-white border border-steel-300 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-1 hover:border-tag/40 transition-all duration-300">
    <div class="relative aspect-square bg-steel-100 flex items-center justify-center overflow-hidden">
        @if ($product->thumbnail)
            <img src="{{ $product->thumbnail->url }}" alt="{{ $product->title }}" loading="lazy"
                 class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500">
        @else
            <span class="text-steel-500 text-sm">No image</span>
        @endif

        @if ($stampLabel)
            <span class="verified-stamp absolute top-2 right-2 !w-14 !h-14 !text-[8px] bg-white/90 shadow-sm">
                {{ $stampLabel }}
            </span>
        @endif
    </div>
    <div class="p-4">
        <p class="text-[11px] uppercase tracking-widest text-tag font-semibold">{{ $product->category_label }}</p>
        <h3 class="font-display font-semibold text-sm mt-1 line-clamp-2 text-field-950">{{ $product->title }}</h3>
        <p class="mt-2 font-mono font-semibold text-field-900">R{{ number_format($product->retail_price_zar, 2) }}</p>
        <p class="text-xs text-steel-700 mt-0.5">incl. duty &amp; VAT · {{ $product->lead_time_days }}</p>
    </div>
</a>
