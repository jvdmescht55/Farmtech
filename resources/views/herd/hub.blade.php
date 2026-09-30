@php use App\Support\SiteImages as Img; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>My kraal — Kuddebestuur · Farmtech</title>
</head>
<body class="bg-sand text-char font-ui antialiased min-h-screen">
<section class="relative overflow-hidden bg-char text-sand">
    <img src="{{ Img::url('tafelberg') }}" srcset="{{ Img::srcset('tafelberg') }}" sizes="100vw" alt="{{ Img::alt('tafelberg') }}" class="absolute inset-0 h-full w-full object-cover opacity-60 img-grade">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/50 to-char/30"></div>
    <div class="relative wrap" style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="h-20 flex items-center justify-between">
            <a href="{{ route('portal') }}" class="flex items-baseline gap-1.5"><span class="font-headline text-[30px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span></a>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('site.store') }}" class="hidden sm:inline text-sand/70 hover:text-sand">Winkel</a>
                @if ($user->canAccessAdminPanel())<a href="{{ route('admin.orders.index') }}" class="text-ochre-light hover:text-sand">Admin</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-line-light btn-sm">Teken uit</button></form>
            </div>
        </div>
        <div class="pt-20 pb-16 sm:pt-28 sm:pb-24">
            <p class="eyebrow text-sand/60">My kraal</p>
            <h1 class="h-display mt-5 text-[clamp(3rem,8vw,7rem)]">Howzit, <em class="text-ochre-light">{{ \Illuminate\Support\Str::before($user->name, ' ') }}.</em></h1>
            <div class="mt-12 flex flex-wrap gap-x-14 gap-y-6">
                <div><div class="font-headline text-5xl num">{{ number_format($stats['animals']) }}</div><div class="text-sm text-sand/60 mt-1">aktiewe diere</div></div>
                <div><div class="font-headline text-5xl num">{{ $stats['readers'] }}</div><div class="text-sm text-sand/60 mt-1">lesers gekoppel</div></div>
                <div><div class="font-headline text-5xl">{{ $stats['lastSync'] ? \Carbon\Carbon::parse($stats['lastSync'])->diffForHumans(short: true) : '—' }}</div><div class="text-sm text-sand/60 mt-1">laaste sinch</div></div>
            </div>
        </div>
    </div>
</section>

<main class="wrap py-16 sm:py-20" style="padding-bottom: max(5rem, env(safe-area-inset-bottom, 0px))">
    @include('partials.flash')
    <div class="flex flex-wrap items-end justify-between gap-6 mb-10">
        <div>
            <p class="eyebrow">Kuddebestuur</p>
            <h2 class="h-display mt-3 text-5xl">Jou sagteware</h2>
        </div>
        <a href="{{ route('account.activate') }}" class="btn-line btn-sm">+ Aktiveer 'n toestel</a>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        @foreach ($modules as $m)
            <a href="{{ $m['unlocked'] ? route($m['route']) : route('account.activate') }}" class="group panel overflow-hidden flex flex-col hover:border-char transition-colors">
                <div class="relative aspect-[16/9] overflow-hidden bg-char">
                    <img src="{{ Img::url($m['image'], true) }}" srcset="{{ Img::srcset($m['image']) }}" sizes="(min-width: 1024px) 50vw, 100vw" alt="{{ Img::alt($m['image']) }}" class="absolute inset-0 h-full w-full object-cover img-grade transition-transform duration-[1.2s] group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-char/80 to-transparent"></div>
                    <span class="absolute top-5 left-5 chip {{ $m['unlocked'] ? 'bg-sand text-char' : 'bg-char/70 text-sand' }}">{{ $m['unlocked'] ? '● Aktief' : 'Gesluit' }}</span>
                    <h3 class="absolute bottom-5 left-6 right-6 h-display text-5xl text-sand">{{ $m['name'] }}</h3>
                </div>
                <div class="p-7 flex-1 flex flex-col">
                    <p class="text-stone leading-relaxed">{{ $m['tagline'] }}</p>
                    <ul class="mt-5 grid sm:grid-cols-2 gap-2.5 text-sm">
                        @foreach ($m['features'] as $f)<li class="flex gap-2.5"><span class="text-ochre">—</span>{{ $f }}</li>@endforeach
                    </ul>
                    <div class="mt-8 pt-6 border-t border-hairline flex items-center justify-between">
                        <span class="font-medium">{{ $m['unlocked'] ? 'Maak oop' : 'Tik jou aktiveringskode in' }}</span>
                        <span class="grid place-items-center w-11 h-11 rounded-full bg-char text-sand transition-transform duration-500 group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>
        @endforeach

        <div class="rounded-2xl border border-dashed border-hairline p-10 flex flex-col justify-center">
            <p class="eyebrow">Binnekort</p>
            <div class="h-display text-4xl mt-3">Meer toestelle op pad.</div>
            <p class="mt-3 text-stone max-w-sm">Nuwe Farmtech-toestelle kom hier met hul eie sagteware — alles op dieselfde kuddeboek.</p>
        </div>
    </div>
    <x-photo-credits :keys="['tafelberg', 'merino-rams']" class="mt-12" />
</main>
</body>
</html>
