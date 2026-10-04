@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Farmtech Store — KraalTrac devices & Herd Manager')
@section('description', 'KraalTrac Pro handheld EID reader & weigh logger, KraalTrac Watch water-point counter, and Herd Manager herd software — built around South African farms.')
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
            <p class="max-w-md text-lg text-white/80 leading-relaxed">Scan in the kraal and your herd book updates itself — weights, growth, pedigree, water visits and auction books. No notebook in the bakkie, no expensive imports.</p>
            <div class="flex flex-wrap gap-3">
                <a href="#devices" class="btn-light">See the devices</a>
                <a href="#order" class="btn-line-light">Get yours</a>
            </div>
        </div>
    </div>
</section>

{{-- Proof strip --}}
<section class="border-b border-hairline bg-sand">
    <div class="wrap py-8 grid grid-cols-2 md:grid-cols-4 gap-6">
        @foreach ([['134.2 kHz', 'Reads the standard ISO ear tags'], ['300', 'Records kept offline on the scale'], ['6 digits', 'To pair a device — no IT oom needed'], ['R0', 'Monthly fee to get going']] as [$v, $l])
            <div><div class="font-headline text-4xl sm:text-5xl">{{ $v }}</div><div class="text-sm text-stone mt-1">{{ $l }}</div></div>
        @endforeach
    </div>
</section>

