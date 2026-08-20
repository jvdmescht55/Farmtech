@props(['product', 'imageUrl' => null])

@php
    $url = $imageUrl ?? $product->thumbnail?->url;
@endphp

<div {{ $attributes->merge(['class' => 'aspect-square bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center justify-center overflow-hidden']) }}>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $product->title }}" loading="lazy" class="object-contain max-h-full max-w-full drop-shadow-sm">
    @else
        <x-product-image-fallback :category="$product->category" class="!bg-transparent" />
    @endif
</div>
