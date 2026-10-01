@extends('layouts.herd', ['module' => 'watch'])
@section('tabs')
    @include('partials.tab', ['href' => route('watch.dashboard'), 'label' => 'Today', 'icon' => 'drop', 'on' => request()->routeIs('watch.dashboard')])
    @include('partials.tab', ['href' => route('watch.animals'), 'label' => 'Animals', 'icon' => 'herd', 'on' => request()->routeIs('watch.animals')])
    @include('partials.tab', ['href' => route('watch.points'), 'label' => 'Water points & gates', 'icon' => 'pin', 'on' => request()->routeIs('watch.points')])
    @include('partials.tab', ['href' => route('rfid.animals.index'), 'label' => 'Herd book ↗', 'icon' => 'list', 'on' => false])
@endsection
