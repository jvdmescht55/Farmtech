@extends('legal._layout')
@php
    $l = config('legal');
@endphp
@section('legal')
<p>This explains what personal information we collect, why, and your rights under the <strong>Protection of Personal Information Act (POPIA)</strong>. We keep it to what we need, and we never sell it.</p>
@include('legal._who')
<p><strong>Information Officer:</strong> {!! filled($l['information_officer']) ? e($l['information_officer']) : '<mark class="bg-ochre/20 text-ochre-dark px-1 rounded">[name to be completed]</mark>' !!} — reach them at {{ $l['email'] }}.</p>

<h2>What we collect</h2>
<ul>
    <li><strong>Account details:</strong> name, email, phone, farm or stud name, address and breeder number (if you add them), and your password (stored scrambled, never readable).</li>
    <li><strong>Enquiries:</strong> what you type into our order, interest, suggestion and contact forms.</li>
    <li><strong>Farm records:</strong> animals, tag numbers, weights, pedigree, health and breeding records, device readings. This is mostly information about animals, but it's linked to you, so we protect it the same way.</li>
    <li><strong>Device and technical data:</strong> device serial, firmware, battery level, IP address and times of connection, and standard server logs.</li>
</ul>

<h2>Why we use it</h2>
<ul>
    <li>To run your account and the software, and to sync your devices (needed to perform our agreement with you).</li>
    <li>To process orders, deliveries, returns and warranty claims.</li>
    <li>To answer you, and to tell you about your account or orders.</li>
    <li>To keep the site secure and working, and to fix problems (our legitimate interest).</li>
    <li>To send news about new devices only if you've asked for it — you can stop that at any time.</li>
</ul>

<h2>Who we share it with</h2>
<p>Only service providers that help us run the site, under agreements to keep it safe: our hosting provider ({{ $l['hosting'] }}), and couriers or payment providers when you buy something. We share information with authorities only when the law requires it. We don't sell or rent personal information, and we don't share your farm records with anyone without your instruction.</p>

<h2>Information stored outside South Africa</h2>
<p>Our servers are in Germany (DigitalOcean, Frankfurt), and Cloudflare handles traffic to the site. Germany is covered by the EU General Data Protection Regulation, which gives protection at least as strong as POPIA (section 72).</p>

<h2>How long we keep it</h2>
<ul>
    <li>Account and farm records: while your account is open, then 30 days after closing (so you can change your mind), then deleted.</li>
    <li>Order and invoice records: as long as tax law requires (usually 5 years).</li>
    <li>Enquiries: up to 2 years, unless they turn into an order.</li>
    <li>Server logs: up to 90 days.</li>
</ul>

<h2>Keeping it safe</h2>
<p>Encrypted connections (HTTPS) everywhere, scrambled passwords and device keys, access limited to people who need it, and regular backups. If a breach ever affects you, we'll tell you and the Information Regulator as POPIA requires.</p>

<h2>Your rights</h2>
<ul>
    <li>Ask what personal information we hold about you, and get a copy.</li>
    <li>Ask us to correct or delete it, or object to how we use it.</li>
    <li>Export your farm records yourself at any time (Kuddebestuur → Data).</li>
    <li>Withdraw consent for marketing at any time.</li>
    <li>Complain to the <strong>Information Regulator</strong> (inforegulator.org.za · complaints.IR@inforegulator.org.za) if you're not happy with how we handled it.</li>
</ul>
<p>Email {{ $l['email'] }} to use any of these rights. We'll respond within 30 days.</p>

<h2>Cookies</h2>
<p>We only use the cookies needed to keep you signed in and protect forms. See <a href="{{ route('legal.show', 'cookies') }}">Cookies</a>.</p>
@endsection
