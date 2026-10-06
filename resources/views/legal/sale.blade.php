@extends('legal._layout')
@section('legal')
<p>These terms apply when you order a device (such as the KraalTrac Pro) from farmtech.site. They're written to meet the Electronic Communications and Transactions Act (ECTA) and the Consumer Protection Act (CPA).</p>
@include('legal._who')

<h2>Ordering</h2>
<ul>
    <li>Every product page shows what the device does, its main specifications and what's in the box.</li>
    <li>Prices are in South African rand. We'll confirm the full total — including delivery and VAT where applicable — before you pay. Nothing is charged until you've confirmed.</li>
    <li>Many devices are <strong>built to order</strong>. We'll give you an estimated build and delivery time when we confirm your order, and keep you posted if anything changes.</li>
    <li>A contract is formed when we confirm your order in writing (email or WhatsApp) and you've paid. If we can't fulfil the order, we'll refund you in full.</li>
</ul>

<h2>Payment</h2>
<p>We'll confirm the available payment options with your order (for example EFT). We never ask for card details over email, phone or WhatsApp. Any online payment is handled by a recognised payment provider over a secure connection.</p>

<h2>Delivery, cooling-off and returns</h2>
<p>See <a href="{{ route('legal.show', 'shipping') }}">Delivery</a> and <a href="{{ route('legal.show', 'returns') }}">Returns, cooling-off & warranty</a> — including your right to cancel within 7 days of receiving a device ordered online.</p>

<h2>Software that comes with a device</h2>
<p>Each device includes an activation code for the matching Herd Manager section, governed by our <a href="{{ route('legal.show', 'terms') }}">terms of use</a>. The software is included free of charge and may be changed or stopped as set out there (with notice where we reasonably can). The device itself keeps storing reads offline and can be read over USB.</p>

<h2>Reservations</h2>
<ul>
    <li>When a product is sold out or not yet available, you can <strong>reserve</strong> one from the next batch. A reservation is not a purchase and costs nothing.</li>
    <li>When your unit is ready we contact you to confirm the final price and delivery date. A contract is only formed once you've confirmed and paid.</li>
    <li>You can cancel a reservation at any time before that. We may also cancel reservations (for example if a batch is cancelled or a product is discontinued); we'll let you know, and nothing is owed either way.</li>
</ul>

<h2>Custom programming and custom builds</h2>
<p>Where we set a device up to your instructions (ID format, questions, fields, language), we program it to match what we agreed with you. Custom-built devices are quoted separately before any work starts; the quote states what's included, the price and the expected delivery time.</p>

<h2>Complaints</h2>
<p>Talk to us first — most things get sorted quickly. If we can't resolve it, you can contact the <strong>Consumer Goods and Services Ombud</strong> (cgso.org.za) or the <strong>National Consumer Commission</strong> (thencc.org.za).</p>
@endsection
