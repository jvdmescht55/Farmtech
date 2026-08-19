@extends('layouts.storefront')

@php
    $categoryLabel = match($category) {
        'scales' => 'Livestock Scales & Load Cells',
        'ultrasound' => 'Veterinary Ultrasound Scanners',
        'rfid' => 'RFID Readers & Ear Tagging Systems',
        'accessories' => 'Replacement Probes & Accessories',
    };
@endphp

@section('title', $categoryLabel.' — Farmtech')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ $categoryLabel }}</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        @forelse ($products as $product)
            @include('storefront.products._card', ['product' => $product])
        @empty
            <p class="text-gray-500 col-span-4">No products in this category yet.</p>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
@endsection
