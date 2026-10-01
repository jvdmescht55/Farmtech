@extends('layouts.herd', ['module' => 'custom'])
@section('tabs')
    @include('partials.tab', ['href' => route('custom.index'), 'label' => 'My devices', 'icon' => 'plug', 'on' => request()->routeIs('custom.*')])
    @include('partials.tab', ['href' => route('herd.suggest'), 'label' => 'Want us to build one?', 'icon' => 'bulb', 'on' => false])
@endsection
