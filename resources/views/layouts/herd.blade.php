{{--
    Shared frame for every Herd Manager section. A section layout supplies
    $module (key in config('herd.modules')), $nav (main tabs) and optional
    $navMore. Pages may set @section('photo', '<SiteImages key>') for a photo
    header, otherwise index pages get one from $photos below.

    Phones: the first four $nav items live in a bottom bar; "Menu" opens a
    sheet with EVERYTHING (all tabs, the rest, sections, help, account), so
    nothing hides behind a scrolling tab strip.
--}}
@php
    use App\Support\SiteImages as Img;
    $u = auth()->user();
    $current = $module ?? null;
    $modules = collect(config('herd.modules'))->map(fn ($m, $k) => $m + ['key' => $k, 'unlocked' => $u->hasModule($k)]);
    $nav ??= [];
    $navMore ??= [];
    $bar = array_slice($nav, 0, 4);
    $moreOn = collect($navMore)->contains('on', true);

    $photos = [
        'rfid.animals.index' => 'flock-golden', 'rfid.weighings.index' => 'flock-bakkie', 'rfid.compare' => 'dorper-ram',
        'rfid.draft' => 'flock-bakkie', 'rfid.alerts' => 'nguni-herd', 'rfid.events.index' => 'ewe-lamb',
        'rfid.catalogues.index' => 'dorper-ram', 'rfid.readers.index' => 'tagged-ewe', 'rfid.data' => 'karoo-farmhouse',
        'rfid.settings.edit' => 'karoo-farmhouse', 'rfid.live' => 'tagged-lamb', 'help.index' => 'koppie-dawn',
        'herd.suggest' => 'golden-valley', 'watch.dashboard' => 'windpomp-storm', 'watch.animals' => 'nguni-herd',
        'watch.points' => 'windmill-red', 'custom.index' => 'karoo-mist',
    ];
    $photo = trim($__env->yieldContent('photo')) ?: ($photos[request()->route()?->getName()] ?? null);
@endphp
<!DOCTYPE html>
<html lang="en-ZA">
<head>
    @include('partials.head')
    <title>@yield('title', 'Herd Manager') — {{ $current ? config("herd.modules.$current.name") : 'Herd Manager' }} · Farmtech</title>
