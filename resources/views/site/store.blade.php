@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Farmtech Store — KraalTrac devices & Kuddebestuur')
@section('description', 'KraalTrac Pro handheld EID reader & weigh logger, KraalTrac Watch water-point counter, and Kuddebestuur herd software — built around South African farms.')
@section('hero_dark', '1')
@push('head')<link rel="preload" as="image" href="{{ Img::url('windpomp-pink') }}" imagesrcset="{{ Img::srcset('windpomp-pink') }}" imagesizes="100vw">@endpush

@section('content')
{{-- Hero --}}
<section class="relative h-[100svh] min-h-[640px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('windpomp-pink') }}" srcset="{{ Img::srcset('windpomp-pink') }}" sizes="100vw" alt="{{ Img::alt('windpomp-pink') }}" class="absolute inset-0 h-full w-full object-cover object-[50%_65%] img-grade" fetchpriority="high">
    <div class="absolute inset-0 bg-gradient-to-t from-char/85 via-char/20 to-char/35"></div>
    <div class="relative wrap h-full flex flex-col justify-end pb-16 sm:pb-24">
        <p class="eyebrow text-white/70 reveal-up">The Farmtech store</p>
        <h1 class="h-display mt-6 text-[clamp(3.6rem,11vw,10rem)] reveal-up" style="animation-delay:.08s">Know every animal.<br><em class="text-ochre-light">Every kilo.</em></h1>
        <div class="mt-10 flex flex-col lg:flex-row lg:items-end justify-between gap-10 reveal-up" style="animation-delay:.18s">
            <p class="max-w-md text-lg text-white/80 leading-relaxed">Scan in the kraal and your herd book updates itself — weights, growth, pedigree, water visits and sale catalogues. No notebook in the bakkie, no expensive imports.</p>
            <div class="flex flex-wrap gap-3">
                <a href="#devices" class="btn-light">See the devices</a>
                <a href="#order" class="btn-line-light">Get yours</a>
            </div>
        </div>
    </div>
</section>

{{-- Devices --}}
<section id="devices" class="wrap py-24 sm:py-32 scroll-mt-20">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
        <div>
            <p class="eyebrow">Devices</p>
            <h2 class="h-display mt-5 text-[clamp(2.6rem,6vw,5rem)]">Built in SA, <em>for SA kraals.</em></h2>
        </div>
        <p class="max-w-sm text-stone">Each device comes with its own section in Kuddebestuur. No monthly subscription to get going.</p>
    </div>

    @php
        $family = [
            'kraaltrac-pro' => ['img' => 'merino-rams', 'kind' => 'Handheld EID reader & weigh logger'],
            'kraaltrac-watch' => ['img' => 'windpomp-storm', 'kind' => 'Gate & water-point counter'],
        ];
        $cards = $listings->map(fn ($l) => ['href' => route('site.product', $l), 'name' => $l->name, 'kind' => $l->tagline, 'img' => $family[$l->slug]['img'] ?? 'golden-valley', 'photo' => $l->images[0] ?? null, 'price' => $l->priceLabel(), 'note' => $l->availability]);
        if (! $listings->firstWhere('slug', 'kraaltrac-watch')) {
            $cards->push(['href' => '#order', 'name' => 'KraalTrac Watch', 'kind' => $family['kraaltrac-watch']['kind'], 'img' => 'windpomp-storm', 'photo' => null, 'price' => null, 'note' => 'Coming soon — register interest']);
        }
        if ($cards->isEmpty()) {
            $cards->push(['href' => '#order', 'name' => 'KraalTrac Pro', 'kind' => $family['kraaltrac-pro']['kind'], 'img' => 'merino-rams', 'photo' => null, 'price' => null, 'note' => 'First batch on the way — register interest']);
        }
        $cards->push(['href' => route('site.suggest', ['kind' => 'custom_build']), 'name' => 'Something just for you', 'kind' => 'Custom builds & your own sensors', 'img' => 'karoo-mist', 'photo' => null, 'price' => null, 'note' => 'Tell us what your farm needs']);
    @endphp
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach ($cards as $c)
            <a href="{{ $c['href'] }}" class="group relative block aspect-[4/5] overflow-hidden rounded-[28px] bg-char text-white">
                <img src="{{ $c['photo'] ? asset('storage/'.$c['photo']) : Img::url($c['img'], true) }}" alt="{{ $c['name'] }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover img-grade transition-transform duration-[1.4s] group-hover:scale-[1.06]">
                <div class="absolute inset-0 bg-gradient-to-t from-char/90 via-char/15 to-transparent"></div>
                <div class="absolute top-6 left-6 right-6 flex justify-between eyebrow text-white/80"><span>{{ $c['note'] }}</span></div>
                <div class="absolute bottom-0 inset-x-0 p-7">
                    <div class="text-sm text-white/70">{{ $c['kind'] }}</div>
                    <h3 class="h-display text-5xl mt-1">{{ $c['name'] }}</h3>
                    <div class="mt-6 flex items-center justify-between">
                        <span class="font-headline text-3xl">{{ $c['price'] ?? '' }}</span>
                        <span class="grid place-items-center w-11 h-11 rounded-full bg-white text-char transition-transform duration-500 group-hover:translate-x-1">→</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</section>

