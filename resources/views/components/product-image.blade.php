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

    The x-init check below matters: the browser starts fetching <img src>
    as soon as it parses the tag, independently of Alpine. Alpine itself only
    starts evaluating x-data/attaching @load listeners once its bundled,
    deferred module script runs — which, for a locally-hosted image the
    browser can fetch in a few ms (or one already in cache), can easily be
    AFTER the image's 'load' event already fired. @load only ever fires once,
    so without this check the state stays stuck at 'loading' forever and the
    photo never appears — this was the actual cause of thumbnails silently
    not rendering on the storefront grid pages.
--}}
<div x-data="{ state: '{{ $url ? 'loading' : 'failed' }}' }"
     {{ $attributes->merge(['class' => 'aspect-square bg-canvas border border-border rounded-xl p-4 flex items-center justify-center overflow-hidden relative']) }}>
    @if ($url)
        <div x-show="state === 'loading'" class="absolute inset-4 rounded-lg bg-border animate-pulse"></div>
        <img src="{{ $url }}" alt="{{ $product->title }}" loading="lazy"
             x-show="state === 'loaded'"
             x-init="if ($el.complete && $el.naturalWidth > 0) { state = 'loaded' } else if ($el.complete) { state = 'failed' }"
             @load="state = 'loaded'" x-on:error="state = 'failed'"
             class="object-contain max-h-full max-w-full drop-shadow-sm">
    @endif
    <div x-show="state === 'failed'" class="w-full h-full">
        <x-product-image-fallback :category="$product->category" class="!bg-transparent" />
    </div>
</div>
