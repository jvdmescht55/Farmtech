@props(['title' => null, 'image' => 'koppie-dawn', 'headline' => null])
@php use App\Support\SiteImages as Img; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $title ?? 'Herd Manager — Farmtech' }}</title>
</head>
<body class="bg-sand text-char font-ui antialiased min-h-screen">
<div class="min-h-screen grid lg:grid-cols-[1.15fr_1fr]">
    <section class="relative hidden lg:block overflow-hidden bg-char text-white">
        <img src="{{ Img::url($image) }}" srcset="{{ Img::srcset($image) }}" sizes="55vw" alt="{{ Img::alt($image) }}" class="absolute inset-0 h-full w-full object-cover img-grade">
        <div class="absolute inset-0 bg-gradient-to-t from-char/85 via-char/10 to-char/30"></div>
        <div class="relative h-full flex flex-col justify-between p-12">
            <a href="{{ route('portal') }}" class="flex items-baseline gap-1.5"><span class="font-headline text-[30px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span></a>
            <div>
                <p class="eyebrow text-white/60">Herd Manager · built around your farm</p>
                <h2 class="h-display mt-5 text-[clamp(3rem,5.5vw,5.5rem)]">{!! $headline ?? 'Welcome back<br><em class="text-ochre-light">to the kraal.</em>' !!}</h2>
                <x-photo-credits :keys="[$image]" dark class="mt-10" />
            </div>
        </div>
    </section>

    <section class="flex flex-col px-5 sm:px-12 lg:px-20" style="padding-top: max(1.5rem, env(safe-area-inset-top, 0px)); padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 0px))">
        <div class="h-14 flex items-center justify-between">
            <a href="{{ route('portal') }}" class="lg:invisible flex items-baseline gap-1.5"><span class="font-headline text-[28px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span></a>
            <a href="{{ route('portal') }}" class="text-sm text-stone hover:text-char">← Back to the website</a>
        </div>
        <div class="flex-1 flex items-center py-12">
            <div class="w-full max-w-md mx-auto">{{ $slot }}</div>
        </div>
    </section>
</div>
</body>
</html>
