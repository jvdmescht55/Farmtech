@extends('layouts.storefront')

@section('title', 'Your Cart — Farmtech')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Your Cart</h1>

    @if (empty($items))
        <p class="text-gray-500">Your cart is empty. <a href="{{ route('home') }}" class="text-farmtech-green font-medium">Browse products</a>.</p>
    @else
        <div class="bg-white border rounded-xl divide-y">
            @foreach ($items as $item)
                <div class="flex items-center gap-4 p-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-md overflow-hidden flex-shrink-0">
                        @if ($item['product']->thumbnail)
                            <img src="{{ $item['product']->thumbnail->url }}" class="object-cover w-full h-full" alt="">
                        @endif
                    </div>
                    <div class="flex-1">
                        <a href="{{ route('products.show', $item['product']) }}" class="font-medium hover:text-farmtech-green">{{ $item['product']->title }}</a>
                        <p class="text-sm text-gray-500">R{{ number_format($item['product']->retail_price_zar, 2) }} each</p>
                    </div>
                    <form action="{{ route('cart.update', $item['product']) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="w-16 border rounded-md px-2 py-1 text-sm">
                        <button type="submit" class="text-sm text-farmtech-green font-medium">Update</button>
                    </form>
                    <p class="font-semibold w-28 text-right">R{{ number_format($item['line_total'], 2) }}</p>
                    <form action="{{ route('cart.remove', $item['product']) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600">Remove</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end items-center gap-6">
            <span class="text-gray-500">Subtotal (incl. VAT)</span>
            <span class="text-2xl font-bold">R{{ number_format($subtotal, 2) }}</span>
        </div>

        <div class="mt-4 flex justify-end">
            <a href="{{ route('checkout.index') }}" class="bg-farmtech-green text-white font-semibold px-8 py-3 rounded-md hover:bg-farmtech-green-dark">Proceed to Checkout</a>
        </div>
    @endif
@endsection
