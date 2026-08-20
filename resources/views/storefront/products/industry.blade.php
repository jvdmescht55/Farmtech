@extends('layouts.storefront')

@section('title', $industry->label().' — Farmtech')

@section('content')
    <div class="bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 py-10">
            <p class="text-xs font-mono uppercase tracking-widest text-mint-dark mb-2">
                <a href="{{ route('home') }}" class="hover:text-brand-900 transition">Home</a>
                <span class="mx-1 text-slate-400">/</span>
                <span class="text-brand-900">{{ $industry->label() }}</span>
            </p>
            <div class="flex items-center gap-3 mb-2">
                <span class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 text-brand-900">
                    <x-category-icon :icon="$industry->categories()[0]->icon()" class="w-5 h-5" />
                </span>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900">{{ $industry->label() }}</h1>
            </div>
            <p class="text-slate-600 max-w-2xl leading-relaxed text-sm">{{ $industry->description() }}</p>

            <div class="flex flex-wrap gap-2 mt-5">
                @foreach ($industry->categories() as $sibling)
                    <a href="{{ route('category.show', $sibling) }}"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 rounded-full border bg-white border-slate-200 text-slate-600 hover:border-brand-900/30 hover:text-brand-900 transition">
                        <x-category-icon :icon="$sibling->icon()" class="w-3.5 h-3.5" />
                        {{ $sibling->shortLabel() }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <x-trending-strip :products="$trending" :sticky="true" />

    <div class="max-w-7xl mx-auto px-4 py-10">
        <div x-data="{ filtersOpen: false }" class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <p class="text-sm text-slate-500 font-mono">
                @if ($products->total() > 0)
                    Showing {{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} of {{ $products->total() }} {{ \Illuminate\Support\Str::plural('item', $products->total()) }}
                @else
                    0 items
                @endif
            </p>

            <div class="flex items-center gap-2">
                <button type="button" @click="filtersOpen = !filtersOpen"
                        class="inline-flex items-center gap-1.5 text-sm font-medium border rounded-full px-4 py-2 transition {{ $inStock ? 'bg-mint/10 border-mint/40 text-mint-dark' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Filters
                    @if ($inStock)<span class="w-1.5 h-1.5 rounded-full bg-mint"></span>@endif
                </button>

                <form method="GET" class="relative">
                    @if ($inStock)<input type="hidden" name="in_stock" value="1">@endif
                    <select name="sort" onchange="this.form.submit()"
                            class="appearance-none bg-white border border-slate-200 rounded-full pl-4 pr-9 py-2 text-sm font-medium text-slate-700 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-mint/30 cursor-pointer">
                        <option value="newest" @selected($sort === 'newest')>Newest</option>
                        <option value="price_asc" @selected($sort === 'price_asc')>Price: Low to High</option>
                        <option value="price_desc" @selected($sort === 'price_desc')>Price: High to Low</option>
                        <option value="popularity" @selected($sort === 'popularity')>Popularity</option>
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </form>
            </div>

            <div x-show="filtersOpen" x-transition x-cloak
                 class="w-full border border-slate-200 bg-white rounded-xl p-4">
                <form method="GET" class="flex items-center gap-3">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" name="in_stock" value="1" onchange="this.form.submit()" {{ $inStock ? 'checked' : '' }}
                               class="rounded border-slate-300 text-mint focus:ring-mint/30">
                        In Stock Only
                    </label>
                    @if ($inStock)
                        <a href="{{ route('industry.show', $industry) }}?sort={{ $sort }}" class="text-xs text-slate-400 hover:text-slate-600 transition">Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($products as $i => $product)
                @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
            @empty
                <div class="col-span-full border border-dashed border-slate-300 rounded-2xl p-8 text-center">
                    <p class="text-brand-900 font-semibold">No {{ $industry->label() }} products match{{ $inStock ? ' the current filters' : ' yet' }}.</p>
                    <p class="text-slate-600 text-sm mt-1">{{ $inStock ? 'Try clearing the In Stock filter, or browse another category.' : 'Check back soon, or browse another category.' }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection
