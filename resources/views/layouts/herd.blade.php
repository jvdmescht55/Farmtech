{{--
    Shared frame for every Herd Manager section. A section layout supplies
    $module (key in config('herd.modules')) and @section('tabs').
--}}
@php
    $u = auth()->user();
    $current = $module ?? null;
    $modules = collect(config('herd.modules'))->map(fn ($m, $k) => $m + ['key' => $k, 'unlocked' => $u->hasModule($k)]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>@yield('title', 'Herd Manager') — {{ $current ? config("herd.modules.$current.name") : 'Herd Manager' }} · Farmtech</title>
</head>
<body class="bg-sand text-char font-ui antialiased min-h-screen bg-[radial-gradient(120%_60%_at_50%_0%,#EFE8DA_0%,#F4F1EA_55%)] bg-no-repeat">
<header class="sticky top-0 z-40 bg-char text-sand" style="padding-top: env(safe-area-inset-top, 0px)">
    <div class="wrap h-16 flex items-center gap-3">
        <a href="{{ route('herd.hub') }}" class="flex items-baseline gap-1.5 shrink-0" title="All devices">
            <span class="font-headline text-[26px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span>
        </a>

        {{-- Section switcher: links every Herd Manager section together --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open" data-tour="switcher" class="flex items-center gap-2 rounded-full border border-white/15 px-4 h-9 text-sm hover:border-white/40 transition">
                <span class="truncate max-w-[11rem] sm:max-w-none">{{ $current ? config("herd.modules.$current.name") : 'All devices' }}</span>
                <svg class="w-3 h-3 text-sand/60 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 mt-2 w-80 rounded-2xl bg-white text-char border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
                @foreach ($modules as $m)
                    <a href="{{ $m['unlocked'] ? route($m['route']) : route('account.activate') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-sand-light {{ $current === $m['key'] ? 'bg-sand-light' : '' }}">
                        <span class="w-2 h-2 rounded-full {{ $m['unlocked'] ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span>
                        <span class="flex-1 min-w-0"><span class="block text-sm font-medium">{{ $m['name'] }}</span><span class="block text-xs text-stone truncate">{{ $m['kind'] }}</span></span>
                        @unless ($m['unlocked'])<span class="text-[11px] text-stone">Locked</span>@endunless
                    </a>
                @endforeach
                <div class="border-t border-hairline mt-1 pt-1">
                    <a href="{{ route('herd.hub') }}" class="block rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">All devices &amp; sections</a>
                    <a href="{{ route('herd.suggest') }}" class="block rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">Suggest a device or feature</a>
                    <a href="{{ route('help.index') }}" class="block rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">Help &amp; guides</a>
                </div>
            </div>
        </div>

        <div class="ml-auto relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-full pl-1 pr-1 sm:pr-3 h-10 hover:bg-white/5">
                <span class="w-8 h-8 rounded-full bg-ochre text-char grid place-items-center text-sm font-semibold">{{ mb_strtoupper(mb_substr($u->farm_name ?: $u->name, 0, 1)) }}</span>
                <span class="hidden md:block text-sm max-w-[14rem] truncate">{{ $u->farm_name ?: $u->name }}</span>
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-60 rounded-2xl bg-white text-char border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
                <div class="px-3 py-2.5 border-b border-hairline mb-1"><div class="text-sm font-medium truncate">{{ $u->name }}</div><div class="text-xs text-stone truncate">{{ $u->email }}</div></div>
                <a href="{{ route('rfid.settings.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Farm settings</a>
                <a href="{{ route('account.activate') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Activate a device</a>
                <a href="{{ route('herd.suggest') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Suggestions</a>
                @if ($u->canAccessAdminPanel())<a href="{{ url('/admin') }}" class="block rounded-lg px-3 py-2 text-sm text-ochre-dark hover:bg-sand-light">Admin</a>@endif
                <form method="POST" action="{{ route('logout') }}" class="border-t border-hairline mt-1 pt-1">@csrf<button class="w-full text-left rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Sign out</button></form>
            </div>
        </div>
    </div>
    @hasSection('tabs')
        <nav class="border-t border-white/10">
            <div class="wrap flex items-center gap-7 overflow-x-auto scrollbar-none" data-tour="tabs">@yield('tabs')</div>
        </nav>
    @endif
</header>

<main class="wrap py-10 sm:py-12" style="padding-bottom: max(3.5rem, env(safe-area-inset-bottom, 0px))">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-8">
        <div>
            @hasSection('eyebrow')<p class="eyebrow mb-3">@yield('eyebrow')</p>@endif
            <h1 class="h-display text-[clamp(2.4rem,4.6vw,3.6rem)]">@yield('title', 'Herd Manager')</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
    </div>
    @yield('subnav')
    @include('partials.toast')
    <div class="pop-in">@yield('content')</div>
</main>

{{-- Help is always one tap away --}}
<div class="fixed z-50 right-5 bottom-5" style="margin-bottom: env(safe-area-inset-bottom, 0px)" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <div x-show="open" x-cloak x-transition.origin.bottom.right class="absolute right-0 bottom-16 w-64 rounded-2xl bg-white border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
        <div class="px-3 pt-2 pb-1 eyebrow">Need a hand, boet?</div>
        <a href="{{ route('help.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">@include('partials.icon', ['name' => 'book'])Guides</a>
        @hasSection('tour')<button type="button" @click="open = false; window.dispatchEvent(new CustomEvent('start-tour'))" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">@include('partials.icon', ['name' => 'start'])Show me around this page</button>@endif
        <a href="{{ route('help.show', 'excel') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">@include('partials.icon', ['name' => 'table'])Upload from Excel</a>
        <a href="{{ route('help.show', 'esp32') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">@include('partials.icon', ['name' => 'chip'])Connect the scale</a>
        <a href="{{ route('herd.suggest') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">@include('partials.icon', ['name' => 'bulb'])Suggest something</a>
        <a href="{{ route('site.contact') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light">@include('partials.icon', ['name' => 'help'])Talk to a person</a>
    </div>
    <button type="button" @click="open = !open" data-tour="help" class="w-14 h-14 rounded-full bg-char text-sand shadow-[0_14px_40px_-10px_rgba(0,0,0,.55)] grid place-items-center hover:scale-105 transition" aria-label="Help">
        <span class="font-headline text-2xl" x-text="open ? '×' : '?'">?</span>
    </button>
</div>
@yield('tour')

</body>
</html>
