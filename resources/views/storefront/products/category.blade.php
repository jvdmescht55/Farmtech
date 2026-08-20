@extends('layouts.storefront')

@php
    $image = $category->image();
    $siblings = $category->industry()->categories();
@endphp

@section('title', $category->label().' — Farmtech')

@section('content')
    <div class="bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 py-10">
            <p class="text-xs font-mono uppercase tracking-widest text-mint-dark mb-2">
                <a href="{{ route('home') }}" class="hover:text-brand-900 transition">Home</a>
                <span class="mx-1 text-slate-400">/</span>
                <a href="{{ route('home') }}" class="hover:text-brand-900 transition">Categories</a>
                <span class="mx-1 text-slate-400">/</span>
                <span class="text-brand-900">{{ $category->shortLabel() }}</span>
            </p>
            <div class="flex items-center gap-3 mb-2">
                <span class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 text-brand-900">
                    <x-category-icon :icon="$category->icon()" class="w-5 h-5" />
                </span>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900">{{ $category->label() }}</h1>
            </div>
            <p class="text-slate-600 max-w-2xl leading-relaxed text-sm">{{ $category->description() }}</p>

            @if (count($siblings) > 1)
                <div class="flex flex-wrap gap-2 mt-5">
                    @foreach ($siblings as $sibling)
                        <a href="{{ route('category.show', $sibling) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 rounded-full border transition {{ $sibling === $category ? 'bg-brand-900 border-brand-900 text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-brand-900/30 hover:text-brand-900' }}">
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
                <div class="col-span-full border border-dashed border-slate-300 rounded-2xl overflow-hidden text-center">
                    <img src="{{ $image['url'] }}" alt="" class="w-full h-40 object-cover opacity-60">
                    <div class="p-8">
                        <p class="text-brand-900 font-semibold">No {{ $category->shortLabel() }} products match{{ $inStock ? ' the current filters' : ' yet' }}.</p>
                        <p class="text-slate-600 text-sm mt-1">{{ $inStock ? 'Try clearing the In Stock filter, or browse another category.' : 'Check back soon, or browse another category.' }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection
