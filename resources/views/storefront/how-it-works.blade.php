@extends('layouts.storefront')

@section('title', 'How Farmtech Works — Sourcing, Import & Delivery')

@section('content')
    <div class="bg-canvas border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-16 text-center">
            <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">How It Works</p>
            <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-charcoal leading-tight mb-4">
                Global equipment. Local confidence.
            </h1>
            <p class="text-slate-600 text-lg leading-relaxed max-w-2xl mx-auto">
                We verify suppliers, check product specifications and handle the import process —
                so you know exactly what you're buying and exactly what you'll pay.
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-20">
        <div class="grid gap-10 sm:gap-14">
            @foreach ([
                ['n' => '01', 'title' => 'Choose your equipment', 'body' => 'Browse categories by industry or search by product, specification, or application. Every listed product has already passed the checks below — nothing goes live unreviewed.'],
                ['n' => '02', 'title' => 'See your complete price', 'body' => 'South African import duty and 15% VAT are already calculated into the price shown, using the real duty rate for that product\'s HS code. No customs invoice or clearing-agent bill arrives later.'],
                ['n' => '03', 'title' => 'Place your order', 'body' => 'Checkout runs through PayFast, Ozow (Instant EFT), or Yoco. Farmtech never stores your card or banking details — the payment provider handles that.'],
                ['n' => '04', 'title' => 'We handle the import', 'body' => 'Your order is coordinated with the supplier and shipped via Direct Express air freight. Customs clearance is handled as part of the price you already paid.'],
                ['n' => '05', 'title' => 'Delivered to your door', 'body' => 'Typical delivery is 7–12 business days from payment confirmation (10–15 for larger/specialist items). Track real-time status any time — see the delivery timeline below.'],
            ] as $step)
                <div class="flex gap-6 sm:gap-8">
                    <span class="font-mono text-3xl sm:text-4xl font-bold text-mint/40 flex-shrink-0">{{ $step['n'] }}</span>
                    <div>
                        <h2 class="font-display font-bold text-xl text-charcoal mb-1.5">{{ $step['title'] }}</h2>
                        <p class="text-slate-600 leading-relaxed">{{ $step['body'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Delivery timeline --}}
    <div class="bg-brand-950 text-white">
        <div class="max-w-4xl mx-auto px-4 py-16">
            <h2 class="font-display font-bold text-2xl text-center mb-10">Your order's journey</h2>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 sm:gap-2">
                @foreach (['Order placed', 'Supplier confirmed', 'International transit', 'Customs clearance', 'Local courier', 'Delivered'] as $i => $stage)
                    <div class="flex sm:flex-col items-center gap-3 sm:gap-2 sm:text-center flex-1">
                        <span class="w-8 h-8 rounded-full bg-mint/20 border border-mint/40 text-mint-light flex items-center justify-center text-xs font-mono font-bold flex-shrink-0">{{ $i + 1 }}</span>
                        <span class="text-sm text-white/80">{{ $stage }}</span>
                    </div>
                    @if (!$loop->last)
                        <div class="hidden sm:block flex-1 h-px bg-white/15 -mt-6"></div>
                    @endif
                @endforeach
            </div>
            <p class="text-center text-white/50 text-sm mt-10">Real order status for your own purchase is at <a href="{{ route('track.index') }}" class="underline hover:text-white transition">Track Your Order</a>.</p>
        </div>
    </div>

    {{-- Why Farmtech --}}
    <div class="max-w-5xl mx-auto px-4 py-20">
        <h2 class="font-display font-bold text-2xl text-charcoal text-center mb-2">Why buy through Farmtech?</h2>
        <p class="text-slate-600 text-center max-w-xl mx-auto mb-12">AI-assisted supplier and product verification, with a fixed set of checks every listing has to pass — not a marketing claim, a process.</p>
        <div class="grid sm:grid-cols-2 gap-x-10 gap-y-10">
            @foreach ([
                ['n' => '01', 'title' => 'Supplier verified', 'body' => 'Listings from suppliers with a short trading history, no verified-account status, or no trade assurance are flagged before anything goes live.'],
                ['n' => '02', 'title' => 'Product checked', 'body' => 'Each listing is checked against the relevant South African standard for its category — ISO 11784/11785 for livestock RFID, ICASA type-approval for radio-emitting devices, or NRCS electrical-safety rules for mains/energizer equipment.'],
                ['n' => '03', 'title' => 'Import calculated', 'body' => 'Freight, South African import duty, and 15% VAT are calculated per product and folded into the price shown — not estimated after the fact.'],
                ['n' => '04', 'title' => 'Delivered to you', 'body' => 'Door-to-door delivery with real order tracking, not a vague "your order has shipped" email.'],
            ] as $item)
                <div>
                    <span class="font-mono text-sm text-mint-dark font-bold">{{ $item['n'] }}</span>
                    <h3 class="font-display font-bold text-lg text-charcoal mt-1 mb-1.5">{{ $item['title'] }}</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
