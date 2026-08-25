@extends('layouts.storefront')

@section('title', 'Checkout — Farmtech')

@section('content')
  <div class="max-w-6xl mx-auto px-4 py-12 sm:py-16">
    <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-2">Secure Checkout</p>
    <h1 class="font-display font-semibold text-3xl sm:text-4xl text-brand-900 mb-10">Complete your order</h1>

    <div class="grid lg:grid-cols-3 gap-10 items-start">
        <form action="{{ route('checkout.store') }}" method="POST" class="lg:col-span-2 space-y-6">
            @csrf

            <fieldset class="card !rounded-xl p-6 sm:p-7 space-y-4">
                <legend class="flex items-center gap-2.5 px-1 mb-1">
                    <span class="w-6 h-6 rounded-full bg-mint/10 text-mint-dark font-mono text-xs font-bold flex items-center justify-center flex-shrink-0">1</span>
                    <span class="font-display font-semibold text-lg text-charcoal">Contact Details</span>
                </legend>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">Full Name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="w-full border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">Cellphone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="082 123 4567" required class="w-full md:w-1/2 border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                    </div>
                </div>
            </fieldset>

            <fieldset class="card !rounded-xl p-6 sm:p-7 space-y-4">
                <legend class="flex items-center gap-2.5 px-1 mb-1">
                    <span class="w-6 h-6 rounded-full bg-mint/10 text-mint-dark font-mono text-xs font-bold flex items-center justify-center flex-shrink-0">2</span>
                    <span class="font-display font-semibold text-lg text-charcoal">Delivery Address</span>
                </legend>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">Address Line 1</label>
                    <input type="text" name="address_line1" value="{{ old('address_line1') }}" required class="w-full border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">Address Line 2 (optional)</label>
                    <input type="text" name="address_line2" value="{{ old('address_line2') }}" class="w-full border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">City / Town</label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="w-full border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">Province</label>
                        <select name="province" required class="w-full border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                            <option value="">Select province</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province }}" @selected(old('province') === $province)>{{ $province }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-ink-secondary mb-1.5">Postal Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="4" pattern="[0-9]{4}" required class="w-full border border-border rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint transition">
                    </div>
                </div>
            </fieldset>

            <fieldset class="card !rounded-xl p-6 sm:p-7 space-y-3">
                <legend class="flex items-center gap-2.5 px-1 mb-1">
                    <span class="w-6 h-6 rounded-full bg-mint/10 text-mint-dark font-mono text-xs font-bold flex items-center justify-center flex-shrink-0">3</span>
                    <span class="font-display font-semibold text-lg text-charcoal">Payment Method</span>
                </legend>
                <label class="flex items-center gap-3 border border-border rounded-lg px-4 py-3.5 cursor-pointer hover:border-mint/50 transition has-[:checked]:border-mint has-[:checked]:bg-mint/5">
                    <input type="radio" name="payment_gateway" value="payfast" required class="accent-mint w-4 h-4"> <span class="text-sm font-medium text-charcoal">PayFast</span>
                </label>
                <label class="flex items-center gap-3 border border-border rounded-lg px-4 py-3.5 cursor-pointer hover:border-mint/50 transition has-[:checked]:border-mint has-[:checked]:bg-mint/5">
                    <input type="radio" name="payment_gateway" value="ozow" class="accent-mint w-4 h-4"> <span class="text-sm font-medium text-charcoal">Ozow (Instant EFT)</span>
                </label>
                <label class="flex items-center gap-3 border border-border rounded-lg px-4 py-3.5 cursor-pointer hover:border-mint/50 transition has-[:checked]:border-mint has-[:checked]:bg-mint/5">
                    <input type="radio" name="payment_gateway" value="yoco" class="accent-mint w-4 h-4"> <span class="text-sm font-medium text-charcoal">Yoco (Card)</span>
                </label>
                <p class="text-xs text-ink-secondary flex items-center gap-1.5 pt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0 text-mint-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/></svg>
                    Card and bank details are handled directly by your chosen provider — Farmtech never sees or stores them.
                </p>
            </fieldset>

            <button type="submit" class="w-full bg-mint hover:bg-mint-dark text-white font-semibold px-8 py-3.5 rounded-full transition-all active:scale-[0.99]">
                Place Order — R{{ number_format($subtotal, 0, '', ' ') }}
            </button>
        </form>

        <div class="card !rounded-xl p-6 sm:p-7 lg:sticky lg:top-24">
            <h2 class="font-display font-semibold text-lg text-brand-900 mb-4">Order Summary</h2>
            <ul class="space-y-3 text-sm divide-y divide-border">
                @foreach ($items as $item)
                    <li class="flex justify-between gap-3 {{ !$loop->first ? 'pt-3' : '' }}">
                        <span class="min-w-0">
                            <span class="line-clamp-1 block text-charcoal font-medium">{{ $item['product']->title }} &times; {{ $item['quantity'] }}</span>
                            @if ($item['variant'])
                                <span class="block text-xs text-ink-secondary font-mono mt-0.5">{{ $item['variant']->option_name }}</span>
                            @endif
                        </span>
                        <span class="font-mono flex-shrink-0 text-charcoal">R{{ number_format($item['line_total'], 0, '', ' ') }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="scan-divider my-4"></div>
            <div class="flex justify-between items-baseline">
                <span class="font-semibold text-charcoal">Total</span>
                <span class="font-mono text-xl font-bold text-brand-900">R{{ number_format($subtotal, 0, '', ' ') }}</span>
            </div>
            <p class="text-xs text-ink-secondary mt-2">Includes SA import duty &amp; 15% VAT. Nothing extra on delivery.</p>

            <div class="flex items-center gap-2 mt-5 pt-5 border-t border-border text-xs text-ink-muted">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 text-mint-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                256-bit encrypted checkout &middot; PCI-compliant payment providers
            </div>
        </div>
    </div>
  </div>
@endsection