</head>
<body class="bg-sand text-char font-ui antialiased min-h-screen bg-[radial-gradient(120%_60%_at_50%_0%,#EFE8DA_0%,#F4F1EA_55%)] bg-no-repeat" x-data="{ sheet: false }" :class="sheet && 'overflow-hidden'" @keydown.escape.window="sheet = false">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-full focus:bg-ochre focus:text-char focus:px-5 focus:py-3 focus:text-sm">Skip to content</a>
<header class="sticky top-0 z-40 bg-char text-sand" style="padding-top: env(safe-area-inset-top, 0px)">
    <div class="wrap h-16 flex items-center gap-3">
        <a href="{{ route('herd.hub') }}" class="flex items-baseline gap-1.5 shrink-0" title="All devices">
            <span class="font-headline text-[26px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span>
        </a>

        {{-- Section switcher: links every Herd Manager section together --}}
        <div class="sm:relative min-w-0" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open" data-tour="switcher" class="flex items-center gap-2 rounded-full border border-white/15 px-4 h-9 text-sm hover:border-white/40 transition max-w-full">
                <span class="truncate">{{ $current ? config("herd.modules.$current.name") : 'All devices' }}</span>
                <svg class="w-3 h-3 shrink-0 text-sand/60 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.left
                 class="fixed inset-x-4 top-[calc(4.25rem+env(safe-area-inset-top,0px))] sm:absolute sm:inset-x-auto sm:left-0 sm:top-auto sm:mt-2 sm:w-80 z-50 rounded-2xl bg-white text-char border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
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

        <div class="ml-auto flex items-center gap-1">
            @if ($u->hasModule('rfid'))
                {{-- Quick-find: tag, ID or name from anywhere --}}
                <form method="GET" action="{{ route('rfid.animals.index') }}" role="search" class="hidden md:block">
                    <input type="hidden" name="go" value="1">
                    <label class="relative block">
                        <span class="sr-only">Find an animal</span>
                        @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-sand/40'])
                        <input type="search" name="q" placeholder="Find an animal…" autocomplete="off" class="w-44 focus:w-64 transition-[width] duration-300 h-9 rounded-full bg-white/8 border border-white/10 pl-9 pr-3 text-sm text-sand placeholder:text-sand/40 focus:outline-none focus:border-white/40">
                    </label>
                </form>
            @endif
            <a href="{{ route('help.index') }}" class="hidden lg:inline-flex items-center gap-2 rounded-full px-3 h-9 text-sm text-sand/70 hover:text-sand hover:bg-white/5">@include('partials.icon', ['name' => 'help'])Help</a>
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" class="flex items-center gap-2 rounded-full pl-1 pr-1 md:pr-3 h-10 hover:bg-white/5" aria-label="Account">
                    <span class="w-8 h-8 rounded-full bg-ochre text-char grid place-items-center text-sm font-semibold">{{ mb_strtoupper(mb_substr($u->farm_name ?: $u->name, 0, 1)) }}</span>
                    <span class="hidden md:block text-sm max-w-[14rem] truncate">{{ $u->farm_name ?: $u->name }}</span>
                </button>
                <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-60 z-50 rounded-2xl bg-white text-char border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
                    <div class="px-3 py-2.5 border-b border-hairline mb-1"><div class="text-sm font-medium truncate">{{ $u->name }}</div><div class="text-xs text-stone truncate">{{ $u->email }}</div></div>
                    <a href="{{ route('rfid.settings.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Farm settings</a>
                    <a href="{{ route('account.activate') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Activate a device</a>
                    <a href="{{ route('herd.suggest') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Suggestions</a>
                    @if ($u->canAccessAdminPanel())<a href="{{ url('/admin') }}" class="block rounded-lg px-3 py-2 text-sm text-ochre-dark hover:bg-sand-light">Admin</a>@endif
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-hairline mt-1 pt-1">@csrf<button class="w-full text-left rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Sign out</button></form>
                </div>
            </div>
        </div>
    </div>

    {{-- Desktop / tablet tabs. The "More" dropdown sits OUTSIDE the scrolling strip so it never gets clipped. --}}
    @if ($nav)
        <nav class="hidden lg:block border-t border-white/10">
            <div class="wrap flex items-center gap-7">
                <div class="flex items-center gap-7 overflow-x-auto scrollbar-none min-w-0" data-tour="tabs">
                    @foreach ($nav as $t)
                        @include('partials.tab', ['href' => $t['href'], 'label' => $t['label'], 'icon' => $t['icon'], 'on' => $t['on'], 'badge' => $t['badge'] ?? null])
                    @endforeach
                </div>
                @if ($navMore)
                    <div class="relative ml-auto shrink-0" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" class="relative py-3.5 text-[14px] flex items-center gap-2 {{ $moreOn ? 'text-sand' : 'text-sand/55 hover:text-sand' }}">
                            @include('partials.icon', ['name' => 'grid', 'class' => 'w-4 h-4 '.($moreOn ? 'text-ochre-light' : '')])
                            {{ $moreOn ? collect($navMore)->firstWhere('on', true)['label'] : 'More' }}
                            <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
                            @if ($moreOn)<span class="absolute inset-x-0 -bottom-px h-[2px] bg-ochre rounded-full"></span>@endif
                        </button>
                        <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-1 w-64 z-50 rounded-2xl bg-white text-char border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
                            @foreach ($navMore as $t)
                                <a href="{{ $t['href'] }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-sand-light {{ $t['on'] ? 'bg-sand-light font-medium' : '' }}">@include('partials.icon', ['name' => $t['icon'], 'class' => 'w-4 h-4 text-stone']){{ $t['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </nav>
    @endif
</header>

<main id="main" class="wrap pt-6 sm:pt-10 {{ $nav ? 'pb-28 lg:pb-14' : 'pb-14' }}">
    @if ($photo)
        {{-- Photo header --}}
        <div class="relative overflow-hidden rounded-[28px] bg-char text-white mb-8 min-h-[190px] sm:min-h-[240px] flex items-end">
            <img src="{{ Img::url($photo, true) }}" srcset="{{ Img::srcset($photo) }}" sizes="(min-width: 1320px) 1320px, 100vw" alt="{{ Img::alt($photo) }}" class="absolute inset-0 w-full h-full object-cover img-grade kenburns">
            <div class="absolute inset-0 bg-gradient-to-t from-char/85 via-char/35 to-char/5"></div>
            <div class="relative w-full p-6 sm:p-9 flex flex-wrap items-end justify-between gap-5">
                <div class="min-w-0">
                    @hasSection('eyebrow')<p class="eyebrow !text-white/70 mb-3">@yield('eyebrow')</p>@endif
                    <h1 class="h-display text-[clamp(2.4rem,5vw,4rem)]">@yield('title', 'Herd Manager')</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2 on-photo">@yield('actions')</div>
            </div>
        </div>
    @else
        <div class="flex flex-wrap items-end justify-between gap-6 mb-8">
            <div class="min-w-0">
                @hasSection('eyebrow')<p class="eyebrow mb-3">@yield('eyebrow')</p>@endif
                <h1 class="h-display text-[clamp(2.4rem,4.6vw,3.6rem)]">@yield('title', 'Herd Manager')</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
        </div>
    @endif
    @yield('subnav')
    @include('partials.toast')
    <div class="pop-in">@yield('content')</div>

    <footer class="mt-16 pt-6 border-t border-hairline flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-stone">
        <div class="flex flex-wrap gap-x-4 gap-y-1">
            <a href="{{ route('help.index') }}" class="hover:text-char">Help &amp; guides</a>
            <a href="{{ route('site.contact') }}" class="hover:text-char">Contact</a>
            <a href="{{ route('legal.show', 'privacy') }}" class="hover:text-char">Privacy</a>
            <a href="{{ route('legal.show', 'terms') }}" class="hover:text-char">Terms</a>
            <a href="{{ route('legal.show', 'data') }}" class="hover:text-char">Your farm data</a>
        </div>
        @if ($photo)<x-photo-credits :keys="[$photo]" class="!text-stone-light" />@else @yield('credits') @endif
    </footer>
</main>

{{-- Phone bottom bar: the four main places + Menu (everything else). --}}
@if ($nav)
    <nav class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-char/95 backdrop-blur-xl text-sand border-t border-white/10" style="padding-bottom: env(safe-area-inset-bottom, 0px)" data-tour="tabs-mobile">
        <div class="grid grid-cols-5 h-16">
            @foreach ($bar as $t)
                <a href="{{ $t['href'] }}" class="relative flex flex-col items-center justify-center gap-1 text-[11px] {{ $t['on'] ? 'text-sand' : 'text-sand/55' }}">
                    @include('partials.icon', ['name' => $t['icon'], 'class' => 'w-[22px] h-[22px] '.($t['on'] ? 'text-ochre-light' : '')])
                    <span class="truncate max-w-full px-1">{{ $t['label'] }}</span>
                    @if (! empty($t['badge']))<span class="absolute top-2 left-1/2 ml-2 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-ochre text-char text-[10px] font-semibold grid place-items-center">{{ $t['badge'] }}</span>@endif
                </a>
            @endforeach
            <button type="button" @click="sheet = true" data-tour="help-mobile" class="flex flex-col items-center justify-center gap-1 text-[11px] {{ collect(array_slice($nav, 4))->merge($navMore)->contains('on', true) ? 'text-sand' : 'text-sand/55' }}">
                @include('partials.icon', ['name' => 'grid', 'class' => 'w-[22px] h-[22px]'])
                <span>Menu</span>
            </button>
        </div>
    </nav>
@endif

{{-- Phone menu sheet: every page, one tap. --}}
<div x-show="sheet" x-cloak class="lg:hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="absolute inset-0 bg-char/50" x-show="sheet" x-transition.opacity @click="sheet = false"></div>
    <div class="absolute inset-x-0 bottom-0 max-h-[88vh] overflow-y-auto rounded-t-[28px] bg-sand p-5 pb-8"
         style="padding-bottom: max(2rem, env(safe-area-inset-bottom, 0px))"
         x-show="sheet" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
        <div class="mx-auto w-10 h-1 rounded-full bg-hairline mb-5"></div>
        <div class="flex items-center justify-between mb-4">
            <div class="font-headline text-3xl">{{ $current ? config("herd.modules.$current.name") : 'Herd Manager' }}</div>
            <button type="button" @click="sheet = false" class="w-10 h-10 rounded-full bg-white border border-hairline grid place-items-center text-xl" aria-label="Close">×</button>
        </div>
        @if ($u->hasModule('rfid'))
            <form method="GET" action="{{ route('rfid.animals.index') }}" role="search" class="mb-4">
                <input type="hidden" name="go" value="1">
                <label class="relative block"><span class="sr-only">Find an animal</span>
                    @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-stone'])
                    <input type="search" name="q" placeholder="Find an animal — tag, ID or name" class="field !rounded-full pl-11">
                </label>
            </form>
        @endif
        <div class="grid grid-cols-3 gap-2">
            @foreach (array_merge($nav, $navMore) as $t)
                <a href="{{ $t['href'] }}" class="relative rounded-2xl p-3 h-24 flex flex-col justify-between border {{ $t['on'] ? 'bg-char text-sand border-char' : 'bg-white border-hairline' }}">
                    @include('partials.icon', ['name' => $t['icon'], 'class' => 'w-5 h-5 '.($t['on'] ? 'text-ochre-light' : 'text-ochre')])
                    <span class="text-[13px] leading-tight">{{ $t['label'] }}</span>
                    @if (! empty($t['badge']))<span class="absolute top-2.5 right-2.5 min-w-[1.25rem] h-5 px-1.5 rounded-full bg-ochre text-char text-[11px] font-semibold grid place-items-center">{{ $t['badge'] }}</span>@endif
                </a>
            @endforeach
        </div>

        <p class="eyebrow mt-7 mb-2">Other sections</p>
        <div class="rounded-2xl bg-white border border-hairline divide-y divide-hairline">
            @foreach ($modules as $m)
                @continue($current === $m['key'])
                <a href="{{ $m['unlocked'] ? route($m['route']) : route('account.activate') }}" class="flex items-center gap-3 px-4 py-3.5">
                    <span class="w-2 h-2 rounded-full {{ $m['unlocked'] ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span>
                    <span class="flex-1 text-sm">{{ $m['name'] }}</span><span class="text-stone">→</span>
                </a>
            @endforeach
            <a href="{{ route('herd.hub') }}" class="flex items-center gap-3 px-4 py-3.5"><span class="w-2 h-2"></span><span class="flex-1 text-sm">All devices</span><span class="text-stone">→</span></a>
        </div>

        <p class="eyebrow mt-7 mb-2">Help</p>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <a href="{{ route('help.index') }}" class="rounded-2xl bg-white border border-hairline p-4 flex items-center gap-2">@include('partials.icon', ['name' => 'book'])Guides</a>
            @hasSection('tour')<button type="button" @click="sheet = false; window.dispatchEvent(new CustomEvent('start-tour'))" class="rounded-2xl bg-white border border-hairline p-4 flex items-center gap-2 text-left">@include('partials.icon', ['name' => 'start'])Show me around</button>@endif
            <a href="{{ route('help.show', 'excel') }}" class="rounded-2xl bg-white border border-hairline p-4 flex items-center gap-2">@include('partials.icon', ['name' => 'table'])Excel upload</a>
            <a href="{{ route('help.show', 'esp32') }}" class="rounded-2xl bg-white border border-hairline p-4 flex items-center gap-2">@include('partials.icon', ['name' => 'chip'])Connect the scale</a>
            <a href="{{ route('herd.suggest') }}" class="rounded-2xl bg-white border border-hairline p-4 flex items-center gap-2">@include('partials.icon', ['name' => 'bulb'])Suggest</a>
            <a href="{{ route('site.contact') }}" class="rounded-2xl bg-white border border-hairline p-4 flex items-center gap-2">@include('partials.icon', ['name' => 'help'])Talk to a person</a>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">@csrf<button class="text-sm text-stone underline">Sign out</button></form>
    </div>
</div>

{{-- Help is always one tap away (desktop — phones have it in the Menu sheet) --}}
<div class="{{ $nav ? 'hidden lg:block' : '' }} fixed z-40 right-5 bottom-5" style="margin-bottom: env(safe-area-inset-bottom, 0px)" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
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
