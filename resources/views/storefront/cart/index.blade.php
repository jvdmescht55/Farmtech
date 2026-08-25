@extends('layouts.storefront')

@section('title', 'Your Cart — Farmtech')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-12 sm:py-16">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-2">{{ count($items) }} item{{ count($items) === 1 ? '' : 's' }}</p>
        <h1 class="font-display font-semibold text-3xl sm:text-4xl text-brand-900 mb-10">Your Cart</h1>

        @if (empty($items))
            <div class="border border-dashed border-border rounded-xl p-16 text-center">
                <p class="text-brand-900 font-semibold mb-2">Your cart is empty.</p>
                <a href="{{ route('home') }}" class="text-mint-dark font-semibold hover:underline">Browse products &rarr;</a>
            </div>
        @else
            <div class="card !rounded-xl divide-y divide-border">
                @foreach ($items as $item)
                    <div class="flex items-center gap-4 p-5">
                        <a href="{{ route('products.show', $item['product']) }}" class="w-16 h-16 bg-canvas border border-border rounded-lg overflow-hidden flex-shrink-0">
                            @if ($item['product']->thumbnail)
                                <img src="{{ $item['product']->thumbnail->url }}" class="object-contain p-1.5 w-full h-full" alt="" onerror="this.remove()">
                            @endif
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('products.show', $item['product']) }}" class="font-semibold text-sm text-brand-900 hover:text-mint-dark transition line-clamp-1">{{ $item['product']->title }}</a>
                            @if ($item['variant'])
                                <p class="text-xs text-ink-secondary font-mono">{{ $item['variant']->option_name }}</p>
                            @endif
                            <p class="text-sm text-ink-secondary font-mono">R{{ number_format($item['unit_price_zar'], 0, '', ' ') }} each</p>
                        </div>
                        <form action="{{ route('cart.update', $item['product']) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">
                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="w-16 border border-border rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30">
                            <button type="submit" class="text-xs font-semibold text-mint-dark hover:underline">Update</button>
                        </form>
                        <p class="font-mono font-semibold w-28 text-right">R{{ number_format($item['line_total'], 0, '', ' ') }}</p>
                        <form action="{{ route('cart.remove', $item['product']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">
                            <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 bg-brand-900 text-white rounded-xl px-6 py-4 flex justify-between items-center">
                <div>
                    <p class="text-sm text-ink-muted">Subtotal (incl. duty &amp; 15% VAT)</p>
                    <p class="text-xs text-ink-muted">Nothing extra to pay on delivery.</p>
                </div>
                <span class="text-2xl font-mono font-bold">R{{ number_format($subtotal, 0, '', ' ') }}</span>
            </div>

            <div class="mt-4 flex justify-end">
                <a href="{{ route('checkout.index') }}" class="bg-mint hover:bg-mint-dark text-white font-semibold px-8 py-3 rounded-full transition inline-flex items-center gap-2">
                    Proceed to Checkout
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        @endif
    </div>
@endsection
