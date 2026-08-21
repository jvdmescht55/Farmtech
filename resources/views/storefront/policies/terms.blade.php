@extends('layouts.storefront')

@section('title', 'Terms of Sale — Farmtech')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-16 prose prose-slate prose-headings:font-display max-w-none">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1 not-prose">Legal &amp; Compliance</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-1 not-prose">Terms of Sale</h1>
        <p class="text-sm text-ink-secondary mb-8 not-prose">Last updated {{ now()->format('j F Y') }}</p>

        <h2>1. Who You're Buying From</h2>
        <p>Farmtech ("we", "us") sells commercial and industrial equipment imported from overseas suppliers to buyers in South Africa. By placing an order on this site, you agree to these terms.</p>

        <h2 id="import">2. Pricing — What's Included</h2>
        <p>Every price shown on a Farmtech product page is <strong>all-inclusive</strong>: South African import duty (rate varies by product's HS code) and 15% VAT are already folded into the price you see. There is no separate customs bill, clearing-agent invoice, or import paperwork for you to handle after checkout — Farmtech's landed-cost pipeline calculates and absorbs those costs before the item is ever listed for sale. What you pay at checkout is the full price, with nothing extra due on delivery.</p>

        <h2 id="delivery">3. Delivery</h2>
        <p>Orders ship via Direct Express air freight, door to door, with a typical lead time of <strong>7–12 business days</strong> from payment confirmation (10–15 business days for larger/specialist items, shown on the specific product's page). Customs clearance is handled entirely by Farmtech as part of the price you paid — you will not be contacted by SARS or a courier for an additional payment before delivery. You can check real-time order status any time at <a href="{{ route('track.index') }}">Track Your Order</a>.</p>

        <h2>4. Payment</h2>
        <p>Payments are processed by PayFast, Ozow (Instant EFT), or Yoco. Farmtech never stores your card or banking details — they are handled entirely by the payment provider.</p>

        <h2>5. Compliance Before Listing</h2>
        <p>Every product listed on Farmtech is checked against the relevant South African regulatory standard for its category before it goes live — ISO 11784/11785 (livestock RFID), ICASA type-approval requirements (any product with a radio transmitter), or the applicable NRCS/electrical-safety standard — see our <a href="{{ route('policies.icasa') }}">ICASA &amp; Compliance</a> page for detail. This does not replace your own responsibility to confirm a product is fit for your specific intended use.</p>

        <h2>6. Stock &amp; Availability</h2>
        <p>Stock levels shown on product pages reflect what's actually available. If an item you've ordered becomes unavailable between order and fulfillment, we will contact you with an alternative or a full refund.</p>

        <h2>7. Returns</h2>
        <p>See our separate <a href="{{ route('policies.returns') }}">Returns &amp; Warranty Policy</a> for defective-goods and warranty terms under the Consumer Protection Act.</p>

        <h2>8. Governing Law</h2>
        <p>These terms are governed by the laws of the Republic of South Africa.</p>

        <hr class="not-prose my-8 border-border">
        <div class="not-prose bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
            <p class="font-semibold mb-1">For the operator to complete before this page goes live:</p>
            <p>Registered entity name: <strong>[Company Registration Pending]</strong> · Company registration number: <strong>[To be added]</strong> · Registered address: <strong>[To be added]</strong></p>
        </div>
    </div>
@endsection
