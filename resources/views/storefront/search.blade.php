@extends('layouts.storefront')

@section('title', ($query !== '' ? 'Search: '.$query : 'Search').' — Farmtech')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-10">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1">Search results</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-8">
            @if ($query !== '')
                "{{ $query }}"
            @else
                Search Farmtech
            @endif
        </h1>

        @if ($query === '')
            <p class="text-slate-600">Type a keyword above — try "134.2 kHz", "rectal probe", "load cell", or a category like "ultrasound".</p>
        @elseif ($products->isEmpty())
            <div class="border border-dashed border-slate-300 rounded-2xl p-10 text-center">
                <p class="text-brand-900 font-semibold mb-1">No matches for "{{ $query }}"</p>
                <p class="text-slate-600 text-sm">Try a shorter or more general term — e.g. "scale" instead of "digital scale indicator".</p>
            </div>
        @else
            <p class="text-sm text-slate-600 mb-6">{{ $products->total() }} result{{ $products->total() === 1 ? '' : 's' }}</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach ($products as $i => $product)
                    @include('storefront.products._card', ['product' => $product, 'delay' => $i * 60])
                @endforeach
            </div>
            <div class="mt-10">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
