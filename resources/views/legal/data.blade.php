@extends('legal._layout')
@section('legal')
<p>Your herd book is yours, boet. Here's exactly how we handle it.</p>
<h2>Ownership</h2>
<ul>
    <li>You own the records you put into Kuddebestuur and that your devices send — animals, weights, pedigrees, health records, water visits and custom readings.</li>
    <li>We store and process them only to run the software for you. We don't sell them, share them or use them to market to you.</li>
    <li>We may use anonymous, combined statistics (for example "average sync time") to improve the product — never anything that identifies you, your farm or your animals.</li>
</ul>
<h2>Getting it out</h2>
<p>Kuddebestuur → Data lets you download every record as CSV, or everything at once as a ZIP backup, any time — no charge, no asking.</p>
<h2>Device connections</h2>
<ul>
    <li>Each device gets its own secret key (stored scrambled on our side). Anyone with the key can send data as that device, so keep it on the device only.</li>
    <li>If a device is lost or stolen, issue a new key under Devices — the old one stops working immediately.</li>
    <li>Devices connect over HTTPS. Our reference firmware stores reads on the device first, so nothing is lost if the connection drops.</li>
    <li>If you build your own device, you're responsible for what it sends. Keep requests to a sensible rate.</li>
</ul>
<h2>Accuracy</h2>
<p>Figures and alerts are only as good as the data that goes in — a mis-read tag or a scale that isn't zeroed will show up in the numbers. Check before you make big decisions.</p>
@endsection