{{-- Devices --}}
<section id="devices" class="wrap py-24 sm:py-32 scroll-mt-20">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
        <div>
            <p class="eyebrow">Devices</p>
            <h2 class="h-display mt-5 text-[clamp(2.6rem,6vw,5rem)]">Built in SA, <em>for SA kraals.</em></h2>
        </div>
        <p class="max-w-sm text-stone">Each device comes with its own section in Herd Manager. No monthly subscription to get going.</p>
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
            <p class="eyebrow text-sand/50">Herd Manager — included</p>
            <h2 class="h-display mt-6 text-[clamp(2.6rem,5.5vw,4.8rem)]">Lekker data.<br><em class="text-ochre-light">Not just numbers.</em></h2>
            <p class="mt-8 text-sand/70 text-lg leading-relaxed max-w-md">See how the herd is growing, which ram's lambs do best, who's ready for the market and who skipped the water — on your phone, in the kraal.</p>
            <ul class="mt-8 space-y-3 text-sand/80">
                @foreach (['Weigh days with daily gain per animal', 'Compare rams, groups and seasons', 'Sort the mob by weight in seconds', 'Alerts for weight loss, missed drinks, births and more', 'Auction books with lot numbers, ready to print'] as $f)
                    <li class="flex gap-3"><span class="text-ochre-light">—</span>{{ $f }}</li>
                @endforeach
            </ul>
        </div>
        <div class="lg:col-span-7" x-data="{ tab: 'weigh' }">
            <div class="flex flex-wrap gap-2 mb-5">
                @foreach (['weigh' => 'Weigh day', 'animal' => 'One animal', 'book' => 'Auction book', 'alerts' => 'Alerts'] as $k => $l)
                    <button type="button" @click="tab = '{{ $k }}'" class="rounded-full px-4 h-9 text-sm border transition" :class="tab === '{{ $k }}' ? 'bg-sand text-char border-sand' : 'border-sand/25 text-sand/70 hover:text-sand hover:border-sand/60'">{{ $l }}</button>
                @endforeach
            </div>
            <div class="rounded-[24px] bg-sand text-char p-2 shadow-[0_40px_120px_-30px_rgba(0,0,0,.6)] lg:rotate-[-1.2deg]">
                <div class="rounded-[18px] bg-white border border-hairline overflow-hidden min-h-[330px]">
                    <div class="flex items-center gap-2 px-5 h-11 border-b border-hairline text-[12px] text-stone">
                        <span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span>
                        <span class="ml-3" x-text="{ weigh: 'Weigh day · 28 Sep', animal: 'DVS 25 5004 · Ewe', book: 'Auction book · Ram & ewe sale 2026', alerts: 'Needs your attention' }[tab]"></span>
                    </div>

                    {{-- Weigh day --}}
                    <div x-show="tab === 'weigh'" x-transition.opacity>
                        <div class="grid grid-cols-3 divide-x divide-hairline border-b border-hairline">
                            @foreach ([['Average', '42.8', 'kg'], ['Daily gain', '+214', 'g'], ['Weighed', '186', 'head']] as [$l, $v, $u])
                                <div class="p-4 sm:p-5"><div class="kpi-label !text-[10px]">{{ $l }}</div><div class="mt-2 font-headline text-3xl sm:text-4xl">{{ $v }}<span class="text-sm sm:text-base text-stone ml-1 font-ui">{{ $u }}</span></div></div>
                            @endforeach
                        </div>
                        <div class="p-5 grid sm:grid-cols-2 gap-6">
                            <div>
                                <div class="kpi-label !text-[10px] mb-3">Average weight · 6 weigh days</div>
                                <svg viewBox="0 0 300 110" class="w-full h-28" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="g1" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#B8732E" stop-opacity=".25"/><stop offset="1" stop-color="#B8732E" stop-opacity="0"/></linearGradient></defs><path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22 L300,110 L0,110Z" fill="url(#g1)"/><path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22" fill="none" stroke="#B8732E" stroke-width="2.5"/></svg>
                            </div>
                            <div>
                                <div class="kpi-label !text-[10px] mb-3">Sort by weight</div>
                                @foreach ([['Market', '≥ 45 kg', 58, 'bg-[#3F7A3A]'], ['Feed on', '38–45 kg', 92, 'bg-ochre'], ['Watch', '< 38 kg', 36, 'bg-[#B0452F]']] as [$g, $r, $n, $c])
                                    <div class="flex items-center gap-3 text-[13px] py-1.5"><span class="w-2 h-2 rounded-full {{ $c }}"></span><span class="w-16">{{ $g }}</span><span class="text-stone flex-1">{{ $r }}</span><span class="font-medium">{{ $n }}</span></div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- One animal --}}
                    <div x-show="tab === 'animal'" x-cloak x-transition.opacity class="p-5 grid sm:grid-cols-2 gap-6">
                        <div>
                            <div class="font-headline text-4xl">DVS 25 5004</div>
                            <div class="text-sm text-stone mt-1">Ewe · born Sep 2025 · <span class="chip bg-char text-sand">SP</span></div>
                            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                @foreach ([['Weight', '70.1 kg'], ['Daily gain', '+196 g'], ['Sire', 'DVS 22 2435'], ['Dam', 'DVS 21 3107'], ['Lambs', '3 (1 set of twins)'], ['Last drink', '2 h ago']] as [$k, $v])
                                    <div><dt class="text-[11px] uppercase tracking-[0.12em] text-stone">{{ $k }}</dt><dd class="mt-0.5">{{ $v }}</dd></div>
                                @endforeach
                            </dl>
                        </div>
                        <div>
                            <div class="kpi-label !text-[10px] mb-3">Growth curve</div>
                            <svg viewBox="0 0 300 140" class="w-full h-36" preserveAspectRatio="none" aria-hidden="true"><path d="M0,130 C60,110 90,80 150,62 S250,30 300,24" fill="none" stroke="#15140F" stroke-width="2.5"/><path d="M0,130 C60,118 100,96 150,82 S250,58 300,52" fill="none" stroke="#B8732E" stroke-dasharray="5 5" stroke-width="2"/></svg>
                            <div class="flex gap-4 text-[12px] text-stone"><span>— this ewe</span><span class="text-ochre">- - flock average</span></div>
                        </div>
                    </div>

                    {{-- Auction book --}}
                    <div x-show="tab === 'book'" x-cloak x-transition.opacity class="p-5">
                        <table class="w-full text-[13px]">
                            <thead><tr class="text-left text-[10px] uppercase tracking-[0.12em] text-stone"><th class="pb-2">Lot</th><th class="pb-2">Animal</th><th class="pb-2">Tier</th><th class="pb-2">Sire × Dam</th><th class="pb-2 text-right">Weight</th></tr></thead>
                            <tbody class="divide-y divide-hairline">
                                @foreach ([['66A', 'DVS 25 5001', 'SP', '222435 × 213107', '84.1'], ['66B', 'DVS 25 5004', 'SP', '222435 × 220120', '70.1'], ['66C', 'DVS 25 5023', 'C', '230017 × 219988', '70.2'], ['67A', 'DVS 25 5042', 'B', '230017 × 221402', '54.2']] as [$lot, $id, $t, $p, $w])
                                    <tr><td class="py-2.5 font-headline text-xl">{{ $lot }}</td><td class="font-mono">{{ $id }}</td><td><span class="chip {{ $t === 'SP' ? 'bg-char text-sand' : 'bg-sand-deep' }}">{{ $t }}</span></td><td class="font-mono text-[11px] text-stone">{{ $p }}</td><td class="text-right">{{ $w }} kg</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4 text-[12px] text-stone">Lot numbers in one click · prints in the Logix layout</div>
                    </div>

                    {{-- Alerts --}}
                    <div x-show="tab === 'alerts'" x-cloak x-transition.opacity class="p-5 space-y-3 text-[13px]">
                        @foreach ([['#B0452F', 'Sharp weight loss', 'DVS 25 5010 lost 6.9 kg (9.4%) in two weeks — check her first.'], ['#B8732E', 'Hasn\'t been to drink', 'Ewe 3107 last seen at the north trough 27 h ago.'], ['#B8732E', 'Lambing this week', '3 ewes scanned with twins are due — move them to the lambing camp.'], ['#3F7A3A', 'Withdrawal done', 'Group B is clear of Closantel from Friday — safe to sell.']] as [$c, $t, $d])
                            <div class="flex gap-3 rounded-xl border border-hairline p-3.5"><span class="mt-1.5 w-2 h-2 shrink-0 rounded-full" style="background: {{ $c }}"></span><div><div class="font-medium">{{ $t }}</div><div class="text-stone mt-0.5">{{ $d }}</div></div></div>
                        @endforeach
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
            <p class="mt-6 text-lg text-white/75 max-w-md">Every farm runs differently. Tell us how you work and we'll shape Herd Manager around it — or build you a device that doesn't exist yet. Boer maak 'n plan; we help.</p>
            <a href="{{ route('site.suggest') }}" class="btn-light mt-10">Tell us what you need</a>
        </div>
    </div>
