@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Switch it on and give it Wi-Fi', 'body' => '<p>New devices open their own Wi-Fi (<em>KraalTrac-…</em>). Join it on your phone and choose your farm Wi-Fi or phone hotspot. It needs internet once to pair.</p>'])
    @include('help._step', ['n' => 2, 'title' => 'Read the 6-digit code off its screen', 'body' => '<p>The code is valid for 15 minutes. If it runs out the device just shows a new one.</p>'])
    @include('help._step', ['n' => 3, 'title' => 'Type it in', 'body' => '<p>Go to <strong>farmtech.site/pair</strong> and type the 6 numbers. The page ticks green when the device has linked.</p>', 'cta' => [route('pair'), 'Open farmtech.site/pair']])
    @include('help._step', ['n' => 4, 'title' => 'Lost or stolen device?', 'body' => '<p>Devices → <strong>New key</strong>. The old key stops working immediately; pair the device again when you have it back.</p>'])
@endsection
