@extends('layouts.storefront')

@php
    $meta = match($category) {
        'scales' => ['label' => 'Livestock Scales & Load Cells', 'desc' => 'Digital weighing indicators and platform scales built for the crush, race, or loading ramp — 220V/50Hz or battery powered, with load-cell sensitivity confirmed before listing.'],
        'ultrasound' => ['label' => 'Veterinary Ultrasound Scanners', 'desc' => 'Handheld pregnancy-diagnosis scanners with probe type confirmed for cattle, sheep, or swine — rectal linear, convex, or mechanical sector.'],
        'rfid' => ['label' => 'RFID Readers & Ear Tagging Systems', 'desc' => 'Handheld and stick readers checked against the 134.2 kHz ISO 11784/11785 livestock standard — the only frequency that reads standard SA ear tags.'],
        'accessories' => ['label' => 'Replacement Probes & Accessories', 'desc' => 'Replacement parts and accessories for the scanners and readers above.'],
    };
@endphp

@section('title', $meta['label'].' — Farmtech')

@section('content')
    <div class="bg-field-900 text-paper">
        <div class="max-w-7xl mx-auto px-4 py-14">
            <p class="text-xs font-mono uppercase tracking-widest text-tag-light mb-2">
                <a href="{{ route('home') }}" class="hover:text-white transition">Farmtech</a> / Category
            </p>
            <h1 class="font-display font-bold text-3xl sm:text-4xl mb-3">{{ $meta['label'] }}</h1>
            <p class="text-steel-300 max-w-2xl leading-relaxed">{{ $meta['desc'] }}</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10">
        <p class="text-sm text-steel-700 mb-6 font-mono">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }}</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($products as $i => $product)
                @include('storefront.products._card', ['product' => $product, 'delay' => $i * 70])
            @empty
                <div class="col-span-full border border-dashed border-steel-300 rounded-xl p-10 text-center">
                    <p class="text-field-900 font-semibold">No products in this category yet.</p>
                    <p class="text-steel-700 text-sm mt-1">Check back soon, or browse another category.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection
