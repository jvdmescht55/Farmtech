@php use App\Support\SiteImages as Img; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $title }} · Farmtech</title>
    <meta name="robots" content="noindex">
</head>
<body class="bg-char text-sand font-ui antialiased">
<div class="relative min-h-[100svh] overflow-hidden flex">
    <img src="{{ Img::url($photo ?? 'koppie-dawn', true) }}" alt="" class="absolute inset-0 w-full h-full object-cover img-grade opacity-60 kenburns">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/60 to-char/30"></div>
    <div class="relative wrap flex flex-col justify-between py-10" style="padding-top: max(2.5rem, env(safe-area-inset-top)); padding-bottom: max(2.5rem, env(safe-area-inset-bottom))">
        <a href="{{ url('/') }}" class="flex items-baseline gap-1.5 self-start"><span class="font-headline text-[30px] leading-none">farmtech</span><span class="w-1.5 h-1.5 rounded-full bg-ochre"></span></a>
        <div class="max-w-2xl">
            <p class="eyebrow !text-sand/60">{{ $code }}</p>
            <h1 class="h-display mt-5 text-[clamp(3.2rem,9vw,7rem)]">{!! $headline !!}</h1>
            <p class="mt-6 text-lg text-sand/75 max-w-lg leading-relaxed">{{ $body }}</p>
            <div class="mt-10 flex flex-wrap gap-3">
                @foreach ($links as [$href, $label, $primary])
                    <a href="{{ $href }}" class="{{ $primary ? 'btn-light' : 'btn-line-light' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <x-photo-credits :keys="[$photo ?? 'koppie-dawn']" dark class="!text-sand/35" />
    </div>
</div>
</body>
</html>