</section>

{{-- Notebook vs KraalTrac --}}
<section class="wrap py-24 sm:py-32">
    <div class="max-w-2xl">
        <p class="eyebrow">Why bother?</p>
        <h2 class="h-display mt-5 text-[clamp(2.6rem,5.5vw,4.6rem)]">The notebook in the bakkie <em>had a good run.</em></h2>
    </div>
    <div class="mt-12 grid md:grid-cols-2 gap-5">
        <div class="rounded-[28px] border border-hairline p-8 sm:p-10">
            <div class="eyebrow">The old way</div>
            <ul class="mt-6 space-y-4 text-stone">
                @foreach (['Shout the tag number, someone writes it down', 'Type it all into Excel that night (if you\'re lucky)', 'Work out daily gain with a calculator', 'Find out a ewe was sick when she\'s already down', 'Build the auction book by hand the week before the sale'] as $t)
                    <li class="flex gap-3"><span class="text-stone-light">×</span>{{ $t }}</li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-[28px] bg-char text-sand p-8 sm:p-10 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-ochre/20 blur-3xl"></div>
            <div class="eyebrow text-ochre-light">With KraalTrac</div>
            <ul class="mt-6 space-y-4 relative">
                @foreach (['Scan the ear tag — the animal pops up', 'Punch in the weight — it\'s saved, even with no signal', 'Gain, ranking and sorting are already done', 'Alerts when something looks wrong — before it\'s too late', 'Auction book with lot numbers in one click'] as $t)
                    <li class="flex gap-3"><span class="text-ochre-light">✓</span>{{ $t }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- What's possible: custom builds --}}
<section id="possible" class="bg-sand-deep/50 border-y border-hairline scroll-mt-20">
    <div class="wrap py-24 sm:py-32">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <p class="eyebrow">What's possible</p>
                <h2 class="h-display mt-5 text-[clamp(2.6rem,5.5vw,4.6rem)]">If it can measure it, <em>we can wire it in.</em></h2>
                <p class="mt-6 text-stone text-lg max-w-xl">A few ideas farmers have asked about. Each one plugs into Herd Manager with its own readings, graphs and alarms — on your phone, next to your herd.</p>
            </div>
            <a href="{{ route('site.suggest', ['kind' => 'custom_build']) }}" class="btn-dark">Tell us your idea</a>
        </div>
        @php
            $ideas = [
                ['drop', 'Trough & tank level', 'Know the dam\'s running low before the sheep do.', 'Level · %', '68%'],
                ['scale', 'Walk-over weigher', 'Weighs every animal on its way to water. No kraal day needed.', 'Avg weight', '43.1 kg'],
                ['live', 'Electric fence alarm', 'A text the moment the voltage drops — jackal or broken wire.', 'Fence', '7.8 kV'],
                ['bell', 'Lambing camp watch', 'Motion at night in the lambing camp, so you check the right pen.', 'Tonight', '3 alerts'],
                ['gear', 'Solar pump monitor', 'Is the borehole pump running? Flow and battery, every hour.', 'Flow', '1.4 m³/h'],
                ['pin', 'Bell-ewe GPS', 'Where the flock is grazing today, on a map of your camps.', 'Camp', 'Rivierkamp'],
                ['chip', 'Cold room & milk tank', 'Temperature alarm for the cold room, dairy tank or vaccine fridge.', 'Temp', '3.4 °C'],
                ['note', 'Rain gauge', 'Rain per camp, logged for you — no more checking the meter.', 'This week', '24 mm'],
            ];
        @endphp
        <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($ideas as [$icon, $t, $d, $mLabel, $mValue])
                <div class="group rounded-[24px] bg-white border border-hairline p-6 flex flex-col hover:-translate-y-1 hover:shadow-[0_30px_60px_-30px_rgba(0,0,0,.25)] transition duration-500">
                    <span class="w-11 h-11 rounded-2xl bg-sand-light text-ochre grid place-items-center group-hover:bg-char group-hover:text-ochre-light transition">@include('partials.icon', ['name' => $icon, 'class' => 'w-5 h-5'])</span>
                    <h3 class="mt-6 text-lg font-medium">{{ $t }}</h3>
                    <p class="mt-2 text-sm text-stone leading-relaxed flex-1">{{ $d }}</p>
                    <div class="mt-6 pt-4 border-t border-hairline flex items-baseline justify-between text-sm">
                        <span class="text-stone">{{ $mLabel }}</span><span class="font-headline text-2xl">{{ $mValue }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-6 text-[13px] text-stone">Ideas and sample readings for illustration — every build is quoted for your farm. Already built your own gadget? It can send readings to Herd Manager today.</p>
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
                ['How does the data get to the website?', 'Three ways, all safe: on Wi-Fi the scale sends every record by itself within a minute. No Wi-Fi? Plug it into a laptop, click "Sync the scale" in Herd Manager and it uploads and clears itself. Or copy and paste. The scale only forgets a record once the website has confirmed it\'s saved.'],
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

{{-- Peace of mind --}}
<section class="wrap py-20 sm:py-24">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-px bg-hairline rounded-[28px] overflow-hidden border border-hairline">
        @foreach ([
            ['check', '6-month warranty', 'If it breaks from normal kraal use, we fix or replace it.', route('legal.show', 'returns')],
            ['start', '7-day cooling-off', 'Changed your mind? Send it back within 7 days of delivery.', route('legal.show', 'returns')],
            ['help', 'Real people, local support', 'Talk to someone who knows a kraal — not a call centre.', route('site.contact')],
            ['download', 'Your data stays yours', 'Export everything any time. POPIA-compliant, never sold.', route('legal.show', 'data')],
        ] as [$icon, $t, $d, $href])
            <a href="{{ $href }}" class="bg-sand p-7 sm:p-8 hover:bg-white transition group">
                <span class="w-10 h-10 rounded-full border border-hairline grid place-items-center text-ochre group-hover:bg-char group-hover:text-ochre-light group-hover:border-char transition">@include('partials.icon', ['name' => $icon, 'class' => 'w-4 h-4'])</span>
                <div class="mt-5 font-medium">{{ $t }}</div>
                <p class="mt-1.5 text-sm text-stone leading-relaxed">{{ $d }}</p>
            </a>
        @endforeach
    </div>
</section>

@include('site._order', ['heading' => 'Get yours', 'listing' => $listings->first()])
@endsection

@section('credits')<x-photo-credits :keys="['windpomp-pink', 'merino-rams', 'windpomp-storm', 'karoo-mist', 'dirt-road']" dark />@endsection
