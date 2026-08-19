@extends('layouts.storefront')

@php
    $image = $category->image();
@endphp

@section('title', $category->label().' — Farmtech')

@section('content')
    <div class="relative bg-field-900 text-paper overflow-hidden">
        <img src="{{ $image['url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30">
        <div class="absolute inset-0 bg-gradient-to-t from-field-900 via-field-900/85 to-field-900/60"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-14">
            <p class="text-xs font-mono uppercase tracking-widest text-tag-light mb-2">
                <a href="{{ route('home') }}" class="hover:text-white transition">Farmtech</a> / {{ $category->shortLabel() }}
            </p>
            <h1 class="font-display font-bold text-3xl sm:text-4xl mb-3">{{ $category->label() }}</h1>
            <p class="text-steel-300 max-w-2xl leading-relaxed">{{ $category->description() }}</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10">
        <p class="text-sm text-steel-700 mb-6 font-mono">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }}</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($products as $i => $product)
                @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
            @empty
                <div class="col-span-full border border-dashed border-steel-200 rounded-xl overflow-hidden text-center">
                    <img src="{{ $image['url'] }}" alt="" class="w-full h-40 object-cover opacity-60">
                    <div class="p-8">
                        <p class="text-field-900 font-semibold">No {{ $category->shortLabel() }} products listed yet.</p>
                        <p class="text-steel-700 text-sm mt-1">Check back soon, or browse another category.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection
