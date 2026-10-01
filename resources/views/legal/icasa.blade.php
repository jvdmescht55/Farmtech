@extends('legal._layout')
@section('legal')
<p>Farmtech devices use radio: 134.2 kHz to read animal ear tags and Wi-Fi to sync. In South Africa, radio equipment sold to the public must be <strong>type-approved by ICASA</strong> (the Independent Communications Authority of South Africa) and used within the licence-exempt bands.</p>
<h2>Our devices</h2>
<ul>
    <li><strong>Tag reading:</strong> 134.2 kHz low-frequency RFID (ISO 11784/11785 FDX-B), the standard for animal identification.</li>
    <li><strong>Wi-Fi:</strong> 2.4 GHz, through an ESP32 radio module.</li>
    <li><strong>ICASA type approval:</strong> {!! filled(config('legal.icasa_approval')) ? e(config('legal.icasa_approval')) : '<mark class="bg-ochre/20 text-ochre-dark px-1 rounded">[approval number(s) to be completed]</mark>' !!}</li>
</ul>
<h2>Using them</h2>
<p>Don't modify the radio parts or antennas beyond what we supply — that can take a device outside its approval. Custom devices you build yourself are your responsibility to keep within the rules.</p>
@endsection
