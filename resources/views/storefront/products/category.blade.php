@extends('layouts.storefront')

@php
    $image = $category->image();
    $siblings = $category->industry()->categories();
@endphp

@section('title', $category->label().' — Farmtech')

@section('content')
    <div class="bg-canvas border-b border-border">
        <div class="max-w-7xl mx-auto px-4 py-12 sm:py-14">
            <p class="text-xs font-mono uppercase tracking-widest text-mint-dark mb-3">
                <a href="{{ route('home') }}" class="hover:text-brand-900 transition">Home</a>
                <span class="mx-1 text-ink-muted">/</span>
                <a href="{{ route('home') }}" class="hover:text-brand-900 transition">Categories</a>
                <span class="mx-1 text-ink-muted">/</span>
                <span class="text-brand-900">{{ $category->shortLabel() }}</span>
            </p>
            <div class="flex items-center gap-3 mb-3">
                <span class="w-11 h-11 rounded-xl bg-white border border-border flex items-center justify-center flex-shrink-0 text-brand-900">
                    <x-category-icon :icon="$category->icon()" class="w-5 h-5" />
                </span>
                <h1 class="font-display font-semibold text-3xl sm:text-4xl text-brand-900">{{ $category->label() }}</h1>
            </div>
            <p class="text-ink-secondary max-w-2xl leading-relaxed text-sm">{{ $category->description() }}</p>

            @if (count($siblings) > 1)
                <div class="flex flex-wrap gap-2 mt-5">
                    @foreach ($siblings as $sibling)
                        <a href="{{ route('category.show', $sibling) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 rounded-full border transition {{ $sibling === $category ? 'bg-brand-900 border-brand-900 text-white' : 'bg-white border-border text-ink-secondary hover:border-brand-900/30 hover:text-brand-900' }}">
                            <x-category-icon :icon="$sibling->icon()" class="w-3.5 h-3.5" />
                            {{ $sibling->shortLabel() }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-trending-strip :products="$trending" :sticky="true" />

    <div class="max-w-7xl mx-auto px-4 py-10">
        @include('storefront.products._filter-panel', [
            'targetUrl' => route('category.show', $category),
            'products' => $products, 'sort' => $sort, 'inStock' => $inStock,
            'facets' => $facets, 'selectedSpecs' => $selectedSpecs,
        ])

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($products as $i => $product)
                @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
            @empty
                <div class="col-span-full border border-dashed border-border rounded-xl overflow-hidden text-center">
                    <img src="{{ $image['url'] }}" alt="" class="w-full h-40 object-cover opacity-60">
                    <div class="p-8">
                        <p class="text-brand-900 font-semibold">No {{ $category->shortLabel() }} products match{{ $inStock ? ' the current filters' : ' yet' }}.</p>
                        <p class="text-ink-secondary text-sm mt-1">{{ $inStock ? 'Try clearing the In Stock filter, or browse another category.' : 'Check back soon, or browse another category.' }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>

    @if ($crossDomain->isNotEmpty())
        <section class="bg-canvas border-t border-border py-14">
            <div class="max-w-7xl mx-auto px-4">
                <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-2">Also Sourced by Commercial Buyers</p>
                <h2 class="font-display font-bold text-xl text-charcoal mb-6">Popular equipment from other domains</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($crossDomain as $i => $product)
                        @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
