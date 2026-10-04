{{--
    Public site layout. @section('hero_dark') set => header starts transparent
    with light type over a full-bleed hero, and turns solid on scroll.
--}}
@php
    $overHero = trim($__env->yieldContent('hero_dark')) !== '';
    $nav = [
        [route('site.store').'#pro', 'KraalTrac Pro'],
        [route('site.store').'#software', 'Herd Manager'],
        [route('site.custom'), 'Custom builds'],
        [route('site.contact'), 'Contact'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en-ZA">
<head>
    @include('partials.head')
    <title>@yield('title', 'Farmtech — Ken jou kudde')</title>
    <meta name="description" content="@yield('description', 'Farmtech KraalTrac devices and Herd Manager herd software — scan in the kraal, see every kilo, every animal, every day. Built around your farm.')">
    @stack('head')
</head>
<body class="bg-sand text-char font-ui antialiased [font-feature-settings:'ss01']"
      x-data="{ scrolled: false, menu: false }"
      x-init="scrolled = window.scrollY > 24"
      @scroll.window.passive="scrolled = window.scrollY > 24"
      :class="menu && 'overflow-hidden'">

<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-full focus:bg-ochre focus:text-char focus:px-5 focus:py-3 focus:text-sm">Skip to content</a>
<header class="fixed inset-x-0 top-0 z-50 transition-colors duration-500"
        :class="(scrolled || menu || {{ $overHero ? 'false' : 'true' }}) ? 'bg-sand/85 backdrop-blur-xl border-b border-hairline text-char' : 'bg-transparent border-b border-transparent {{ $overHero ? 'text-white' : 'text-char' }}'"
        style="padding-top: env(safe-area-inset-top, 0px)">
    <div class="wrap h-[76px] flex items-center gap-8">
        <a href="{{ route('site.store') }}" class="shrink-0 flex items-baseline gap-1.5" aria-label="Farmtech store">
            <span class="font-headline text-[30px] leading-none tracking-[-0.02em]">farmtech</span>
            <span class="w-1.5 h-1.5 rounded-full bg-ochre translate-y-[-2px]"></span>
        </a>

        <nav class="hidden md:flex items-center gap-8 text-[15px] mx-auto">
            @foreach ($nav as [$href, $label])
                <a href="{{ $href }}" class="link-u opacity-80 hover:opacity-100 transition-opacity">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="hidden md:flex items-center gap-5 shrink-0">
            <a href="{{ auth()->check() ? route('herd.hub') : route('landing') }}" class="text-[15px] opacity-80 hover:opacity-100">{{ auth()->check() ? 'My herd' : 'Sign in' }}</a>
            <a href="{{ route('site.store') }}#order"
               class="btn btn-sm"
               :class="(scrolled || {{ $overHero ? 'false' : 'true' }}) ? 'bg-char text-sand hover:bg-char-soft' : 'bg-white text-char hover:bg-sand'">Reserve yours</a>
        </div>

        <button type="button" class="md:hidden ml-auto -mr-2 w-11 h-11 grid place-items-center" @click="menu = !menu" :aria-expanded="menu" aria-label="Menu">
            <span class="relative block w-6 h-3">
                <span class="absolute left-0 top-0 h-[1.5px] w-6 bg-current transition-transform duration-300" :class="menu && 'translate-y-[5px] rotate-45'"></span>
                <span class="absolute left-0 bottom-0 h-[1.5px] w-6 bg-current transition-transform duration-300" :class="menu && '-translate-y-[5px] -rotate-45'"></span>
            </span>
        </button>
    </div>

</header>
    {{-- Mobile menu — outside the header on purpose: the header's backdrop-blur would trap a fixed child inside its 76px. --}}
<div x-show="menu" x-cloak x-transition.opacity.duration.300ms
     class="md:hidden fixed inset-x-0 bottom-0 top-[calc(76px+env(safe-area-inset-top,0px))] z-40 bg-sand text-char overflow-y-auto">
    <div class="wrap py-10 flex flex-col min-h-full" style="padding-bottom: max(2.5rem, env(safe-area-inset-bottom, 0px))">
        <nav class="flex flex-col">
            @foreach ([[route('site.store'), 'Store'], ...$nav] as [$href, $label])
                <a href="{{ $href }}" @click="menu = false" class="font-headline text-5xl py-3 border-b border-hairline">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="mt-auto pt-10 grid gap-3">
            <a href="{{ route('site.store') }}#order" @click="menu = false" class="btn-dark w-full">Reserve yours</a>
            <a href="{{ auth()->check() ? route('herd.hub') : route('landing') }}" class="btn-line w-full">Herd Manager →</a>
        </div>
    </div>
</div>


<main id="main">
    @yield('content')
</main>

<footer class="bg-char text-sand/70">
    <div class="wrap pt-24 pb-10" style="padding-bottom: max(2.5rem, env(safe-area-inset-bottom, 0px))">
        <div class="grid lg:grid-cols-12 gap-12">
            <div class="lg:col-span-6">
                <div class="font-headline text-sand text-[clamp(3.5rem,9vw,8rem)] leading-[0.85] tracking-[-0.03em]">Ken jou<br>kudde.</div>
                <p class="mt-6 max-w-sm">KraalTrac devices &amp; Herd Manager. Built for the kraal, not the office. And built around your farm.</p>
            </div>
            <div class="lg:col-span-6 grid grid-cols-2 sm:grid-cols-3 gap-8 text-[15px]">
                <div>
                    <div class="eyebrow text-sand/40 mb-4">Farmtech</div>
                    <ul class="space-y-2.5">
                        <li><a class="hover:text-sand" href="{{ route('portal') }}">Home</a></li>
                        <li><a class="hover:text-sand" href="{{ route('site.store') }}">Store</a></li>
                        <li><a class="hover:text-sand" href="{{ route('landing') }}">Herd Manager</a></li>
                        <li><a class="hover:text-sand" href="{{ route('site.custom') }}">Custom builds</a></li>
                        <li><a class="hover:text-sand" href="{{ route('site.suggest') }}">Suggest a device</a></li>
                        <li><a class="hover:text-sand" href="{{ route('site.contact') }}">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <div class="eyebrow text-sand/40 mb-4">Account</div>
                    <ul class="space-y-2.5">
                        <li><a class="hover:text-sand" href="{{ route('login') }}">Sign in</a></li>
                        <li><a class="hover:text-sand" href="{{ route('register') }}">Activate a device</a></li>
                    </ul>
                </div>
                <div>
                    <div class="eyebrow text-sand/40 mb-4">Legal</div>
                    <ul class="space-y-2.5">
                        <li><a class="hover:text-sand" href="{{ route('legal.show', 'privacy') }}">Privacy (POPIA)</a></li>
                        <li><a class="hover:text-sand" href="{{ route('legal.show', 'terms') }}">Terms of use</a></li>
                        <li><a class="hover:text-sand" href="{{ route('legal.show', 'sale') }}">Terms of sale</a></li>
                        <li><a class="hover:text-sand" href="{{ route('legal.show', 'returns') }}">Returns &amp; warranty</a></li>
                        <li><a class="hover:text-sand" href="{{ route('legal.index') }}">All legal</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="mt-20 pt-6 border-t border-sand/10 flex flex-col md:flex-row md:items-center justify-between gap-4 text-[13px] text-sand/40">
            <span>© {{ date('Y') }} {{ config('legal.legal_name') ?: 'Farmtech' }} · South Africa · Lekker boer.</span>
            @hasSection('credits')<div class="max-w-3xl md:text-right">@yield('credits')</div>@endif
        </div>
    </div>
</footer>
@stack('scripts')
</body>
</html>
