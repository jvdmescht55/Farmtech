@extends('legal._layout')
@section('legal')
<p>These terms apply when you use farmtech.site, including <strong>Kuddebestuur</strong> (our herd management software) and any device connected to it. By creating an account or using the site you agree to them. If you buy a device, our <a href="{{ route('legal.show', 'sale') }}">terms of sale</a> apply to that purchase too.</p>
@include('legal._who')

<h2>Your account</h2>
<ul>
    <li>Kuddebestuur accounts are opened with the activation code that comes with a Farmtech device. Custom devices can be used with any account.</li>
    <li>Keep your password and device keys private. You're responsible for what happens under your account; tell us straight away if you think someone else has access.</li>
    <li>You must be 18 or older, or have a parent or guardian agree on your behalf.</li>
</ul>

<h2>The software</h2>
<ul>
    <li>We give you a non-exclusive, non-transferable right to use Kuddebestuur for your own farming operation for as long as your account is active.</li>
    <li>The software that comes with a Farmtech device has no monthly fee. If we ever introduce paid extras, we'll tell you first and you won't lose what you already have.</li>
    <li>We keep improving things and may change features. We'll try hard not to take away something you rely on, and will give notice before any major change.</li>
    <li>We aim for the site to be available all the time, but we don't promise it will never be down — maintenance, internet outages and things outside our control happen. Devices store reads and sync later, so a short outage shouldn't lose data.</li>
</ul>

<h2>Alerts and figures are a help, not a vet</h2>
<p>Kuddebestuur highlights things like weight loss, missed drinks, low birth weights and due dates from the data you and your devices give it. These are <strong>aids to your own judgement</strong>, not veterinary, breeding or financial advice. Always check the animal, and call your vet when something looks wrong. Pedigree tiers and breeding values are calculated from what's recorded and don't replace your breed society's official records.</p>

<h2>Fair use</h2>
<ul>
    <li>Don't try to break, overload, reverse-engineer or get around the security of the site or the device API.</li>
    <li>Don't upload anything unlawful, or data you don't have the right to use.</li>
    <li>Device connections are meant for real farm devices — keep request rates reasonable.</li>
</ul>

<h2>Your data</h2>
<p>Your herd records belong to you. You can export them at any time, and we don't sell them. The details are in <a href="{{ route('legal.show', 'data') }}">Your farm data</a> and our <a href="{{ route('legal.show', 'privacy') }}">privacy policy</a>.</p>

<h2>Closing an account</h2>
<p>You can ask us to close your account at any time. Export your data first — we'll keep it for 30 days after closing in case you change your mind, then delete it (except what the law makes us keep). We may suspend an account that's being misused, after warning you where it's reasonable to.</p>

<h2>Liability</h2>
<p>Nothing in these terms takes away rights you have under the Consumer Protection Act or other law. Beyond that, and to the extent the law allows, we're not liable for indirect or consequential losses (such as lost profits or lost stock) from using or not being able to use the software, and our total liability for the software is limited to the amount you paid us in the 12 months before the claim.</p>

<h2>Changes and law</h2>
<p>We may update these terms; the date at the top shows the latest version and we'll let account holders know about important changes. These terms are governed by South African law.</p>
@endsection
