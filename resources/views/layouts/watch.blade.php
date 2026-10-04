@extends('layouts.herd', ['module' => 'watch'])
@php
    $nav = [
        ['href' => route('watch.dashboard'), 'label' => 'Today', 'icon' => 'drop', 'on' => request()->routeIs('watch.dashboard')],
        ['href' => route('watch.animals'), 'label' => 'Animals', 'icon' => 'herd', 'on' => request()->routeIs('watch.animals')],
        ['href' => route('watch.points'), 'label' => 'Water points & gates', 'icon' => 'pin', 'on' => request()->routeIs('watch.points')],
    ];
    $navMore = [
        ['href' => route('rfid.animals.index'), 'label' => 'Herd book (KraalTrac Pro)', 'icon' => 'list', 'on' => false],
    ];
@endphp
