@extends('layouts.herd', ['module' => 'custom'])
@php
    $nav = [
        ['href' => route('custom.index'), 'label' => 'My devices', 'icon' => 'plug', 'on' => request()->routeIs('custom.*')],
        ['href' => route('herd.suggest'), 'label' => 'Want us to build one?', 'icon' => 'bulb', 'on' => false],
    ];
@endphp
