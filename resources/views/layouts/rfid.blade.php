<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>@yield('title', 'Oorsig') — Kuddebestuur · Farmtech</title>
</head>
<body class="bg-sand text-char font-ui antialiased min-h-screen">
@php
    $tabs = [
        ['rfid.dashboard', 'rfid.dashboard', 'Oorsig'],
        ['rfid.animals.index', 'rfid.animals.*|rfid.import.*', 'Kudde'],
        ['rfid.weighings.index', 'rfid.weighings.*', 'Weegsessies'],
        ['rfid.compare', 'rfid.compare', 'Vergelyk'],
        ['rfid.draft', 'rfid.draft*', 'Sorteer'],
        ['rfid.catalogues.index', 'rfid.catalogues.*', 'Katalogusse'],
        ['rfid.readers.index', 'rfid.readers.*|rfid.sync.*', 'Lesers & sinch'],
    ];
    $u = auth()->user();
@endphp

<header class="sticky z-40 bg-char text-sand" style="top: 0; padding-top: env(safe-area-inset-top, 0px)">
    <div class="wrap h-16 flex items-center gap-4">
        <a href="{{ route('herd.hub') }}" class="flex items-baseline gap-1.5 shrink-0">
            <span class="font-headline text-[26px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span>
        </a>
        <span class="hidden sm:block text-sand/30">/</span>
        <span class="hidden sm:block text-sm text-sand/70 truncate">Kuddebestuur <span class="text-sand/30 mx-1">·</span> RFID Scanner V1</span>

        <div class="ml-auto relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open" class="flex items-center gap-3 rounded-full pl-1 pr-3 h-10 hover:bg-white/5 transition">
                <span class="w-8 h-8 rounded-full bg-ochre text-char grid place-items-center text-sm font-semibold">{{ mb_strtoupper(mb_substr($u->farm_name ?: $u->name, 0, 1)) }}</span>
                <span class="hidden md:block text-sm max-w-[16rem] truncate">{{ $u->farm_name ?: $u->name }}</span>
                <svg class="w-3.5 h-3.5 text-sand/60 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.right
                 class="absolute right-0 mt-2 w-64 rounded-2xl bg-white text-char border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
                <div class="px-3 py-2.5 border-b border-hairline mb-1">
                    <div class="text-sm font-medium truncate">{{ $u->name }}</div>
                    <div class="text-xs text-stone truncate">{{ $u->email }}</div>
                </div>
                <a href="{{ route('herd.hub') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">My kraal · alle sagteware</a>
                <a href="{{ route('rfid.settings.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Plaas-instellings</a>
                <a href="{{ route('rfid.import.create') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Voer stamregister in</a>
                <a href="{{ route('account.activate') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Aktiveer nog 'n toestel</a>
                @if ($u->canAccessAdminPanel())<a href="{{ route('admin.orders.index') }}" class="block rounded-lg px-3 py-2 text-sm text-ochre-dark hover:bg-sand-light">Admin</a>@endif
                <form method="POST" action="{{ route('logout') }}" class="border-t border-hairline mt-1 pt-1">@csrf
                    <button class="w-full text-left rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Teken uit</button>
                </form>
            </div>
        </div>
    </div>
    <nav class="border-t border-white/10">
        <div class="wrap flex gap-7 overflow-x-auto scrollbar-none">
            @foreach ($tabs as [$route, $pattern, $label])
                @php($on = collect(explode('|', $pattern))->contains(fn ($p) => request()->routeIs($p)))
                <a href="{{ route($route) }}" class="relative shrink-0 py-3.5 text-[14px] transition-colors {{ $on ? 'text-sand' : 'text-sand/55 hover:text-sand' }}">
                    {{ $label }}
                    @if ($on)<span class="absolute inset-x-0 -bottom-px h-[2px] bg-ochre rounded-full"></span>@endif
                </a>
            @endforeach
        </div>
    </nav>
</header>

<main class="wrap py-10 sm:py-14" style="padding-bottom: max(3.5rem, env(safe-area-inset-bottom, 0px))">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-10">
        <div>
            @hasSection('eyebrow')<p class="eyebrow mb-3">@yield('eyebrow')</p>@endif
            <h1 class="h-display text-[clamp(2.6rem,5vw,4rem)]">@yield('title', 'Oorsig')</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
    </div>
    @include('partials.flash')
    @yield('content')
</main>
</body>
</html>
