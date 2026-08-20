@props(['product', 'imageUrl' => null])

@php
    $url = $imageUrl ?? $product->thumbnail?->url;
@endphp

{{--
    Three real states — loading (skeleton shimmer), loaded (the actual photo),
    failed (the same category-icon fallback used when there's no image record
    at all). A stored thumbnail URL can still 404 at request time (broken
    supplier CDN link, moved file), so this checks for that live via onerror
    rather than trusting the DB record — the one thing this must never do is
    fall through to the browser's own broken-image icon.
--}}
<div x-data="{ state: '{{ $url ? 'loading' : 'failed' }}' }"
     {{ $attributes->merge(['class' => 'aspect-square bg-slate-50 border border-border rounded-xl p-4 flex items-center justify-center overflow-hidden relative']) }}>
    @if ($url)
        <div x-show="state === 'loading'" class="absolute inset-4 rounded-lg bg-slate-200 animate-pulse"></div>
        <img src="{{ $url }}" alt="{{ $product->title }}" loading="lazy"
             x-show="state === 'loaded'"
             @load="state = 'loaded'" x-on:error="state = 'failed'"
             class="object-contain max-h-full max-w-full drop-shadow-sm">
    @endif
    <div x-show="state === 'failed'" class="w-full h-full">
        <x-product-image-fallback :category="$product->category" class="!bg-transparent" />
    </div>
</div>
