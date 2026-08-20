@extends('layouts.storefront')

@php
    $image = $category->image();
@endphp

@section('title', $category->label().' — Farmtech')

@section('content')
    <div class="relative bg-brand-950 text-white overflow-hidden">
        <img src="{{ $image['url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-25">
        <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/85 to-brand-950/60"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-14">
            <p class="text-xs font-mono uppercase tracking-widest text-mint-light mb-2">
                <a href="{{ route('home') }}" class="hover:text-white transition">Farmtech</a> / {{ $category->shortLabel() }}
            </p>
            <div class="flex items-center gap-3 mb-3">
                <span class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0">
                    <x-category-icon :icon="$category->icon()" class="w-5 h-5" />
                </span>
                <h1 class="font-display font-bold text-3xl sm:text-4xl">{{ $category->label() }}</h1>
            </div>
            <p class="text-slate-300 max-w-2xl leading-relaxed">{{ $category->description() }}</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10">
        <p class="text-sm text-slate-500 mb-6 font-mono">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }}</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($products as $i => $product)
                @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
            @empty
                <div class="col-span-full border border-dashed border-slate-300 rounded-2xl overflow-hidden text-center">
                    <img src="{{ $image['url'] }}" alt="" class="w-full h-40 object-cover opacity-60">
                    <div class="p-8">
                        <p class="text-brand-900 font-semibold">No {{ $category->shortLabel() }} products listed yet.</p>
                        <p class="text-slate-600 text-sm mt-1">Check back soon, or browse another category.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection
