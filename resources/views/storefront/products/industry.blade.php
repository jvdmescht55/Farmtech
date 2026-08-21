@extends('layouts.storefront')

@section('title', $industry->label().' — Farmtech')

@section('content')
    <div class="bg-canvas border-b border-border">
        <div class="max-w-7xl mx-auto px-4 py-10">
            <p class="text-xs font-mono uppercase tracking-widest text-mint-dark mb-2">
                <a href="{{ route('home') }}" class="hover:text-brand-900 transition">Home</a>
                <span class="mx-1 text-ink-muted">/</span>
                <span class="text-brand-900">{{ $industry->label() }}</span>
            </p>
            <div class="flex items-center gap-3 mb-2">
                <span class="w-10 h-10 rounded-xl bg-white border border-border flex items-center justify-center flex-shrink-0 text-brand-900">
                    <x-category-icon :icon="$industry->categories()[0]->icon()" class="w-5 h-5" />
                </span>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900">{{ $industry->label() }}</h1>
            </div>
            <p class="text-ink-secondary max-w-2xl leading-relaxed text-sm">{{ $industry->description() }}</p>

            <div class="flex flex-wrap gap-2 mt-5">
                @foreach ($industry->categories() as $sibling)
                    <a href="{{ route('category.show', $sibling) }}"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 rounded-full border bg-white border-border text-ink-secondary hover:border-brand-900/30 hover:text-brand-900 transition">
                        <x-category-icon :icon="$sibling->icon()" class="w-3.5 h-3.5" />
                        {{ $sibling->shortLabel() }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <x-trending-strip :products="$trending" :sticky="true" />

    <div class="max-w-7xl mx-auto px-4 py-10">
        @include('storefront.products._filter-panel', [
            'targetUrl' => route('industry.show', $industry),
            'products' => $products, 'sort' => $sort, 'inStock' => $inStock,
            'facets' => $facets, 'selectedSpecs' => $selectedSpecs,
        ])

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($products as $i => $product)
                @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
            @empty
                <div class="col-span-full border border-dashed border-border rounded-xl p-8 text-center">
                    <p class="text-brand-900 font-semibold">No {{ $industry->label() }} products match{{ $inStock ? ' the current filters' : ' yet' }}.</p>
                    <p class="text-ink-secondary text-sm mt-1">{{ $inStock ? 'Try clearing the In Stock filter, or browse another category.' : 'Check back soon, or browse another category.' }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection
