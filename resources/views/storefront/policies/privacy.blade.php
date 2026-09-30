@extends('layouts.legal')

@section('title', 'Privacy Policy — Farmtech')

@section('policy')
    <div class="max-w-3xl mx-auto px-4 py-16 prose prose-slate prose-headings:font-display max-w-none">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1 not-prose">Legal &amp; Compliance</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-charcoal mb-1 not-prose">Privacy Policy</h1>
        <p class="text-sm text-ink-secondary mb-8 not-prose">Last updated {{ now()->format('j F Y') }}</p>

        <p>This policy is written to comply with South Africa's Protection of Personal Information Act, 4 of 2013 ("POPIA"). It explains what personal information Farmtech collects when you use this site, why, and your rights over it. For the purposes of POPIA, Farmtech is the <strong>Responsible Party</strong> for the personal information described below.</p>

        <h2>What We Collect, and Why (Lawful Basis)</h2>
        <p>At checkout, we collect your name, email address, phone number, and delivery/billing address — the minimum needed to perform the sale contract you're entering into by placing an order (POPIA's "processing necessary to carry out a contract" basis). We do not collect or store your card or banking details; those are handled entirely by the payment provider you choose (PayFast, Ozow, or Yoco).</p>

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

        <h2>How Long We Keep It</h2>
        <p>Order and personal information is retained for as long as needed to fulfil your order, handle any warranty or return under our <a href="{{ route('policies.returns') }}">Returns &amp; Warranty Policy</a>, and meet South African tax and financial record-keeping requirements (SARS generally requires retention of transaction records for 5 years) — not indefinitely, and not for any purpose beyond that.</p>

        <h2>Cookies</h2>
        <p>This site uses only the session cookie required to keep your cart working between pages — no third-party tracking or advertising cookies.</p>

        <h2>Your Rights Under POPIA</h2>
        <p>You have the right to ask what personal information we hold about you, ask us to correct or delete it, object to how it's processed, or withdraw consent where processing relies on it — email <a href="mailto:support@farmtech.co.za">support@farmtech.co.za</a> and we'll act on your request. If you believe your information has been mishandled and it isn't resolved directly with us, you have the right to lodge a complaint with South Africa's <strong>Information Regulator</strong> (<a href="https://inforegulator.org.za" target="_blank" rel="noopener">inforegulator.org.za</a>), the statutory body responsible for enforcing POPIA.</p>
    </div>
@endsection