{{-- Software --}}
<section id="software" class="bg-bush text-sand overflow-hidden scroll-mt-20">
    <div class="wrap py-24 sm:py-32 grid lg:grid-cols-12 gap-14 items-center">
        <div class="lg:col-span-5">
            <p class="eyebrow text-sand/50">Kuddebestuur — included</p>
            <h2 class="h-display mt-6 text-[clamp(2.6rem,5.5vw,4.8rem)]">Lekker data.<br><em class="text-ochre-light">Not just numbers.</em></h2>
            <p class="mt-8 text-sand/70 text-lg leading-relaxed max-w-md">See how the herd is growing, which ram's lambs do best, who's ready for the market and who skipped the water — on your phone, in the kraal.</p>
            <ul class="mt-8 space-y-3 text-sand/80">
                @foreach (['Weigh days with daily gain per animal', 'Compare rams, groups and seasons', 'Sort the mob by weight in seconds', 'Alerts for weight loss, missed drinks, births and more', 'Sale catalogues with lot numbers, ready to print'] as $f)
                    <li class="flex gap-3"><span class="text-ochre-light">—</span>{{ $f }}</li>
                @endforeach
            </ul>
        </div>
        <div class="lg:col-span-7">
            <div class="rounded-[24px] bg-sand text-char p-2 shadow-[0_40px_120px_-30px_rgba(0,0,0,.6)] rotate-[-1.2deg]">
                <div class="rounded-[18px] bg-white border border-hairline overflow-hidden">
                    <div class="flex items-center gap-2 px-5 h-11 border-b border-hairline text-[12px] text-stone">
                        <span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span>
                        <span class="ml-3">Weigh day · 28 Sep</span>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-hairline border-b border-hairline">
                        @foreach ([['Average', '42.8', 'kg'], ['Daily gain', '+214', 'g'], ['Weighed', '186', 'head']] as [$l, $v, $u])
                            <div class="p-5"><div class="kpi-label !text-[10px]">{{ $l }}</div><div class="mt-2 font-headline text-4xl">{{ $v }}<span class="text-base text-stone ml-1 font-ui">{{ $u }}</span></div></div>
                        @endforeach
                    </div>
                    <div class="p-5 grid sm:grid-cols-2 gap-6">
                        <div>
                            <div class="kpi-label !text-[10px] mb-3">Average weight · 6 weigh days</div>
                            <svg viewBox="0 0 300 110" class="w-full h-28" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="g1" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#B8732E" stop-opacity=".25"/><stop offset="1" stop-color="#B8732E" stop-opacity="0"/></linearGradient></defs><path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22 L300,110 L0,110Z" fill="url(#g1)"/><path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22" fill="none" stroke="#B8732E" stroke-width="2.5"/></svg>
                        </div>
                        <div>
                            <div class="kpi-label !text-[10px] mb-3">Needs attention</div>
                            <ul class="space-y-2 text-[13px]">
                                <li class="flex gap-2"><span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-[#B0452F]"></span>DVS 25 5010 — sharp weight loss</li>
                                <li class="flex gap-2"><span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-ochre"></span>Ewe 3107 hasn't been to drink</li>
                                <li class="flex gap-2"><span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-ochre"></span>3 ewes due to lamb this week</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-6 text-[13px] text-sand/40">Illustration with sample data.</p>
        </div>
    </div>
</section>

{{-- Made for you --}}
<section class="relative h-[80svh] min-h-[520px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('merino-rams', true) }}" srcset="{{ Img::srcset('merino-rams') }}" sizes="100vw" alt="{{ Img::alt('merino-rams') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char/80 via-char/30 to-transparent"></div>
    <div class="relative wrap h-full flex items-center">
        <div class="max-w-xl">
            <p class="eyebrow text-white/60">Made for your farm</p>
            <h2 class="h-display mt-5 text-[clamp(3rem,7vw,6.5rem)]">Your farm isn't<br><em class="text-ochre-light">a template.</em></h2>
            <p class="mt-6 text-lg text-white/75 max-w-md">Every farm runs differently. Tell us how you work and we'll shape Kuddebestuur around it — or build you a device that doesn't exist yet. Boer maak 'n plan; we help.</p>
            <a href="{{ route('site.suggest') }}" class="btn-light mt-10">Tell us what you need</a>
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="wrap py-24 sm:py-32">
    <div class="grid lg:grid-cols-12 gap-14">
        <div class="lg:col-span-4">
            <p class="eyebrow">How it works</p>
            <h2 class="h-display mt-6 text-[clamp(2.6rem,5vw,4.4rem)]">Three steps. <em>Sommer easy.</em></h2>
        </div>
        <ol class="lg:col-span-8 divide-y divide-hairline border-y border-hairline">
            @foreach ([
                ['Get your device', 'Order a KraalTrac. In the box is an activation card just for you.'],
                ['Pair it in 6 digits', 'Create your account with the card, then type the code on the device\'s screen. Done.'],
                ['Scan. Weigh. Lekker.', 'Every read lands on farmtech.site — even if you\'re out of signal, it syncs later. You see the sums; you make the calls.'],
            ] as $i => [$t, $d])
                <li class="grid sm:grid-cols-[5rem_1fr] gap-4 py-10">
                    <span class="font-headline text-5xl text-ochre">{{ $i + 1 }}</span>
                    <div><h3 class="text-2xl font-medium">{{ $t }}</h3><p class="mt-2 text-stone leading-relaxed max-w-lg">{{ $d }}</p></div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="bg-sand-deep/60 border-y border-hairline scroll-mt-20">
    <div class="wrap py-24 sm:py-32 grid lg:grid-cols-12 gap-14">
        <div class="lg:col-span-4">
            <p class="eyebrow">Questions</p>
            <h2 class="h-display mt-6 text-[clamp(2.4rem,4.5vw,4rem)]">Ja-nee, <em>we know.</em></h2>
        </div>
        <div class="lg:col-span-8 divide-y divide-hairline border-y border-hairline" x-data="{ open: 0 }">
            @foreach ([
                ['Will it read my existing tags?', 'If they\'re standard animal EID tags (134.2 kHz FDX-B, ISO 11784/11785) — the usual ones in South Africa — yes.'],
                ['What if there\'s no signal in the kraal?', 'The KraalTrac saves every read on the device first and syncs when it\'s back in Wi-Fi range. Nothing gets lost.'],
                ['Do I need a computer or a local server?', 'No. Everything runs on farmtech.site — open it on your phone, tablet or laptop.'],
                ['Is there a monthly fee?', 'No subscription to get going — the software that comes with your device is included.'],
                ['Can I bring my Logix / stud book records?', 'Yes. Upload a CSV with IDs, parents and EBVs — we build the pedigree and work out SP / C / B tiers.'],
                ['Who owns my data?', 'You do. Export everything any time. We never sell it. See our privacy policy.'],
            ] as $i => [$q, $a])
                <div>
                    <button type="button" @click="open = open === {{ $i }} ? -1 : {{ $i }}" class="w-full flex items-center justify-between gap-6 py-7 text-left">
                        <span class="text-xl">{{ $q }}</span>
                        <span class="shrink-0 w-9 h-9 rounded-full border border-hairline grid place-items-center transition-transform duration-300" :class="open === {{ $i }} && 'rotate-45 bg-char text-sand border-char'">+</span>
                    </button>
                    <div x-show="open === {{ $i }}" x-transition.opacity.duration.300ms x-cloak><p class="pb-8 -mt-2 max-w-2xl text-stone leading-relaxed">{{ $a }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@include('site._order', ['heading' => 'Get yours', 'listing' => $listings->first()])
@endsection

@section('credits')<x-photo-credits :keys="['windpomp-pink', 'merino-rams', 'windpomp-storm', 'karoo-mist', 'dirt-road']" dark />@endsection
