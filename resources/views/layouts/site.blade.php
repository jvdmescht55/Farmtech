{{--
    Public site layout. @section('hero_dark') set => header starts transparent
    with light type over a full-bleed hero, and turns solid on scroll.
--}}
@php
    $shopCart = app(\App\Services\Shop\ShopCart::class);
    $cartCount = $shopCart->count();
    $overHero = trim($__env->yieldContent('hero_dark')) !== '';
    $nav = [
        [route('site.store').'#shop', 'Shop'],
        [route('site.store').'#see', 'See it work'],
        [route('site.store').'#pricing', 'Pricing'],
        [route('site.custom'), 'Custom builds'],
        [route('site.prices'), 'Market prices'],
        [route('site.calculator'), 'Auction calculator'],
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
      x-data="{ scrolled: false, menu: false, cart: {{ session('cart_added') ? 'true' : 'false' }} }"
      x-init="scrolled = window.scrollY > 24"
      @scroll.window.passive="scrolled = window.scrollY > 24"
      :class="(menu || cart) && 'overflow-hidden'" @keydown.escape.window="cart = false">

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
            <button type="button" @click="cart = true" class="relative w-10 h-10 grid place-items-center rounded-full hover:bg-black/5" aria-label="Cart ({{ $cartCount }})">
                <svg class="w-[22px] h-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7h14l-1.2 11.1a2 2 0 01-2 1.9H8.2a2 2 0 01-2-1.9L5 7zM9 7V6a3 3 0 016 0v1"/></svg>
                @if ($cartCount)<span class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-ochre text-char text-[11px] font-semibold grid place-items-center">{{ $cartCount }}</span>@endif
            </button>
            <a href="{{ route('site.store') }}#shop"
               class="btn btn-sm"
               :class="(scrolled || {{ $overHero ? 'false' : 'true' }}) ? 'bg-char text-sand hover:bg-char-soft' : 'bg-white text-char hover:bg-sand'">Shop now</a>
        </div>

        <div class="md:hidden ml-auto"><button type="button" @click="cart = true" class="relative w-11 h-10 grid place-items-center rounded-full hover:bg-black/5" aria-label="Cart ({{ $cartCount }})">
                <svg class="w-[22px] h-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7h14l-1.2 11.1a2 2 0 01-2 1.9H8.2a2 2 0 01-2-1.9L5 7zM9 7V6a3 3 0 016 0v1"/></svg>
                @if ($cartCount)<span class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-ochre text-char text-[11px] font-semibold grid place-items-center">{{ $cartCount }}</span>@endif
            </button></div>
        <button type="button" class="md:hidden -mr-2 w-11 h-11 grid place-items-center" @click="menu = !menu" :aria-expanded="menu" aria-label="Menu">
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
            <a href="{{ route('site.store') }}#shop" @click="menu = false" class="btn-dark w-full">Shop now</a>
            <a href="{{ auth()->check() ? route('herd.hub') : route('landing') }}" class="btn-line w-full">Herd Manager →</a>
        </div>
    </div>
</div>

{{-- Cart drawer --}}
<div x-show="cart" x-cloak class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Your cart">
    <div class="absolute inset-0 bg-char/50" x-show="cart" x-transition.opacity @click="cart = false"></div>
    <aside class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-sand text-char flex flex-col shadow-2xl"
           x-show="cart" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           style="padding-top: env(safe-area-inset-top, 0px); padding-bottom: env(safe-area-inset-bottom, 0px)">
        <div class="flex items-center justify-between px-6 h-[76px] border-b border-hairline">
            <div class="font-headline text-3xl">Your cart</div>
            <button type="button" @click="cart = false" class="w-10 h-10 rounded-full border border-hairline grid place-items-center text-xl" aria-label="Close">×</button>
        </div>
        @if (session('cart_added'))<div class="mx-6 mt-5 rounded-xl bg-[#3F7A3A]/10 text-[#2f5d2c] px-4 py-3 text-sm">✓ {{ session('cart_added') }}.</div>@endif
        @php($cartLines = $shopCart->lines())
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
            @forelse ($cartLines as $line)
                @include('shop._line', ['line' => $line, 'compact' => true])
            @empty
                <div class="py-16 text-center">
                    <div class="font-headline text-3xl">Nothing in here yet.</div>
                    <a href="{{ route('site.store') }}#shop" @click="cart = false" class="btn-line mt-6">Browse the shop</a>
                </div>
            @endforelse
        </div>
        @if ($cartLines->isNotEmpty())
            <div class="border-t border-hairline px-6 py-5 space-y-3">
                @php($dueNow = $shopCart->dueNow($cartLines))
                @if ($dueNow)<div class="flex justify-between"><span>To pay now</span><span class="font-headline text-2xl">{{ \App\Models\StoreListing::rand($dueNow) }}</span></div>@endif
                @if (config('shop.free_courier_over_cents') && $dueNow && $dueNow < config('shop.free_courier_over_cents'))<p class="text-sm text-stone">Add {{ \App\Models\StoreListing::rand(config('shop.free_courier_over_cents') - $dueNow) }} more for free courier.</p>@endif
                @if ($cartLines->where('reserve', true)->isNotEmpty())<p class="text-sm text-stone">Reserved items are paid only when your batch is ready. You can cancel any time before.</p>@endif
                <a href="{{ route('shop.checkout') }}" class="btn-dark w-full">{{ $dueNow ? 'Checkout' : 'Confirm my reservation' }}</a>
                <a href="{{ route('shop.cart') }}" class="block text-center text-sm underline text-stone">View full cart</a>
            </div>
        @endif
    </aside>
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
                        <li><a class="hover:text-sand" href="{{ route('site.prices') }}">Market prices</a></li>
                        <li><a class="hover:text-sand" href="{{ route('site.calculator') }}">Auction calculator</a></li>
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
            <span>© {{ date('Y') }} {{ config('legal.legal_name') ?: 'Farmtech' }} · South Africa · <a href="mailto:{{ config('legal.email') }}" class="hover:text-sand">{{ config('legal.email') }}</a>@if (config('legal.phone')) · <a href="tel:{{ preg_replace('/\s+/', '', config('legal.phone')) }}" class="hover:text-sand">{{ config('legal.phone') }}</a>@endif</span>
            @hasSection('credits')<div class="max-w-3xl md:text-right">@yield('credits')</div>@endif
        </div>
    </div>
</footer>
@php($wa = preg_replace('/[^0-9]/', '', (string) \App\Models\Setting::get('support_whatsapp', '')))
@if ($wa)
    <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hi Farmtech, I have a question about ') }}" target="_blank" rel="noopener" class="fixed z-30 right-5 bottom-5 w-14 h-14 rounded-full bg-[#25D366] text-white shadow-[0_14px_40px_-10px_rgba(0,0,0,.45)] grid place-items-center hover:scale-105 transition" style="margin-bottom: env(safe-area-inset-bottom, 0px)" aria-label="WhatsApp us">
        <svg viewBox="0 0 24 24" class="w-7 h-7" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.2a8.2 8.2 0 01-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1112 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 01-3.3-2.9c-.3-.4.3-.4.7-1.3a.5.5 0 000-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 00-.7.3 3 3 0 00-.9 2.2 5.2 5.2 0 001.1 2.8 11.9 11.9 0 004.6 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 001.8-1.2 2.2 2.2 0 00.1-1.2c0-.1-.2-.2-.4-.3z"/></svg>
    </a>
@endif
@stack('scripts')
</body>
</html>
