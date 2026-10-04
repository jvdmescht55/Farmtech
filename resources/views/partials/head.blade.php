@php
    $metaTitle = trim($__env->yieldContent('title')) ?: 'Farmtech — Know every animal. Every kilo.';
    $metaDesc = trim($__env->yieldContent('description')) ?: 'KraalTrac EID readers & weigh loggers with Herd Manager — weights, growth, alerts and auction books that build themselves. Built in South Africa, for South African farms.';
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#15140F">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="apple-mobile-web-app-title" content="Farmtech">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:site_name" content="Farmtech">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_ZA">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:image" content="{{ asset('images/og-farmtech.jpg') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
@if (request()->is('app*', 'admin*', 'activate*'))<meta name="robots" content="noindex, nofollow">@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
