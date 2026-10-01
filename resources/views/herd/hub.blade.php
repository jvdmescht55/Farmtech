@php use App\Support\SiteImages as Img; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>Herd Manager — Farmtech</title>
</head>
<body class="bg-sand text-char font-ui antialiased min-h-screen">
<section class="relative overflow-hidden bg-char text-sand">
    <img src="{{ Img::url('tafelberg') }}" srcset="{{ Img::srcset('tafelberg') }}" sizes="100vw" alt="{{ Img::alt('tafelberg') }}" class="absolute inset-0 h-full w-full object-cover opacity-55 img-grade">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/50 to-char/30"></div>
    <div class="relative wrap" style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="h-20 flex items-center justify-between">
            <a href="{{ route('portal') }}" class="flex items-baseline gap-1.5"><span class="font-headline text-[30px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span></a>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('site.store') }}" class="hidden sm:inline text-sand/70 hover:text-sand">Store</a>
                <a href="{{ route('help.index') }}" class="hidden sm:inline text-sand/70 hover:text-sand">Help &amp; guides</a>
                <a href="{{ route('rfid.settings.edit') }}" class="hidden sm:inline text-sand/70 hover:text-sand">Settings</a>
                @if ($user->canAccessAdminPanel())<a href="{{ url('/admin') }}" class="text-ochre-light hover:text-sand">Admin</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-line-light btn-sm">Sign out</button></form>
            </div>
        </div>
        <div class="pt-16 pb-14 sm:pt-24 sm:pb-20">
            <p class="eyebrow text-sand/60">Herd Manager · {{ $user->farm_name ?: 'your farm' }}</p>
            <h1 class="h-display mt-5 text-[clamp(3rem,8vw,7rem)]">Howzit, <em class="text-ochre-light">{{ \Illuminate\Support\Str::before($user->name, ' ') }}.</em></h1>
            <div class="mt-10 flex flex-wrap gap-x-14 gap-y-6">
                <div><div class="font-headline text-5xl num">{{ number_format($stats['animals']) }}</div><div class="text-sm text-sand/60 mt-1">head in the herd book</div></div>
                <div><div class="font-headline text-5xl num">{{ $stats['devices'] }}</div><div class="text-sm text-sand/60 mt-1">devices connected</div></div>
                <div><div class="font-headline text-5xl num {{ $stats['alerts'] ? 'text-ochre-light' : '' }}">{{ $stats['alerts'] }}</div><div class="text-sm text-sand/60 mt-1">things need you</div></div>
            </div>
        </div>
    </div>
</section>

<main class="wrap py-14 sm:py-20" style="padding-bottom: max(5rem, env(safe-area-inset-bottom, 0px))">
    @include('partials.toast')
    <div class="flex flex-wrap items-end justify-between gap-6 mb-10">
        <div>
            <p class="eyebrow">Pick a device</p>
            <h2 class="h-display mt-3 text-5xl">Where to, boet?</h2>
        </div>
        <a href="{{ route('account.activate') }}" class="btn-line btn-sm">+ Activate a device</a>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach ($modules as $m)
            <a href="{{ $m['unlocked'] ? route($m['route']) : route('account.activate') }}" class="group panel overflow-hidden flex flex-col hover:border-char transition-colors {{ $m['unlocked'] ? '' : 'opacity-90' }}">
                <div class="relative aspect-[16/10] overflow-hidden bg-char">
                    <img src="{{ Img::url($m['image'], true) }}" alt="{{ Img::alt($m['image']) }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover img-grade transition-transform duration-[1.2s] group-hover:scale-105 {{ $m['unlocked'] ? '' : 'grayscale-[50%]' }}">
                    <div class="absolute inset-0 bg-gradient-to-t from-char/85 to-transparent"></div>
                    <span class="absolute top-5 left-5 chip {{ $m['unlocked'] ? 'bg-sand text-char' : 'bg-char/70 text-sand' }}">{{ $m['unlocked'] ? '● Ready' : 'Needs its activation code' }}</span>
                    <div class="absolute bottom-5 left-6 right-6 text-sand">
                        <div class="text-xs uppercase tracking-[0.18em] text-sand/60">{{ $m['kind'] }}</div>
                        <h3 class="h-display text-4xl mt-1">{{ $m['name'] }}</h3>
                    </div>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <p class="text-stone leading-relaxed">{{ $m['tagline'] }}</p>
                    <div class="mt-auto pt-6 flex items-center justify-between">
                        <span class="font-medium">{{ $m['unlocked'] ? 'Open' : 'Unlock' }}</span>
                        <span class="grid place-items-center w-11 h-11 rounded-full bg-char text-sand transition-transform duration-500 group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid lg:grid-cols-2 gap-6">
        <a href="{{ route('herd.suggest') }}" class="group rounded-[24px] bg-char text-sand p-8 sm:p-10 flex flex-col justify-between min-h-[15rem] hover:bg-char-soft transition-colors">
            <p class="eyebrow text-sand/50">Made for your farm</p>
            <div>
                <div class="h-display text-4xl sm:text-5xl">Farm a bit differently? <em class="text-ochre-light">Tell us.</em></div>
                <p class="mt-3 text-sand/70 max-w-lg">Suggest a device, a feature, or ask us to build something just for your setup. Herd Manager grows around the farmers using it.</p>
            </div>
            <span class="mt-6 inline-flex items-center gap-3 font-medium">Make a suggestion <span class="transition-transform group-hover:translate-x-1">→</span></span>
        </a>
        <div class="panel p-8 sm:p-10 flex flex-col justify-between">
            <p class="eyebrow">One herd book</p>
            <div>
                <div class="h-display text-4xl">Every device, same animals.</div>
                <p class="mt-3 text-stone max-w-lg">Weights from the KraalTrac Pro, water visits from the Watch, readings from your own gadgets — it all lands on the same animal pages and the same alerts. Switch sections any time from the menu at the top.</p>
            </div>
            <a href="{{ route('rfid.animals.index') }}" class="mt-6 link-u self-start font-medium">Open the herd book →</a>
        </div>
    </div>
    <x-photo-credits :keys="['tafelberg', 'merino-rams', 'windpomp-storm', 'karoo-mist']" class="mt-12" />
</main>
</body>
</html>
