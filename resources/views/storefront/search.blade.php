@extends('layouts.storefront')

@section('title', ($query !== '' ? 'Search: '.$query : 'Search').' — Farmtech')

@section('content')
    <x-trending-strip :products="$trending" :sticky="true" />

    <div class="max-w-7xl mx-auto px-4 py-10">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-2">Search results</p>
        <h1 class="font-display font-semibold text-3xl sm:text-4xl text-brand-900 mb-8">
            @if ($query !== '')
                "{{ $query }}"
            @else
                Search Farmtech
            @endif
        </h1>

        @if ($query === '')
            <p class="text-ink-secondary">Type a keyword above — try "134.2 kHz", "rectal probe", "load cell", or a category like "ultrasound".</p>
        @elseif ($products->isEmpty())
            <div class="border border-dashed border-border rounded-xl p-10 text-center">
                <p class="text-brand-900 font-semibold mb-1">No matches for "{{ $query }}"</p>
                <p class="text-ink-secondary text-sm">Try a shorter or more general term — e.g. "scale" instead of "digital scale indicator".</p>
            </div>
        @else
            <div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
                <p class="text-sm text-ink-secondary">{{ $products->total() }} result{{ $products->total() === 1 ? '' : 's' }}</p>
                <form method="GET" class="relative">
                    <input type="hidden" name="q" value="{{ $query }}">
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
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach ($products as $i => $product)
                    @include('storefront.products._card', ['product' => $product, 'delay' => $i * 60])
                @endforeach
            </div>
            <div class="mt-10">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
