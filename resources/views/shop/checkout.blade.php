@extends('layouts.site')
@section('title', 'Checkout — Farmtech')
@push('head')<meta name="robots" content="noindex">@endpush
@section('content')
@php($buying = $lines->where('reserve', false)->isNotEmpty())
<section class="wrap pt-32 sm:pt-36 pb-24">
    <a href="{{ route('shop.cart') }}" class="text-sm text-stone underline">← Back to cart</a>
    <h1 class="h-display mt-4 text-[clamp(3rem,7vw,5.5rem)]">{{ $buying ? 'Checkout' : 'Confirm your reservation' }}</h1>
    <form method="POST" action="{{ route('shop.place') }}" class="mt-10 grid lg:grid-cols-[1fr_24rem] gap-10 items-start" x-data="{ delivery: '{{ old('delivery_method', 'courier') }}', pay: '{{ old('payment_method', 'eft') }}' }">
        @csrf
        <input type="text" name="website" aria-label="Leave empty" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
        <div class="space-y-6">
            @if ($errors->any())<div class="rounded-2xl bg-[#B0452F]/10 border border-[#B0452F]/30 px-5 py-4 text-sm">Eish, check the highlighted fields: {{ $errors->first() }}</div>@endif

            <fieldset class="rounded-[28px] bg-white border border-hairline p-6 sm:p-8">
                <legend class="sr-only">Your details</legend>
                <div class="font-headline text-2xl">1. Your details</div>
                <div class="mt-5 grid sm:grid-cols-2 gap-4">
                    <div><label class="field-label">Name &amp; surname *</label><input name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required autocomplete="name" class="field @error('customer_name') !border-[#B0452F] @enderror"></div>
                    <div><label class="field-label">Farm / stud</label><input name="farm_name" value="{{ old('farm_name', auth()->user()?->farm_name) }}" autocomplete="organization" class="field"></div>
                    <div><label class="field-label">Email *</label><input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required autocomplete="email" class="field @error('email') !border-[#B0452F] @enderror"></div>
                    <div><label class="field-label">Cellphone *</label><input type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="082 123 4567" class="field @error('phone') !border-[#B0452F] @enderror"></div>
                </div>
            </fieldset>

            <fieldset class="rounded-[28px] bg-white border border-hairline p-6 sm:p-8">
                <legend class="sr-only">Delivery</legend>
                <div class="font-headline text-2xl">2. Delivery</div>
                <div class="mt-5 grid sm:grid-cols-2 gap-3">
                    <label class="rounded-2xl border p-4 cursor-pointer transition" :class="delivery === 'courier' ? 'border-char bg-sand-light' : 'border-hairline'">
                        <input type="radio" name="delivery_method" value="courier" x-model="delivery" class="sr-only">
                        <div class="font-medium">Courier to my farm or town</div>
                        <div class="text-sm text-stone mt-1">{{ $courier === null ? 'Cost confirmed with your order' : ($courier ? \App\Models\StoreListing::rand($courier) : 'Free') }}</div>
                    </label>
                    <label class="rounded-2xl border p-4 cursor-pointer transition" :class="delivery === 'collect' ? 'border-char bg-sand-light' : 'border-hairline'">
                        <input type="radio" name="delivery_method" value="collect" x-model="delivery" class="sr-only">
                        <div class="font-medium">I'll collect</div>
                        <div class="text-sm text-stone mt-1">{{ config('shop.collect_from') ?: 'We arrange a time and place with you' }}</div>
                    </label>
                </div>
                <div x-show="delivery === 'courier'" class="mt-5 grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2"><label class="field-label">Street address or farm name &amp; road *</label><input name="address_line1" value="{{ old('address_line1') }}" autocomplete="address-line1" class="field @error('address_line1') !border-[#B0452F] @enderror"></div>
                    <div class="sm:col-span-2"><label class="field-label">More directions (optional)</label><input name="address_line2" value="{{ old('address_line2') }}" placeholder="e.g. 12 km on the R27, second gate" autocomplete="address-line2" class="field"></div>
                    <div><label class="field-label">Town *</label><input name="city" value="{{ old('city') }}" autocomplete="address-level2" class="field @error('city') !border-[#B0452F] @enderror"></div>
                    <div><label class="field-label">Postal code *</label><input name="postal_code" value="{{ old('postal_code') }}" inputmode="numeric" maxlength="4" autocomplete="postal-code" class="field @error('postal_code') !border-[#B0452F] @enderror"></div>
                    <div class="sm:col-span-2"><label class="field-label">Province *</label>
                        <select name="province" class="field @error('province') !border-[#B0452F] @enderror"><option value="">Choose…</option>@foreach ($provinces as $p)<option @selected(old('province') === $p)>{{ $p }}</option>@endforeach</select></div>
                </div>
            </fieldset>

            @if ($buying)
                <fieldset class="rounded-[28px] bg-white border border-hairline p-6 sm:p-8">
                    <legend class="sr-only">Payment</legend>
                    <div class="font-headline text-2xl">3. Payment</div>
                    <div class="mt-5 grid sm:grid-cols-2 gap-3">
                        <label class="rounded-2xl border p-4 cursor-pointer transition" :class="pay === 'eft' ? 'border-char bg-sand-light' : 'border-hairline'">
                            <input type="radio" name="payment_method" value="eft" x-model="pay" class="sr-only">
                            <div class="font-medium">EFT / bank transfer</div>
                            <div class="text-sm text-stone mt-1">You get our banking details and an invoice. We ship once it clears.</div>
                        </label>
                        @if ($cardEnabled)
                            <label class="rounded-2xl border p-4 cursor-pointer transition" :class="pay === 'card' ? 'border-char bg-sand-light' : 'border-hairline'">
                                <input type="radio" name="payment_method" value="card" x-model="pay" class="sr-only">
                                <div class="font-medium">Card or Instant EFT</div>
                                <div class="text-sm text-stone mt-1">Pay now, securely through PayFast.</div>
                            </label>
                        @endif
                    </div>
                </fieldset>
            @endif

            <fieldset class="rounded-[28px] bg-white border border-hairline p-6 sm:p-8">
                <legend class="sr-only">Anything else</legend>
                <label class="field-label">Anything we should know? (optional)</label>
                <textarea name="customer_notes" rows="3" class="field" placeholder="e.g. Best to phone after 5pm, or how many animals you run">{{ old('customer_notes') }}</textarea>
                <label class="mt-5 flex items-start gap-3 text-sm">
                    <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-hairline" @checked(old('terms'))>
                    <span>I accept the <a href="{{ route('legal.show', 'sale') }}" target="_blank" class="underline">terms of sale</a> and <a href="{{ route('legal.show', 'returns') }}" target="_blank" class="underline">returns policy</a>, and I've read the <a href="{{ route('legal.show', 'privacy') }}" target="_blank" class="underline">privacy policy</a>.</span>
                </label>
            </fieldset>
        </div>

        <div class="space-y-4 lg:sticky lg:top-28">
            <div class="rounded-[28px] bg-white border border-hairline px-6">
                @foreach ($lines as $line)
                    <div class="flex justify-between gap-3 py-4 border-b border-hairline last:border-0 text-sm">
                        <span>{{ $line['qty'] }} × {{ $line['listing']->name }}@if ($line['reserve'])<span class="block text-xs text-ochre-dark">Reservation</span>@endif</span>
                        <span class="font-num shrink-0">{{ $line['line'] ? \App\Models\StoreListing::rand($line['line']) : 'TBC' }}</span>
                    </div>
                @endforeach
            </div>
            @include('shop._summary')
            <button class="btn-dark w-full !h-14 text-base">{{ $buying ? 'Place order' : 'Reserve' }}</button>
            <p class="text-xs text-stone text-center">{{ $buying ? 'Nothing is charged until you pay by EFT or card.' : 'No payment now.' }}</p>
        </div>
    </form>
</section>
@endsection
