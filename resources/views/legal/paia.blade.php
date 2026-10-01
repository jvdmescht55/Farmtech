@extends('legal._layout')
@section('legal')
<p>The Promotion of Access to Information Act (PAIA) gives you the right to ask for records we hold, subject to the grounds for refusal in the Act. POPIA also lets you ask what personal information we hold about you.</p>
@include('legal._who')
<h2>How to ask</h2>
<ul>
    <li>For your own personal information or farm records, just email us — or download your farm records yourself under Herd Manager → Data. There's no fee for this.</li>
    <li>For other records, send a written request (the prescribed PAIA form is available from the Information Regulator, inforegulator.org.za) to our Information Officer at {{ config('legal.email') }}.</li>
    <li>We'll respond within 30 days, as the Act requires.</li>
</ul>
<h2>Records we keep</h2>
<p>Customer accounts and farm records, orders and invoices, enquiries and suggestions, device registrations, and statutory company and tax records.</p>
<p>A copy of our PAIA manual, where one is required, is available on request.</p>
@endsection
