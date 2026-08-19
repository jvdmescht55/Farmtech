@extends('layouts.storefront')

@section('title', 'Checkout — Farmtech')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Checkout</h1>

    <div class="grid md:grid-cols-3 gap-10">
        <form action="{{ route('checkout.store') }}" method="POST" class="md:col-span-2 space-y-6">
            @csrf

            <fieldset class="bg-white border rounded-xl p-6 space-y-4">
                <legend class="font-semibold px-1">Contact Details</legend>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Full Name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Cellphone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="082 123 4567" required class="w-full border rounded-md px-3 py-2">
                    </div>
                </div>
            </fieldset>

            <fieldset class="bg-white border rounded-xl p-6 space-y-4">
                <legend class="font-semibold px-1">Delivery Address</legend>
                <div>
                    <label class="block text-sm font-medium mb-1">Address Line 1</label>
                    <input type="text" name="address_line1" value="{{ old('address_line1') }}" required class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Address Line 2 (optional)</label>
                    <input type="text" name="address_line2" value="{{ old('address_line2') }}" class="w-full border rounded-md px-3 py-2">
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">City / Town</label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Province</label>
                        <select name="province" required class="w-full border rounded-md px-3 py-2">
                            <option value="">Select province</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province }}" @selected(old('province') === $province)>{{ $province }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Postal Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="4" pattern="[0-9]{4}" required class="w-full border rounded-md px-3 py-2">
                    </div>
                </div>
            </fieldset>

            <fieldset class="bg-white border rounded-xl p-6 space-y-3">
                <legend class="font-semibold px-1">Payment Method</legend>
                <label class="flex items-center gap-3 border rounded-md px-4 py-3 cursor-pointer">
                    <input type="radio" name="payment_gateway" value="payfast" required> PayFast
                </label>
                <label class="flex items-center gap-3 border rounded-md px-4 py-3 cursor-pointer">
                    <input type="radio" name="payment_gateway" value="ozow"> Ozow (Instant EFT)
                </label>
                <label class="flex items-center gap-3 border rounded-md px-4 py-3 cursor-pointer">
                    <input type="radio" name="payment_gateway" value="yoco"> Yoco (Card)
                </label>
            </fieldset>

            <button type="submit" class="w-full bg-farmtech-green text-white font-semibold px-8 py-3 rounded-md hover:bg-farmtech-green-dark">
                Place Order — R{{ number_format($subtotal, 2) }}
            </button>
        </form>

        <div class="bg-white border rounded-xl p-6 h-fit">
            <h2 class="font-semibold mb-4">Order Summary</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($items as $item)
                    <li class="flex justify-between">
                        <span>{{ $item['product']->title }} &times; {{ $item['quantity'] }}</span>
                        <span>R{{ number_format($item['line_total'], 2) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="border-t mt-4 pt-4 flex justify-between font-bold">
                <span>Total (incl. VAT)</span>
                <span>R{{ number_format($subtotal, 2) }}</span>
            </div>
        </div>
    </div>
@endsection
