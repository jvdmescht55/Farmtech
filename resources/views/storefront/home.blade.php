@extends('layouts.storefront')

@section('title', 'Farmtech — AI-Vetted Agricultural Technology for South African Farms')

@section('content')
    <section class="bg-farmtech-green text-white rounded-2xl px-8 py-12 mb-12">
        <h1 class="text-3xl md:text-4xl font-bold max-w-2xl">Compliance-checked livestock tech, imported and priced for South Africa.</h1>
        <p class="mt-4 text-white/80 max-w-xl">Every scale, scanner, and RFID reader is AI-vetted against ISO 11784/11785, ICASA, and SARS requirements before it ever reaches this catalogue.</p>
    </section>

    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
        @foreach ($categories as $category)
            <a href="{{ route('category.show', $category) }}" class="bg-white border rounded-xl p-6 text-center hover:shadow-md transition">
                <span class="font-semibold text-farmtech-green-dark">
                    {{ match($category) {
                        'scales' => 'Livestock Scales',
                        'ultrasound' => 'Ultrasound Scanners',
                        'rfid' => 'RFID & Ear Tagging',
                        'accessories' => 'Probes & Accessories',
                    } }}
                </span>
            </a>
        @endforeach
    </section>

    <section>
        <h2 class="text-xl font-bold mb-4">Latest Approved Products</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @forelse ($featured as $product)
                @include('storefront.products._card', ['product' => $product])
            @empty
                <p class="text-gray-500 col-span-4">No products published yet — check back soon.</p>
            @endforelse
        </div>
    </section>
@endsection
