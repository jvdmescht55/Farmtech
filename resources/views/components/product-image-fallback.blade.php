@props(['category', 'iconClass' => 'w-10 h-10'])

{{--
    Shown whenever a product has no image (or the URL is broken) instead of a
    generic gray box — the category's own icon (already built, one per
    category) on a subtle blueprint-style dot grid, so it at least signals
    what kind of equipment this is.
--}}
<div {{ $attributes->merge(['class' => 'w-full h-full flex items-center justify-center bg-slate-50 relative overflow-hidden']) }}>
    <svg class="absolute inset-0 w-full h-full text-slate-200" aria-hidden="true">
        <pattern id="fallback-grid-{{ $category->value }}" width="14" height="14" patternUnits="userSpaceOnUse">
            <circle cx="1.5" cy="1.5" r="1.2" fill="currentColor"/>
        </pattern>
        <rect width="100%" height="100%" fill="url(#fallback-grid-{{ $category->value }})"/>
    </svg>
    <span class="relative text-slate-400">
        <x-category-icon :icon="$category->icon()" :class="$iconClass" />
    </span>
</div>
