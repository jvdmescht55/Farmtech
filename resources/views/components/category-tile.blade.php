@props(['category'])

@php
    $image = $category->image();
@endphp

{{--
    Standardized 16:10 category tile — real Wikimedia photo with a live
    onerror check (same three-state principle as <x-product-image>), falling
    back to the category's own blueprint icon on a dot-grid rather than a
    broken-image icon or empty box.
--}}
<a href="{{ route('category.show', $category) }}"
   x-data="{ failed: false }"
   class="card group relative block overflow-hidden">
    <div class="aspect-[16/10] w-full relative overflow-hidden">
        <img src="{{ $image['url'] }}" alt="{{ $category->label() }}" loading="lazy"
             x-show="!failed" x-on:error="failed = true"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        <div x-show="failed" class="absolute inset-0">
            <x-product-image-fallback :category="$category" icon-class="w-10 h-10" />
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-brand-975/70 via-brand-975/10 to-transparent"></div>
        <span class="absolute bottom-3 left-4 right-4 text-white font-display font-semibold text-sm drop-shadow">{{ $category->label() }}</span>
    </div>
</a>
