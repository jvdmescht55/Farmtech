{{-- Included (not a component) — expects $targetUrl, $products, $sort, $inStock, $facets, $selectedSpecs. --}}
@php
    $hasActiveFilters = $inStock || !empty($selectedSpecs);
@endphp

<div x-data="{ filtersOpen: {{ !empty($selectedSpecs) ? 'true' : 'false' }} }" class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <p class="text-sm text-ink-secondary font-mono">
        @if ($products->total() > 0)
            Showing {{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} of {{ $products->total() }} {{ \Illuminate\Support\Str::plural('item', $products->total()) }}
        @else
            0 items
        @endif
    </p>

    <div class="flex items-center gap-2">
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="inline-flex items-center gap-1.5 text-sm font-medium border rounded-full px-4 py-2 transition {{ $hasActiveFilters ? 'bg-mint/10 border-mint/40 text-mint-dark' : 'bg-white border-border text-ink-secondary hover:border-border' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filters
            @if ($hasActiveFilters)<span class="w-1.5 h-1.5 rounded-full bg-mint"></span>@endif
        </button>

        <form method="GET" class="relative">
            @if ($inStock)<input type="hidden" name="in_stock" value="1">@endif
            @foreach ($selectedSpecs as $specKey => $specValues)
                @foreach ((array) $specValues as $specValue)
                    <input type="hidden" name="spec[{{ $specKey }}][]" value="{{ $specValue }}">
                @endforeach
            @endforeach
            <select name="sort" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-border rounded-full pl-4 pr-9 py-2 text-sm font-medium text-charcoal hover:border-border focus:outline-none focus:ring-2 focus:ring-mint/30 cursor-pointer">
                <option value="newest" @selected($sort === 'newest')>Newest Arrivals</option>
                <option value="price_asc" @selected($sort === 'price_asc')>Price: Low to High</option>
                <option value="price_desc" @selected($sort === 'price_desc')>Price: High to Low</option>
                <option value="popularity" @selected($sort === 'popularity')>Most Popular</option>
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </form>
    </div>

    <div x-show="filtersOpen" x-transition x-cloak
         class="w-full border border-border bg-white rounded-xl p-5">
        <form method="GET" class="space-y-5">
            <input type="hidden" name="sort" value="{{ $sort }}">

            <label class="inline-flex items-center gap-2 text-sm text-charcoal cursor-pointer">
                <input type="checkbox" name="in_stock" value="1" onchange="this.form.submit()" {{ $inStock ? 'checked' : '' }}
                       class="rounded border-border text-mint focus:ring-mint/30">
                In Stock Only
            </label>

            @if ($facets->isNotEmpty())
                <div class="grid sm:grid-cols-3 gap-x-6 gap-y-5 pt-1 border-t border-border">
                    @foreach ($facets as $specKey => $values)
                        <div class="pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-charcoal mb-2">{{ $specKey }}</p>
                            <div class="space-y-1.5">
                                @foreach ($values as $value)
                                    <label class="flex items-center gap-2 text-sm text-ink-secondary cursor-pointer">
                                        <input type="checkbox" name="spec[{{ $specKey }}][]" value="{{ $value }}" onchange="this.form.submit()"
                                               {{ in_array($value, (array) ($selectedSpecs[$specKey] ?? []), true) ? 'checked' : '' }}
                                               class="rounded border-border text-mint focus:ring-mint/30">
                                        {{ $value }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($hasActiveFilters)
                <a href="{{ $targetUrl }}?sort={{ $sort }}" class="inline-block text-xs text-ink-muted hover:text-ink-secondary transition">Clear all filters</a>
            @endif
        </form>
    </div>
</div>
