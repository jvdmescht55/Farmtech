@extends('layouts.storefront')

@section('title', 'Returns & Warranty Policy — Farmtech')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-16 prose prose-slate prose-headings:font-display max-w-none">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1 not-prose">Legal &amp; Compliance</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-1 not-prose">Returns &amp; Warranty Policy</h1>
        <p class="text-sm text-ink-secondary mb-8 not-prose">Last updated {{ now()->format('j F Y') }}</p>

        <p>This policy is written to comply with the South African Consumer Protection Act, 68 of 2008 ("the CPA"). Nothing in this policy limits any right the CPA gives you as a consumer.</p>

        <h2>1. Defective, Unsafe, or Not-as-Described Goods</h2>
        <p>Under section 56 of the CPA, if hardware you receive from Farmtech is defective, unsafe, or does not match the specification shown on its product page at the time of purchase, you may return it within <strong>14 (fourteen) days</strong> of delivery for your choice of a repair, replacement, or full refund — Farmtech's choice does not override yours here; the CPA gives the consumer the choice.</p>
        <p>To start a return, email <a href="mailto:support@farmtech.co.za">support@farmtech.co.za</a> with your order number (format <code>FT-YYYYMMDD-XXXX</code>, shown on your confirmation email and on the <a href="{{ route('track.index') }}">order tracking page</a>) and a description of the fault. We aim to acknowledge every return request within 1 business day.</p>

        <h2>2. Change-of-Mind Returns</h2>
        <p>Because every listing on Farmtech is imported to order against a real supplier purchase once your payment clears, change-of-mind returns are handled case by case rather than as an automatic right — the CPA's change-of-mind provisions (section 44) apply specifically to direct-marketing transactions, and a considered B2B/agri-equipment purchase from a product page you actively browsed does not fall under that section. Contact <a href="mailto:support@farmtech.co.za">support@farmtech.co.za</a> before the item ships and we will do our best to accommodate a cancellation.</p>

        <h2>3. Manufacturer Warranty</h2>
        <p>Warranty terms vary by supplier and model, since Farmtech imports equipment from multiple overseas manufacturers rather than manufacturing hardware itself. The specific warranty coverage for the item you're asking about is confirmed on request — contact <a href="mailto:support@farmtech.co.za">support@farmtech.co.za</a> with the product's SKU, shown on its product page.</p>

        <h2>4. Return Shipping</h2>
        <p>For a defective or not-as-described item under section 1 above, return shipping is covered by Farmtech. For a discretionary change-of-mind cancellation under section 2, return/cancellation shipping costs are agreed with you case by case before the return proceeds.</p>

        <h2>5. Unresolved Complaints</h2>
        <p>If a return or complaint isn't resolved to your satisfaction through the process above, you may refer it to the National Consumer Commission (NCC), the statutory body responsible for enforcing the Consumer Protection Act in South Africa.</p>

        <hr class="not-prose my-8 border-border">
        <div class="not-prose bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
            <p class="font-semibold mb-1">For the operator to complete before this page goes live:</p>
            <p>Registered entity name: <strong>[Company Registration Pending]</strong> · Company registration number: <strong>[To be added]</strong> · Registered address: <strong>[To be added]</strong></p>
        </div>
    </div>
@endsection
