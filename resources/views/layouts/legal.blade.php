@extends('layouts.site')
@section('content')
    <div class="pt-28 pb-24 bg-sand">
        <div class="wrap max-w-4xl [&_h1]:font-headline [&_h1]:font-normal [&_h2]:font-headline [&_h2]:font-normal [&_h1]:text-char [&_h2]:text-char">
            @yield('policy')
        </div>
    </div>
@endsection
