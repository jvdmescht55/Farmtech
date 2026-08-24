@extends('layouts.storefront')

@section('title', 'Checkout — Farmtech')

@section('content')
  <div class="max-w-7xl mx-auto px-4 py-10">
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-8">Checkout</h1>

    <div class="grid md:grid-cols-3 gap-10">
        <form action="{{ route('checkout.store') }}" method="POST" class="md:col-span-2 space-y-6">
            @csrf

            <fieldset class="bg-white border border-border rounded-xl p-6 space-y-4">
                <legend class="font-semibold px-1">Contact Details</legend>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Full Name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Cellphone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="082 123 4567" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                    </div>
                </div>
            </fieldset>

            <fieldset class="bg-white border border-border rounded-xl p-6 space-y-4">
                <legend class="font-semibold px-1">Delivery Address</legend>
                <div>
                    <label class="block text-sm font-medium mb-1">Address Line 1</label>
                    <input type="text" name="address_line1" value="{{ old('address_line1') }}" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Address Line 2 (optional)</label>
                    <input type="text" name="address_line2" value="{{ old('address_line2') }}" class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">City / Town</label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Province</label>
                        <select name="province" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                            <option value="">Select province</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province }}" @selected(old('province') === $province)>{{ $province }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Postal Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="4" pattern="[0-9]{4}" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                    </div>
                </div>
            </fieldset>

            <fieldset class="bg-white border border-border rounded-xl p-6 space-y-3">
                <legend class="font-semibold px-1">Payment Method</legend>
                <label class="flex items-center gap-3 border border-border rounded-md px-4 py-3 cursor-pointer hover:border-mint/50 transition has-[:checked]:border-mint has-[:checked]:bg-mint/5">
                    <input type="radio" name="payment_gateway" value="payfast" required class="accent-mint"> PayFast
                </label>
                <label class="flex items-center gap-3 border border-border rounded-md px-4 py-3 cursor-pointer hover:border-mint/50 transition has-[:checked]:border-mint has-[:checked]:bg-mint/5">
                    <input type="radio" name="payment_gateway" value="ozow" class="accent-mint"> Ozow (Instant EFT)
                </label>
                <label class="flex items-center gap-3 border border-border rounded-md px-4 py-3 cursor-pointer hover:border-mint/50 transition has-[:checked]:border-mint has-[:checked]:bg-mint/5">
                    <input type="radio" name="payment_gateway" value="yoco" class="accent-mint"> Yoco (Card)
                </label>
                <p class="text-xs text-ink-secondary flex items-center gap-1.5 pt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
                    Card and bank details are handled directly by your chosen provider — Farmtech never sees or stores them.
                </p>
            </fieldset>

            <button type="submit" class="w-full bg-mint hover:bg-mint-dark text-white font-semibold px-8 py-3 rounded-full transition">
                Place Order — R{{ number_format($subtotal, 0, '', ' ') }}
            </button>
        </form>

        <div class="bg-white border border-border rounded-xl p-6 h-fit shadow-sm">
            <h2 class="font-display font-semibold mb-4 text-brand-900">Order Summary</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($items as $item)
                    <li class="flex justify-between gap-3">
                        <span class="min-w-0">
                            <span class="line-clamp-1 block">{{ $item['product']->title }} &times; {{ $item['quantity'] }}</span>
                            @if ($item['variant'])
                                <span class="block text-xs text-ink-secondary font-mono">{{ collect($item['variant']['attributes'] ?? [])->map(fn ($v, $k) => "{$k}: {$v}")->implode(' · ') }}</span>
                            @endif
                        </span>
                        <span class="font-mono flex-shrink-0">R{{ number_format($item['line_total'], 0, '', ' ') }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-border mt-4 pt-4 flex justify-between font-bold text-brand-900">
                <span>Total</span>
                <span class="font-mono">R{{ number_format($subtotal, 0, '', ' ') }}</span>
            </div>
            <p class="text-xs text-ink-secondary mt-2">Includes SA import duty &amp; 15% VAT. Nothing extra on delivery.</p>
        </div>
    </div>
  </div>
@endsection
