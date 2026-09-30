@php use App\Support\SiteImages; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>Herd Management — Farmtech</title>
</head>
<body class="bg-canvas text-charcoal antialiased font-body min-h-screen">
<section class="relative overflow-hidden text-white">
    <img src="{{ SiteImages::url('sheep-field') }}" alt="{{ SiteImages::alt('sheep-field') }}" class="absolute inset-0 w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-r from-brand-975 via-brand-975/85 to-brand-975/40"></div>
    <div class="relative max-w-6xl mx-auto px-5 sm:px-8" style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="h-20 flex items-center justify-between">
            <a href="{{ route('portal') }}" class="font-display text-2xl font-semibold tracking-tight">Farm<span class="text-mint">tech</span> <span class="font-body text-xs uppercase tracking-[0.2em] text-white/60 ml-1">Herd Management</span></a>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('home') }}" class="hidden sm:inline text-white/70 hover:text-white">Store</a>
                @if ($user->canAccessAdminPanel())<a href="{{ route('admin.orders.index') }}" class="text-alert-light hover:text-white">Admin</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-full bg-white/10 px-4 py-2 font-semibold hover:bg-white/20">Sign out</button></form>
            </div>
        </div>
        <div class="py-14 sm:py-20 max-w-2xl">
            <p class="text-mint text-xs font-semibold uppercase tracking-[0.25em]">Welcome back</p>
            <h1 class="font-display text-4xl sm:text-5xl font-semibold tracking-tight mt-3">{{ $user->farm_name ?: $user->name }}</h1>
            <div class="mt-8 flex flex-wrap gap-8 text-sm">
                <div><div class="font-display text-3xl font-semibold">{{ number_format($stats['animals']) }}</div><div class="text-white/60">active animals</div></div>
                <div><div class="font-display text-3xl font-semibold">{{ $stats['readers'] }}</div><div class="text-white/60">linked readers</div></div>
                <div><div class="font-display text-3xl font-semibold">{{ $stats['lastSync'] ? \Carbon\Carbon::parse($stats['lastSync'])->diffForHumans(short: true) : '—' }}</div><div class="text-white/60">last sync</div></div>
            </div>
        </div>
    </div>
</section>

<main class="max-w-6xl mx-auto px-5 sm:px-8 py-12">
    @include('partials.flash')
    <div class="flex items-end justify-between gap-4 mb-6">
        <div>
            <h2 class="font-display text-2xl font-semibold">Your software</h2>
            <p class="text-ink-secondary mt-1">Each Farmtech device unlocks its own tools.</p>
        </div>
        <a href="{{ route('account.activate') }}" class="btn-secondary">+ Activate a device</a>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        @foreach ($modules as $m)
            <div class="app-card overflow-hidden flex flex-col group">
                <div class="relative aspect-[16/9] overflow-hidden">
                    <img src="{{ SiteImages::url($m['image']) }}" srcset="{{ SiteImages::url($m['image'], true) }} 800w, {{ SiteImages::url($m['image']) }} 1920w" sizes="(min-width: 768px) 50vw, 100vw" alt="{{ SiteImages::alt($m['image']) }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <span class="absolute top-4 left-4 rounded-full px-3 py-1 text-xs font-semibold {{ $m['unlocked'] ? 'bg-mint text-white' : 'bg-white/90 text-charcoal' }}">{{ $m['unlocked'] ? 'Active' : 'Locked' }}</span>
                    <h3 class="absolute bottom-4 left-5 right-5 font-display text-2xl font-semibold text-white">{{ $m['name'] }}</h3>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <p class="text-ink-secondary">{{ $m['tagline'] }}</p>
                    <ul class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        @foreach ($m['features'] as $f)<li class="flex gap-2"><span class="text-mint">✓</span>{{ $f }}</li>@endforeach
                    </ul>
                    <div class="mt-6 pt-6 border-t border-border mt-auto">
                        @if ($m['unlocked'])
                            <a href="{{ route($m['route']) }}" class="btn-primary w-full py-3">Open {{ $m['name'] }} →</a>
                        @else
                            <a href="{{ route('account.activate') }}" class="btn-primary w-full py-3">Enter activation code</a>
                            <a href="{{ route('category.show', 'rfid') }}" class="block text-center text-sm text-brand-900 font-semibold mt-3 hover:underline">Don't have the device? Shop RFID readers</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="rounded-xl border-2 border-dashed border-border p-8 flex flex-col justify-center text-center text-ink-secondary">
            <div class="font-display text-xl font-semibold text-charcoal">More devices coming</div>
            <p class="mt-2 text-sm max-w-sm mx-auto">New Farmtech devices will appear here with their own software. Everything shares the same herd records.</p>
        </div>
    </div>
    <x-photo-credits :keys="['sheep-field', 'lamb-tag']" class="mt-10" />
</main>
</body>
</html>
