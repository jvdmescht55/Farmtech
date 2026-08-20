@extends('layouts.storefront')

@section('title', 'Privacy Policy — Farmtech')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-16 prose prose-slate prose-headings:font-display max-w-none">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1 not-prose">Legal &amp; Compliance</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-charcoal mb-1 not-prose">Privacy Policy</h1>
        <p class="text-sm text-slate-500 mb-8 not-prose">Last updated {{ now()->format('j F Y') }}</p>

        <p>This policy explains what personal information Farmtech collects when you use this site, and what it's used for.</p>

        <h2>What We Collect</h2>
        <p>At checkout, we collect your name, email address, phone number, and delivery/billing address — the minimum needed to fulfil and deliver an order. We do not collect or store your card or banking details; those are handled entirely by the payment provider you choose (PayFast, Ozow, or Yoco).</p>

        <h2>What It's Used For</h2>
        <ul>
            <li>Processing and delivering your order.</li>
            <li>Order confirmation, status, and delivery emails.</li>
            <li>Responding to support requests you send us.</li>
            <li>Looking up your own order at <a href="{{ route('track.index') }}">Track Your Order</a>, using the order number and email you provide.</li>
        </ul>
        <p>We do not sell or rent customer information to third parties.</p>

        <h2>Who We Share It With</h2>
        <p>Order and delivery details are shared with the courier fulfilling your delivery, and payment details are handled directly by your chosen payment provider — not passed through Farmtech. Product suppliers do not receive your personal contact details; Farmtech coordinates fulfilment on your behalf.</p>

        <h2>Cookies</h2>
        <p>This site uses only the session cookie required to keep your cart working between pages — no third-party tracking or advertising cookies.</p>

        <h2>Your Rights</h2>
        <p>You can ask what personal information we hold about you, or ask us to correct or delete it, by emailing <a href="mailto:support@farmtech.co.za">support@farmtech.co.za</a>.</p>

        <hr class="not-prose my-8 border-slate-200">
        <div class="not-prose bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
            <p class="font-semibold mb-1">For the operator to complete before this page goes live:</p>
            <p>Registered entity name: <strong>[Company Registration Pending]</strong> · Data protection contact/officer, if applicable under POPIA: <strong>[To be added]</strong></p>
        </div>
    </div>
@endsection
