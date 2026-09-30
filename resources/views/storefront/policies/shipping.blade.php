@extends('layouts.legal')

@section('title', 'Shipping & Import Policy — Farmtech')

@section('policy')
    <div class="max-w-3xl mx-auto px-4 py-16 prose prose-slate prose-headings:font-display max-w-none">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1 not-prose">Legal &amp; Compliance</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-1 not-prose">Shipping &amp; Import Policy</h1>
        <p class="text-sm text-ink-secondary mb-8 not-prose">Last updated {{ now()->format('j F Y') }}</p>

        <p>This page explains exactly how a Farmtech order gets from an overseas supplier to your door — delivery timeframes, how customs and import duty are handled, and how to track an order in transit.</p>

        <h2>1. Delivery Timeframe</h2>
        <p>Orders ship via Direct Express air freight, door to door. Typical lead time is <strong>7–12 business days</strong> from payment confirmation for standard equipment, and <strong>10–15 business days</strong> for larger or specialist items — the exact figure for a specific listing is always shown on that product's page, and repeated at checkout before you pay.</p>

        <h2>2. Customs &amp; Import Handling</h2>
        <p>Every price shown on Farmtech is all-inclusive: South African import duty (the rate varies by the product's HS code) and 15% VAT are calculated per product and already folded into the price you see before it's ever listed for sale. Farmtech manages customs clearance end to end as part of your order — you will not be contacted by SARS, a clearing agent, or a courier for an additional payment before delivery. What you pay at checkout is the complete price.</p>

        <h2>3. Courier &amp; Tracking</h2>
        <p>Once your order ships, you can follow its real-time status at <a href="{{ route('track.index') }}">Track Your Order</a> using your order number (format <code>FT-YYYYMMDD-XXXX</code>, on your confirmation email) and the email address you checked out with. You'll also receive email updates as your order moves from payment confirmation through customs clearance to delivery.</p>

        <h2>4. Delivery Area</h2>
        <p>Farmtech delivers nationwide across South Africa. Delivery address, city, province, and postal code are collected at checkout to route your order correctly.</p>

        <h2>5. If Something Goes Wrong in Transit</h2>
        <p>If your order is delayed beyond its stated lead time, or arrives damaged, contact <a href="mailto:support@farmtech.co.za">support@farmtech.co.za</a> with your order number — see our <a href="{{ route('policies.returns') }}">Returns &amp; Warranty Policy</a> for how a damaged or not-as-described delivery is handled under the Consumer Protection Act.</p>
    </div>
@endsection
