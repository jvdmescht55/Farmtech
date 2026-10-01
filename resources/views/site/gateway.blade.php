@php use App\Support\SiteImages as Img; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>Farmtech — Store or Herd Manager?</title>
    <meta name="description" content="Farmtech: KraalTrac devices and Herd Manager herd software for sheep, goats and cattle — built around your farm.">
    <link rel="preload" as="image" href="{{ Img::url('windpomp-pink', true) }}">
    <link rel="preload" as="image" href="{{ Img::url('windpomp-storm', true) }}">
</head>
<body class="bg-char text-sand font-ui antialiased">
<div class="relative min-h-[100svh] flex flex-col">
    <header class="absolute inset-x-0 top-0 z-20" style="padding-top: env(safe-area-inset-top, 0px)">
        <div class="wrap h-20 flex items-center justify-between">
            <a href="{{ route('portal') }}" class="flex items-baseline gap-1.5"><span class="font-headline text-[32px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span></a>
            <a href="{{ route('site.contact') }}" class="text-sm text-sand/70 hover:text-sand">Contact</a>
        </div>
    </header>

    <main class="flex-1 flex flex-col lg:flex-row">
        @foreach ([
            [route('site.store'), 'windpomp-pink', '01', 'Store', 'KraalTrac devices — and everything they can do for your herd.', 'Go to the store'],
            [auth()->check() ? route('herd.hub') : route('landing'), 'windpomp-storm', '02', 'Herd Manager', 'Your herd online — weights, growth, water, alerts and catalogues. Shaped around your farm.', auth()->check() ? 'Open my herd' : 'Sign in'],
        ] as $i => [$href, $img, $n, $title, $body, $cta])
            <a href="{{ $href }}" class="group relative flex-1 min-h-[50svh] lg:min-h-[100svh] overflow-hidden lg:transition-[flex-grow] lg:duration-700 lg:ease-[cubic-bezier(.2,.7,.2,1)] lg:hover:grow-[1.35] {{ $i ? 'lg:border-l border-white/10' : '' }}">
                <img src="{{ Img::url($img, true) }}" srcset="{{ Img::srcset($img) }}" sizes="(min-width: 1024px) 60vw, 100vw" alt="{{ Img::alt($img) }}"
                     class="absolute inset-0 h-full w-full object-cover img-grade scale-[1.04] transition-transform duration-[1.6s] ease-[cubic-bezier(.2,.7,.2,1)] group-hover:scale-100">
                <div class="absolute inset-0 bg-gradient-to-t from-char/90 via-char/25 to-char/40 transition-colors duration-700 group-hover:from-char/80"></div>
                <div class="relative h-full flex flex-col justify-end px-6 sm:px-12 pb-12 sm:pb-16 pt-28">
                    <span class="font-num text-sm text-sand/50">{{ $n }}</span>
                    <h2 class="h-display mt-3 text-[clamp(3.6rem,9vw,8.5rem)] reveal-up" style="animation-delay: {{ 0.1 + $i * 0.12 }}s">{{ $title }}</h2>
                    <p class="mt-5 max-w-sm text-lg text-sand/75 leading-relaxed">{{ $body }}</p>
                    <span class="mt-8 inline-flex items-center gap-4 text-[15px] font-medium">
                        <span class="grid place-items-center w-12 h-12 rounded-full bg-sand text-char transition-transform duration-500 group-hover:translate-x-1.5">→</span>{{ $cta }}
                    </span>
                </div>
            </a>
        @endforeach
    </main>

    <div class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 hidden lg:flex justify-center">
        <span class="rounded-full bg-sand text-char w-14 h-14 grid place-items-center font-headline text-2xl italic shadow-[0_10px_40px_rgba(0,0,0,.35)]">or</span>
    </div>

    <footer class="absolute inset-x-0 bottom-0 z-10 pointer-events-none" style="padding-bottom: env(safe-area-inset-bottom, 0px)">
        <div class="wrap h-10 flex items-center justify-between text-[11px] text-sand/35">
            <span>Know your herd. Lekker boer.</span>
            <x-photo-credits :keys="['windpomp-pink', 'windpomp-storm']" dark class="pointer-events-auto hidden sm:block !text-sand/35" />
        </div>
    </footer>
</div>
</body>
</html>
