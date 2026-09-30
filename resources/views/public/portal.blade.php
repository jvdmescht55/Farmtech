@php use App\Support\SiteImages; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>Farmtech — Livestock RFID Store &amp; Herd Management</title>
    <meta name="description" content="Farmtech: buy livestock RFID readers, tags and farm equipment, or sign in to Herd Management to run your herd from your reader's scans.">
    <link rel="preload" as="image" href="{{ SiteImages::url('cattle-tag') }}">
</head>
<body class="bg-brand-975 text-white antialiased font-body">
<div class="min-h-screen flex flex-col">
    <header class="absolute inset-x-0 top-0 z-20" style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 h-20 flex items-center justify-between">
            <a href="{{ route('portal') }}" class="font-display text-2xl font-semibold tracking-tight drop-shadow">Farm<span class="text-mint">tech</span></a>
            <div class="flex items-center gap-2 text-sm">
                @auth
                    <a href="{{ auth()->user()->canAccessAdminPanel() ? route('admin.orders.index') : route('herd.hub') }}" class="rounded-full bg-white/10 backdrop-blur px-4 py-2 font-semibold hover:bg-white/20 transition">My account</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full bg-white/10 backdrop-blur px-4 py-2 font-semibold hover:bg-white/20 transition">Sign in</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1 grid lg:grid-cols-2">
        @foreach ([
            [
                'href' => route('home'), 'img' => 'cattle-tag', 'eyebrow' => 'Farmtech Store',
                'title' => 'Livestock RFID & farm equipment',
                'body' => 'ISO 11784/11785 stick readers, EID ear tags and the field equipment around them — priced all-in, delivered to your gate.',
                'cta' => 'Shop the store',
                'meta' => $rfidCount ? $rfidCount.' RFID products · '.$productCount.' items in total' : $productCount.' items in stock',
            ],
            [
                'href' => route('login'), 'img' => 'kraal', 'eyebrow' => 'Herd Management',
                'title' => 'Run your herd from the kraal',
                'body' => 'Bought a Farmtech reader? Your software is included — herd register, weights, pedigree grading and sale catalogues, synced from every scan.',
                'cta' => auth()->check() ? 'Open Herd Management' : 'Sign in or activate',
                'meta' => 'Now available: RFID Scanner V1',
            ],
        ] as $i => $p)
            <a href="{{ $p['href'] }}" class="group relative min-h-[70vh] lg:min-h-screen overflow-hidden flex items-end">
                <img src="{{ SiteImages::url($p['img']) }}" srcset="{{ SiteImages::url($p['img'], true) }} 800w, {{ SiteImages::url($p['img']) }} 1920w" sizes="(min-width: 1024px) 50vw, 100vw"
                     alt="{{ SiteImages::alt($p['img']) }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-[1.6s] ease-out group-hover:scale-105" @if($i) loading="lazy" @endif>
                <div class="absolute inset-0 bg-gradient-to-t from-brand-975 via-brand-975/55 to-brand-975/10"></div>
                <div class="absolute inset-0 bg-brand-975/0 group-hover:bg-brand-975/10 transition"></div>
                <div class="relative w-full px-6 sm:px-12 pb-14 sm:pb-20 pt-32 max-w-2xl">
                    <p class="text-mint text-xs font-semibold uppercase tracking-[0.25em] mb-4">{{ $p['eyebrow'] }}</p>
                    <h2 class="font-display text-4xl sm:text-5xl font-semibold leading-[1.05] tracking-tight [text-shadow:0_2px_24px_rgba(0,0,0,.35)]">{{ $p['title'] }}</h2>
                    <p class="mt-5 text-white/80 text-base sm:text-lg leading-relaxed max-w-lg">{{ $p['body'] }}</p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <span class="inline-flex items-center gap-2 rounded-full bg-mint group-hover:bg-mint-dark px-7 py-3.5 text-sm font-semibold transition-all group-hover:gap-3">
                            {{ $p['cta'] }}
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </span>
                        <span class="text-sm text-white/60">{{ $p['meta'] }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </main>

    <footer class="border-t border-white/10 px-5 sm:px-8 py-5" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom, 0px))">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3 text-xs text-white/50">
            <span>© {{ date('Y') }} Farmtech · South Africa</span>
            <x-photo-credits :keys="['cattle-tag', 'kraal']" dark />
        </div>
    </footer>
</div>
</body>
</html>
