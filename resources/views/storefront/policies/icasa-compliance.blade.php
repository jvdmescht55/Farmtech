@extends('layouts.storefront')

@section('title', 'ICASA & ISO Compliance — Farmtech')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-16 prose prose-slate prose-headings:font-display max-w-none">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1 not-prose">Legal &amp; Compliance</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-1 not-prose">ICASA &amp; ISO Compliance</h1>
        <p class="text-sm text-ink-secondary mb-8 not-prose">Last updated {{ now()->format('j F Y') }}</p>

        <p>Certain radio-emitting and electronic products sold in South Africa require regulatory compliance under ICASA and related standards. Not every product on Farmtech is subject to the same requirements — this page explains what applies to what, and every product page shows the specific checks recorded for that item.</p>

        <h2>What Gets Checked, and When</h2>
        <p>Every listing on Farmtech goes through an automated sourcing and compliance review before it's approved for sale — not after a customer complaint. The review covers three regulatory areas, depending on what the product actually is:</p>

        <h2>1. ISO 11784/11785 — Livestock RFID</h2>
        <p>RFID readers and ear-tagging equipment sold for livestock identification are checked against the ISO 11784/11785 standard, which specifies <strong>134.2 kHz</strong> as the operating frequency for animal identification in South Africa. A reader operating at a different frequency (most commonly 125 kHz, sold internationally for other uses) will not reliably read standard South African livestock ear tags, and is rejected during the compliance review rather than listed.</p>

        <h2>2. ICASA — Radio-Emitting Devices</h2>
        <p>Any product containing a radio transmitter — Bluetooth, Wi-Fi, cellular/GSM, or a proprietary RF link (this includes fleet GPS trackers, UHF RFID gate scanners, and any Wi-Fi/Bluetooth-enabled controller) — is checked for its ICASA type-approval status before listing. Each product page's Compliance section shows the actual status found for that specific item:</p>
        <ul>
            <li><strong>Pre-approved</strong> — the specific radio module used already carries ICASA type approval.</li>
            <li><strong>Exempt</strong> — the product doesn't use regulated RF spectrum (e.g. a mains-powered indicator with no wireless module).</li>
            <li><strong>Requires permit</strong> — importable, but requires an import permit under ICASA's type-approval process.</li>
            <li><strong>Flagged</strong> — the review could not confirm compliant status; these items are not listed for sale.</li>
        </ul>

        <h2>3. NRCS / Electrical Safety</h2>
        <p>Mains-powered and electric-fence-energizer equipment is checked for stated power source, voltage rating, and — for fencing energizers specifically — joule output and NRCS electrical-safety compliance, the standard South Africa requires before an energizer may legally be sold or imported.</p>

        <h2>Where to See the Result for a Specific Product</h2>
        <p>Every product page's <strong>Compliance</strong> section shows the real checks recorded for that item — not a generic badge. If a check couldn't be confirmed for a given listing, that's shown honestly rather than assumed.</p>

        <h2>This Doesn't Replace Your Own Diligence</h2>
        <p>This review process is Farmtech's own sourcing and listing standard, not a substitute for confirming a product's suitability for your specific regulatory context (for example, if you're a licensed radio user with additional obligations). If you need documentation beyond what's shown on a product page, contact <a href="mailto:support@farmtech.co.za">support@farmtech.co.za</a>.</p>
    </div>
@endsection
