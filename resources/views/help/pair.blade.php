@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Switch the device on near Wi-Fi', 'body' => '<p>It needs internet once to pair. A phone hotspot is fine.</p>'])
    @include('help._step', ['n' => 2, 'title' => 'Read the 6-digit code off its screen', 'body' => '<p>The code is valid for 15 minutes. If it runs out the device just shows a new one.</p>'])
    @include('help._step', ['n' => 3, 'title' => 'Type it in', 'body' => '<p>KraalTrac Pro → More → Devices, or KraalTrac Watch → Water points. Click <strong>Pair it</strong>. A few seconds later the device says it\'s paired.</p>', 'cta' => [route('rfid.readers.index'), 'Open Devices']])
    @include('help._step', ['n' => 4, 'title' => 'Lost or stolen device?', 'body' => '<p>Devices → <strong>New key</strong>. The old key stops working immediately; pair the device again when you have it back.</p>'])
@endsection
