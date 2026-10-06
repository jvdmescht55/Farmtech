@extends('legal._layout')
@section('legal')
<p>These terms apply when you use farmtech.site, including <strong>Herd Manager</strong> (our herd management software) and any device connected to it. By creating an account or using the site you agree to them. If you buy a device, our <a href="{{ route('legal.show', 'sale') }}">terms of sale</a> apply to that purchase too.</p>
@include('legal._who')

<h2>Your account</h2>
<ul>
    <li>Herd Manager accounts are opened with the activation code that comes with a Farmtech device. Custom devices can be used with any account.</li>
    <li>Keep your password and device keys private. You're responsible for what happens under your account; tell us straight away if you think someone else has access.</li>
    <li>You must be 18 or older, or have a parent or guardian agree on your behalf.</li>
</ul>

<h2>The software</h2>
<ul>
    <li>We give you a non-exclusive, non-transferable right to use Herd Manager for your own farming operation for as long as your account is active.</li>
    <li>The software that comes with a Farmtech device has no monthly fee. If we ever introduce paid extras, we'll tell you first, and you won't lose what you already have while the service runs.</li>
    <li>We keep improving things and may change features. We'll try hard not to take away something you rely on, and will give notice before any major change.</li>
    <li>We aim for the site to be available all the time, but we don't promise it will never be down: maintenance, internet outages and things outside our control happen. Devices store reads and sync later, so a short outage shouldn't lose data.</li>
    <li>Herd Manager, the free tools (such as the auction calculator and market prices) and the device connection service are provided <strong>“as is” and “as available”</strong>, to the extent the law allows.</li>
</ul>

<h2>Changing, pausing or ending the service</h2>
<ul>
    <li>We may change, suspend or <strong>permanently stop</strong> farmtech.site, Herd Manager, the device connection service, the free tools, or any part of them, at any time and for any reason, including closing the business.</li>
    <li>Where we reasonably can, we'll give account holders at least <strong>30 days' notice</strong> by email and on the site, so you have time to export your records. If we have to act sooner (for example for legal, security or financial reasons outside our control), we'll give as much notice as we reasonably can.</li>
    <li>When the service ends, your account and the data in it will be deleted after the notice period (except what the law makes us keep). <strong>Export your data</strong> before then: Herd Manager → Import &amp; export gives you everything as CSV or a ZIP backup.</li>
    <li>Your devices keep working on their own: a KraalTrac keeps storing reads on the device, and you can still copy them off over USB. Online features (syncing, the herd book, alerts, pairing and updates) stop when the service stops.</li>
    <li>Herd Manager comes free with a device, so stopping it is not a refund event in itself. If you've paid us in advance for a service we stop, we'll refund the part you haven't used. This doesn't limit your rights under the Consumer Protection Act on the device itself (including the warranty).</li>
    <li>We're not obliged to keep releasing updates, new features or support for a device, but we'll tell you if we stop.</li>
</ul>

<h2>Market prices and free tools</h2>
<p>Market prices on the site come from third parties (such as the Red Meat Producers Organisation) and are shown for general information only. The auction calculator and other free tools give estimates from the numbers you enter. Neither is financial advice; check with your agent or buyer before you rely on a figure.</p>

<h2>Alerts and figures are a help, not a vet</h2>
<p>Herd Manager highlights things like weight loss, missed drinks, low birth weights and due dates from the data you and your devices give it. These are <strong>aids to your own judgement</strong>, not veterinary, breeding or financial advice. Always check the animal, and call your vet when something looks wrong. Pedigree tiers and breeding values are calculated from what's recorded and don't replace your breed society's official records.</p>

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
<p>We may update these terms; the date at the top shows the latest version. When we make important changes, we'll ask you to accept the new terms the next time you sign in; if you don't agree, you can export your data and close your account. These terms are governed by South African law, and South African courts have jurisdiction.</p>
@endsection
