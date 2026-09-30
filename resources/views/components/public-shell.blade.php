@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $title ?? 'Farmtech RFID — Herd Manager' }}</title>
    <meta name="description" content="Farmtech RFID: ISO 11784/11785 livestock readers that sync straight into your herd register, pedigree tiers and sale catalogues.">
</head>
<body class="bg-canvas text-charcoal antialiased font-body min-h-screen">
<div class="min-h-screen grid lg:grid-cols-[1.1fr_1fr]">
    <section class="relative overflow-hidden bg-brand-975 text-white px-6 sm:px-12 py-10 lg:py-14 flex flex-col">
        <img src="{{ \App\Support\SiteImages::url('dorper-ram') }}" alt="{{ \App\Support\SiteImages::alt('dorper-ram') }}" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-975/95 via-brand-975/85 to-brand-975/60"></div>
        <div class="relative flex items-center justify-between">
            <a href="{{ route('portal') }}" class="font-display text-2xl font-semibold tracking-tight">Farm<span class="text-mint">tech</span> <span class="text-white/50 font-body text-sm font-medium tracking-[0.2em] uppercase ml-1">Herd Management</span></a>
            <a href="{{ route('home') }}" class="text-sm text-white/70 hover:text-white">Store →</a>
        </div>

        <div class="relative my-auto py-12 max-w-xl">
            <p class="text-mint text-xs font-semibold uppercase tracking-[0.25em] mb-5">ISO 11784 / 11785 · HDX &amp; FDX-B</p>
            <h1 class="font-display text-4xl sm:text-5xl font-semibold leading-[1.05] tracking-tight">Scan in the kraal.<br>Your stud book updates itself.</h1>
            <p class="mt-6 text-white/70 text-lg leading-relaxed">Every Farmtech RFID Scanner V1 comes with its Herd Management software. Tags, weights and dates sync straight into your herd register — pedigrees, SP/C grading and sale catalogues are built from the same records.</p>

            <dl class="mt-10 grid sm:grid-cols-2 gap-x-8 gap-y-6 text-sm">
                @foreach ([
                    ['Reader sync', 'Push scans from the reader over the API, or drop in the session file it exports.'],
                    ['Herd register', 'EID, visual ID, birth data, weights and daily gain per animal.'],
                    ['Pedigree tiers', 'SP, C and B status worked out from three generations of pedigree.'],
                    ['Sale catalogues', 'Lot numbers (66A, 66B …) and a print-ready veiling catalogue layout.'],
                ] as [$t, $d])
                    <div class="border-l-2 border-mint/60 pl-4">
                        <dt class="font-semibold text-white">{{ $t }}</dt>
                        <dd class="text-white/60 mt-1 leading-relaxed">{{ $d }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="relative flex flex-wrap items-end justify-between gap-3">
            <div class="font-mono text-[11px] text-white/40 flex flex-wrap gap-x-6 gap-y-1"><span>982 000123456789</span><span>DVS 25 5082</span><span>42.5 kg</span><span>SP</span></div>
            <x-photo-credits :keys="['dorper-ram']" dark />
        </div>
    </section>

    <section class="flex items-center justify-center px-6 sm:px-12 py-12">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </section>
</div>
</body>
</html>
