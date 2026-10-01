@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Mount it where they drink', 'body' => '<p>Trough or a gate they walk through every day. The antenna needs to be within reach of the ear tags.</p>'])
    @include('help._step', ['n' => 2, 'title' => 'Pair it and name the spot', 'body' => '<p>KraalTrac Watch → <strong>Water points &amp; gates</strong> → type the 6-digit code and where it is (e.g. "Trough — Bergkamp").</p>', 'cta' => [route('watch.points'), 'Water points']])
    @include('help._step', ['n' => 3, 'title' => 'Set the limit', 'body' => '<p>"Shout after" — how many hours without a drink before we warn you. 24 by default; hot camps might want less.</p>'])
    @include('help._step', ['n' => 4, 'title' => 'Check "Who came to drink?"', 'body' => '<p>Head count today, who missed, and when they drink. Missed-drink alerts also show on the KraalTrac Pro overview and the animal\'s page.</p>'])
@endsection
