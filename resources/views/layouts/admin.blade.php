<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>@yield('title', trim($__env->yieldContent('heading', 'Admin'))) — Farmtech Admin</title>
</head>
@php
    $u = auth()->user();
    $openLeads = \App\Models\Lead::whereNull('handled_at')->count();
    $item = fn ($route, $label, $pattern = null, $can = null, $badge = null) => ['route' => $route, 'label' => $label, 'pattern' => $pattern ?? $route, 'can' => $can, 'badge' => $badge];
    $newSuggestions = \App\Models\Suggestion::where('status', 'new')->count();
    $groups = [
        'Overview' => [$item('admin.insights', 'Insights', null, 'view-financials')],
        'Customers' => [
            $item('admin.leads.index', 'Leads & orders', 'admin.leads.*', null, $openLeads ?: null),
            $item('admin.licenses.index', 'Activation codes & customers', 'admin.licenses.*', 'manage-users'),
            $item('admin.suggestions.index', 'Suggestions', 'admin.suggestions.*', null, $newSuggestions ?: null),
        ],
        'Store' => [
            $item('admin.listings.index', 'Store listings', 'admin.listings.*', 'manage-catalog'),
            $item('admin.orders.index', 'Orders', 'admin.orders.*'),
        ],
        'System' => [$item('admin.users.index', 'Staff', 'admin.users.*', 'manage-users'), $item('admin.settings.edit', 'Settings', 'admin.settings.*', 'manage-settings'), $item('admin.profile.edit', 'My profile', 'admin.profile.*')],
    ];
    $isOn = fn ($i) => collect(explode('|', $i['pattern']))->contains(fn ($p) => request()->routeIs($p));
    $groups = collect($groups)->map(fn ($items) => collect($items)->filter(fn ($i) => ! $i['can'] || $u->can($i['can']))->values())->filter->isNotEmpty();
    $activeGroup = $groups->search(fn ($items) => $items->contains($isOn)) ?: $groups->keys()->first();
@endphp
<body class="admin-v2 bg-sand text-char font-ui antialiased min-h-screen">
<header class="sticky top-0 z-40 bg-char text-sand" style="padding-top: env(safe-area-inset-top, 0px)">
    <div class="wrap h-16 flex items-center gap-4">
        <a href="{{ route('admin.insights') }}" class="flex items-baseline gap-1.5 shrink-0">
            <span class="font-headline text-[26px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span>
            <span class="ml-2 text-[11px] uppercase tracking-[0.2em] text-sand/50">Admin</span>
        </a>
        <div class="ml-auto flex items-center gap-1 sm:gap-3 text-sm">
            <a href="{{ route('portal') }}" target="_blank" class="hidden md:inline px-3 h-9 leading-9 rounded-full text-sand/70 hover:text-sand">Website ↗</a>
            <a href="{{ route('herd.hub') }}" class="hidden md:inline px-3 h-9 leading-9 rounded-full text-sand/70 hover:text-sand">Herd Manager ↗</a>
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-full pl-1 pr-3 h-10 hover:bg-white/5">
                    <span class="w-8 h-8 rounded-full bg-ochre text-char grid place-items-center text-sm font-semibold">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                    <span class="hidden sm:block max-w-[10rem] truncate">{{ $u->name }}</span>
                </button>
                <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-56 rounded-2xl bg-white text-char border border-hairline shadow-[0_24px_60px_-20px_rgba(0,0,0,.35)] p-2">
                    <a href="{{ route('admin.profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-sand-light">My profile</a>
                    <a href="{{ route('portal') }}" target="_blank" class="block md:hidden rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Website ↗</a>
                    <a href="{{ route('herd.hub') }}" class="block md:hidden rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Herd Manager ↗</a>
                    <form action="{{ route('admin.logout') }}" method="POST" class="border-t border-hairline mt-1 pt-1">@csrf<button class="w-full text-left rounded-lg px-3 py-2 text-sm hover:bg-sand-light">Sign out</button></form>
                </div>
            </div>
        </div>
    </div>
    <nav class="border-t border-white/10">
        <div class="wrap flex gap-7 overflow-x-auto scrollbar-none">
            @foreach ($groups as $name => $items)
                <a href="{{ route($items->first()['route']) }}" class="relative shrink-0 py-3.5 text-[14px] flex items-center gap-1.5 {{ $activeGroup === $name ? 'text-sand' : 'text-sand/55 hover:text-sand' }}">
                    {{ $name }}
                    @if ($name === 'Customers' && ($openLeads + $newSuggestions))<span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-ochre text-char text-[11px] font-semibold grid place-items-center">{{ $openLeads + $newSuggestions }}</span>@endif
                    @if ($activeGroup === $name)<span class="absolute inset-x-0 -bottom-px h-[2px] bg-ochre rounded-full"></span>@endif
                </a>
            @endforeach
        </div>
    </nav>
</header>

<main class="wrap py-10" style="padding-bottom: max(3.5rem, env(safe-area-inset-bottom, 0px))">
    @if ($groups->get($activeGroup)?->count() > 1)
        <div class="flex flex-wrap gap-1 mb-8">
            @foreach ($groups[$activeGroup] as $i)
                <a href="{{ route($i['route']) }}" class="rounded-full px-4 h-9 inline-flex items-center gap-1.5 text-sm border {{ $isOn($i) ? 'bg-char text-sand border-char' : 'bg-white border-hairline text-stone hover:text-char hover:border-char' }}">
                    {{ $i['label'] }}@if ($i['badge'])<span class="rounded-full bg-ochre text-char px-1.5 text-[10px] font-semibold">{{ $i['badge'] }}</span>@endif
                </a>
            @endforeach
        </div>
    @endif

    <h1 class="h-display text-[clamp(2.4rem,4.5vw,3.6rem)] mb-8">@yield('heading', 'Admin')</h1>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-hairline bg-white px-4 py-3 text-sm flex gap-3"><span class="text-ochre">●</span>{{ session('status') }}</div>
    @endif
    @if (session('resetLink'))
        <div class="mb-6 rounded-xl border border-ochre/30 bg-ochre/5 px-4 py-3 text-sm">
            <div class="text-stone">Send this link to the customer (WhatsApp / SMS):</div>
            <code class="mt-1 block break-all font-num select-all">{{ session('resetLink') }}</code>
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="mb-6 rounded-xl border border-[#B0452F]/25 bg-[#B0452F]/5 px-4 py-3 text-sm text-[#B0452F]">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>
