<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>@yield('title', 'Dashboard') — Farmtech RFID</title>
</head>
<body class="bg-canvas text-charcoal antialiased font-body" x-data="{ nav: false }">
@php
    $links = [
        ['rfid.dashboard', 'rfid.dashboard', 'Overview', 'M3 12l9-9 9 9M5 10v10h14V10'],
        ['rfid.animals.index', 'rfid.animals.*', 'Herd register', 'M4 6h16M4 12h16M4 18h10'],
        ['rfid.readers.index', 'rfid.readers.*|rfid.sync.*', 'Readers & sync', 'M4 7h16v10H4zM8 11h.01M12 11h4'],
        ['rfid.catalogues.index', 'rfid.catalogues.*', 'Sale catalogues', 'M6 4h9l3 3v13H6zM9 10h6M9 14h6'],
        ['rfid.import.create', 'rfid.import.*', 'Import herd', 'M12 4v12m0 0l-4-4m4 4l4-4M4 20h16'],
        ['rfid.settings.edit', 'rfid.settings.*', 'Farm settings', 'M12 15a3 3 0 100-6 3 3 0 000 6zM19 12l2-1-2-4-2 1-2-2V4h-4v2L9 7 7 6 5 10l2 1v2l-2 1 2 4 2-1 2 2v2h4v-2l2-2 2 1 2-4-2-1z'],
    ];
@endphp
<div class="min-h-screen lg:flex">
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-brand-975 text-white/85 flex flex-col transform transition lg:translate-x-0 lg:static"
           :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="px-6 pt-6 pb-5 border-b border-white/10" style="padding-top: max(1.5rem, env(safe-area-inset-top, 0px))">
            <a href="{{ route('rfid.dashboard') }}" class="block">
                <div class="font-display text-2xl font-semibold text-white tracking-tight">Farm<span class="text-mint">tech</span></div>
                <div class="text-[11px] uppercase tracking-[0.2em] text-white/50 mt-0.5">RFID Herd Manager</div>
            </a>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            @foreach ($links as [$route, $pattern, $label, $icon])
                @php($active = collect(explode('|', $pattern))->contains(fn ($p) => request()->routeIs($p)))
                <a href="{{ route($route) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ $active ? 'bg-white/10 text-white font-semibold' : 'hover:bg-white/5' }}">
                    <svg class="w-4 h-4 shrink-0 {{ $active ? 'text-mint' : 'text-white/50' }}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            @endforeach
            @if (auth()->user()->canAccessAdminPanel())
                <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 text-alert-light mt-4">← Admin panel</a>
            @endif
        </nav>
        <div class="px-6 py-4 border-t border-white/10 text-xs" style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 0px))">
            <div class="text-white font-medium truncate">{{ auth()->user()->farm_name ?: auth()->user()->name }}</div>
            <div class="text-white/50 truncate">{{ auth()->user()->email }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
                <button class="text-white/60 hover:text-white">Sign out</button>
            </form>
        </div>
    </aside>
    <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>

    <div class="flex-1 min-w-0">
        <header class="sticky z-20 bg-white/90 backdrop-blur border-b border-border" style="top: env(safe-area-inset-top, 0px)">
            <div class="px-4 sm:px-8 h-16 flex items-center gap-4">
                <button class="lg:hidden -ml-1 p-2 rounded-md hover:bg-canvas" @click="nav = true" aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="font-display text-xl font-semibold truncate">@yield('title', 'Overview')</h1>
                <div class="ml-auto flex items-center gap-2">@yield('actions')</div>
            </div>
        </header>
        <main class="px-4 sm:px-8 py-8 max-w-[1400px]">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
