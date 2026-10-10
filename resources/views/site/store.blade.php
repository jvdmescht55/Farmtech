@extends('layouts.site')
@php
    use App\Support\SiteImages as Img;
    use App\Services\Billing;
    $pro = $pro ?? null;
    $watch = $listings->firstWhere('slug', 'kraaltrac-watch');
    $proCents = $pro?->price_cents ?: 799900;
    $price = 'R'.number_format($proCents / 100, 0, '.', ' ');
    $monthly = Billing::rand(Billing::price('monthly'));
    $yearly = Billing::rand(Billing::price('yearly'));
    $freeMonths = (int) round(config('billing.trial_days') / 30);
    $faqs = [
    ['Will it read my existing tags?', 'If they are standard animal EID tags (134.2 kHz FDX-B, ISO 11784/11785), yes. Those are the usual ones in South Africa.'],
    ['Does it work for cattle and goats?', 'Yes. Sheep, goats and cattle live in the same herd book, each with their own birth-weight limits, calving or lambing dates and alerts. We set the scanner\'s screens to say cattle, goat or sheep for your farm.'],
    ['What if there\'s no signal in the kraal?', 'The KraalTrac keeps about 2 000 weighings and your animal list on the device and sends them once it finds Wi-Fi or your phone\'s hotspot. With no Wi-Fi at all, plug it into a laptop and click Sync the scale.'],
    ['Does it connect to my scale?', 'For now you type the weight from your scale\'s display. It takes about two seconds an animal. A direct scale cable is on our list.'],
    ['What does Herd Manager cost?', 'The first '.$freeMonths.' months are free from the day you activate your scanner. Then '.$monthly.' a month or '.$yearly.' a year for the whole farm, every device included. No contract and no debit order. If you stop paying, nothing is deleted: it turns read-only and you can still download everything.'],
    ['Can I bring my Logix, stud book or Excel records?', 'Yes. Drop in the Excel or CSV file as it is. No template: we read the columns (English or Afrikaans), split animals, weights and treatments, and keep any extra columns in the notes.'],
    ['How does buying work?', 'Add it to your cart and check out. Pay by EFT (or card, where offered) and we courier it or you collect. If something is sold out, reserve it: nothing is paid until your batch is ready.'],
    ['Who owns my data?', 'You do. Export everything any time. We never sell it.'],
    ];
    $faqLd = json_encode(['@'.'context' => 'https://schema.org', '@'.'type' => 'FAQPage', 'mainEntity' => collect($faqs)->map(fn ($f) => ['@'.'type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@'.'type' => 'Answer', 'text' => $f[1]]])->all()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $orgLd = json_encode(['@'.'context' => 'https://schema.org', '@'.'type' => 'Organization', 'name' => 'Farmtech', 'url' => url('/'), 'logo' => asset('icons/icon-512.png'), 'description' => 'KraalTrac EID scanners and Herd Manager software for sheep, goats and cattle, built in South Africa.'], JSON_UNESCAPED_SLASHES);
@endphp
@section('title', 'KraalTrac Pro & Herd Manager · Farmtech')
@section('description', 'Spend less time in the kraal, lose fewer animals and sell at the right weight. KraalTrac Pro EID scanner, '.$price.' once-off, and Herd Manager for sheep, goats and cattle, built in South Africa.')
@section('hero_dark', '1')
@push('head')
<script type="application/ld+json">{!! $orgLd !!}</script>
<link rel="preload" as="image" href="{{ Img::url('windpomp-pink', true) }}" imagesrcset="{{ Img::srcset('windpomp-pink') }}" imagesizes="100vw">
@endpush

@section('content')
{{-- Hero: one promise, two buttons --}}
<section class="relative h-[100svh] min-h-[620px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('windpomp-pink') }}" srcset="{{ Img::srcset('windpomp-pink') }}" sizes="100vw" alt="{{ Img::alt('windpomp-pink') }}" class="absolute inset-0 h-full w-full object-cover object-[50%_65%] img-grade kenburns" fetchpriority="high">
    <div class="absolute inset-0 bg-gradient-to-t from-char/90 via-char/25 to-char/45"></div>
    <div class="relative wrap h-full flex flex-col justify-end pb-14 sm:pb-20">
        <h1 class="h-display text-[clamp(4.2rem,15vw,13rem)] leading-[0.86] reveal-up">Ken jou<br>kudde.</h1>
        <div class="mt-10 grid lg:grid-cols-[1fr_auto] gap-8 items-end reveal-up" style="animation-delay:.15s">
            <p class="max-w-xl text-xl sm:text-2xl text-white/90 leading-snug">Less time in the kraal. Fewer animals lost. Better prices at the sale.</p>
            <div class="flex flex-wrap gap-3">
                <a href="#see" class="btn-light">Watch it work</a>
                <a href="#shop" class="btn-line-light">Shop</a>
            </div>
        </div>
        <ul class="mt-10 pt-6 border-t border-white/15 flex flex-wrap gap-x-8 gap-y-2 text-sm text-white/65">
            <li>Sheep, goats and cattle</li>
            <li>Works with no signal</li>
            <li>Built in South Africa</li>
        </ul>
    </div>
</section>

<section class="wrap -mt-10 relative z-10"><x-market-strip class="shadow-[0_30px_70px_-40px_rgba(0,0,0,.4)]" /></section>

{{-- The problems we solve: one line each, the "how" on tap --}}
<section id="problems" class="wrap pt-20 sm:pt-28 pb-6 scroll-mt-20" x-data="{ open: null }">
    <h2 class="h-display text-[clamp(2.8rem,6vw,5rem)] max-w-3xl">Saves you time, money and effort.</h2>
    <p class="mt-4 text-stone text-lg">Tap one to see how.</p>
    <div class="mt-10 grid md:grid-cols-2 gap-4">
        @foreach ([
            ['time', 'Weigh day takes all day', 'Done before tea', 'Wand to the ear, type the weight, press #. About eight seconds an animal. No clipboard, no evening typing it into Excel: the gains, averages and who\'s behind are on your phone the moment you finish.', 'weighday'],
            ['loss', 'You find the sick one too late', 'Fewer animals lost', 'An animal that loses weight between weigh days, or stops coming to water, gets flagged on your phone straight away. Sick animals stop drinking and lose condition first, so you catch them days earlier.', 'alerts'],
            ['money', 'Selling at the wrong weight', 'More rand per animal', 'Sort the herd by weight in one tap and see exactly who is market-ready. Compare with this week\'s meat prices before you load the truck.', 'sort'],
            ['effort', 'Paperwork for the sale and the vet', 'Paperwork done for you', 'Auction books with lot numbers in the Logix layout, withdrawal dates after every treatment, birth weights and pedigrees. Print it or send it, nothing to copy out by hand.', 'auction'],
        ] as $i => [$key, $problem, $win, $how, $clip])
            <div class="rounded-[24px] border transition" :class="open === {{ $i }} ? 'bg-white border-char' : 'bg-white/60 border-hairline hover:border-char'">
                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}" class="w-full text-left p-6 sm:p-7 flex items-start gap-5">
                    <span class="flex-1">
                        <span class="block text-stone line-through decoration-ochre/60">{{ $problem }}</span>
                        <span class="block font-headline text-3xl sm:text-4xl mt-1">{{ $win }}</span>
                    </span>
                    <span class="shrink-0 w-9 h-9 mt-1 rounded-full border border-hairline grid place-items-center transition-transform" :class="open === {{ $i }} && 'rotate-45 bg-char text-sand border-char'">+</span>
                </button>
                <div x-show="open === {{ $i }}" x-collapse x-cloak class="px-6 sm:px-7 pb-7 -mt-2">
                    <p class="text-stone leading-relaxed">{{ $how }}</p>
                    <a href="#see" @click="$dispatch('play-clip', '{{ $clip }}')" class="mt-4 inline-flex items-center gap-2 text-sm font-medium underline">Watch it on screen</a>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- See it work: real screen recordings from our test farm --}}
@php
    $clips = [
        'weighday' => ['Weigh day', 'Every animal\'s weight, gain per day and who\'s falling behind.'],
        'sort' => ['Sort by weight', 'Heaviest first, in one tap. Pick who goes to the sale.'],
        'alerts' => ['Alerts', 'Low birth weights, sharp weight loss, withdrawal dates.'],
        'auction' => ['Auction book', 'Lot numbers and the Logix layout, ready to print.'],
        'import' => ['Bring your records', 'Drop in any Excel sheet. It sorts itself.'],
    ];
@endphp
<section id="see" class="scroll-mt-20 py-20 sm:py-28" x-data="{ clip: 'weighday', play(k) { this.clip = k; } }" x-on:play-clip.window="play($event.detail)">
    <span id="software" class="block -translate-y-20"></span><span id="weighday" class="block -translate-y-20"></span>
    <div class="wrap">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <h2 class="h-display text-[clamp(2.8rem,6vw,5rem)]">Watch it work.</h2>
            <p class="text-stone max-w-sm">Real screens from our test farm in Herd Manager. Every scan from the KraalTrac lands here.</p>
        </div>
        <div class="mt-10 grid lg:grid-cols-[1fr_17rem] gap-6 items-start">
            <div>
                <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1" role="tablist">
                    @foreach ($clips as $k => [$t])
                        <button type="button" role="tab" @click="play('{{ $k }}')" :aria-selected="clip === '{{ $k }}'" class="shrink-0 rounded-full px-4 h-10 text-sm border transition" :class="clip === '{{ $k }}' ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char'">{{ $t }}</button>
                    @endforeach
                </div>
                <div class="mt-4 rounded-[24px] overflow-hidden bg-char border border-hairline shadow-[0_40px_90px_-40px_rgba(0,0,0,.45)]">
                    <div class="flex items-center gap-1.5 px-4 h-8 bg-[#2a2520]"><span class="w-2.5 h-2.5 rounded-full bg-white/20"></span><span class="w-2.5 h-2.5 rounded-full bg-white/20"></span><span class="w-2.5 h-2.5 rounded-full bg-white/20"></span><span class="ml-3 text-[11px] text-white/40">farmtech.site/app</span></div>
                    <video x-ref="player" :poster="'/videos/' + clip + '.jpg'" autoplay muted loop playsinline preload="metadata" x-effect="clip; $nextTick(() => { $el.load(); $el.play().catch(() => {}); })" class="block w-full aspect-[1120/700] object-cover object-top bg-sand" aria-label="Screen recording of Herd Manager">
                        <source :src="'/videos/' + clip + '.webm'" type="video/webm">
                        <source :src="'/videos/' + clip + '.mp4'" type="video/mp4">
                    </video>
                </div>
                @foreach ($clips as $k => [$t, $c])
                    <p x-show="clip === '{{ $k }}'" @if (! $loop->first) x-cloak @endif class="mt-4 text-lg">{{ $c }}</p>
                @endforeach
            </div>
            <figure class="hidden lg:block">
                <div class="rounded-[38px] bg-char p-2.5 shadow-[0_40px_90px_-40px_rgba(0,0,0,.5)]">
                    <video poster="/videos/phone.jpg" autoplay muted loop playsinline preload="metadata" class="block w-full rounded-[30px] aspect-[390/780] object-cover object-top bg-sand" aria-label="Screen recording of an animal's page on a phone"><source src="/videos/phone.webm" type="video/webm"><source src="/videos/phone.mp4" type="video/mp4"></video>
                </div>
                <figcaption class="mt-3 text-sm text-stone text-center">Each animal on your phone: weights, parents, treatments.</figcaption>
            </figure>
        </div>
    </div>
</section>

{{-- The shop --}}
<section id="shop" class="wrap pt-6 pb-20 scroll-mt-20">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <h2 class="h-display text-[clamp(2.8rem,6vw,5rem)]">Shop</h2>
        <p class="text-stone max-w-sm">Sold out? Reserve from the next batch and pay only when it's ready.</p>
    </div>
    <div class="mt-10 grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach ($listings as $l)
            @include('shop._card', ['l' => $l])
        @endforeach
        <article class="group relative rounded-[28px] overflow-hidden bg-char text-sand md:col-span-2 xl:col-span-3 min-h-[240px] flex">
            <img src="{{ Img::url('karoo-mist', true) }}" alt="{{ Img::alt('karoo-mist') }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover opacity-60 img-grade transition duration-[1.2s] group-hover:scale-[1.03]">
            <div class="absolute inset-0 bg-gradient-to-r from-char via-char/70 to-transparent"></div>
            <div class="relative self-center p-7 sm:p-10 w-full flex flex-wrap items-end justify-between gap-6">
                <div>
                    <div class="text-sm text-sand/70">Custom builds</div>
                    <h3 class="mt-1 font-headline text-3xl sm:text-4xl">Something only your farm needs?</h3>
                    <p class="mt-2 text-sand/70">Trough levels, fence alarms, pump monitors.</p>
                </div>
                <a href="{{ route('site.custom') }}" class="btn-light">See what we build</a>
            </div>
        </article>
    </div>
</section>

{{-- KraalTrac Pro, short --}}
<section id="pro" class="scroll-mt-20 bg-white border-y border-hairline">
    <div class="wrap py-20 sm:py-24 grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
        <div class="rounded-[32px] bg-gradient-to-b from-white to-sand-deep border border-hairline overflow-hidden">
            <img src="{{ asset('storage/listings/kraaltrac-pro-photo.webp') }}" alt="The KraalTrac Pro handheld EID reader with its 4×4 keypad and screen" loading="lazy" class="w-full h-auto">
        </div>
        <div>
            <h2 class="h-display text-[clamp(3rem,6vw,5.2rem)]">KraalTrac Pro</h2>
            <p class="mt-3 text-xl text-stone">Reads the ear tag, logs the weight, works with no signal.</p>
            <div class="mt-7 flex items-baseline gap-3"><span class="font-headline text-6xl">{{ $price }}</span><span class="text-stone">once-off</span></div>
            <ul class="mt-7 space-y-2.5">
                @foreach (['Standard ISO ear tags (sheep, goats, cattle)', 'Remembers ~2 000 weighings and your animal list offline', 'Set up for your farm before it ships'] as $t)
                    <li class="flex gap-3"><span class="mt-2.5 w-1.5 h-1.5 shrink-0 rounded-full bg-ochre"></span>{{ $t }}</li>
                @endforeach
            </ul>
            @if ($pro)<p class="mt-6 inline-flex items-center gap-2 text-sm"><span class="w-2 h-2 rounded-full {{ $pro->buyable() ? 'bg-[#3F7A3A]' : 'bg-ochre' }}"></span>{{ $pro->stockLabel() }}@if (! $pro->buyable() && $pro->next_batch). {{ $pro->next_batch }}@endif</p>@endif
            <div class="mt-5 flex flex-wrap gap-3">
                @if ($pro)@include('shop._buy', ['l' => $pro, 'qty' => true])
                <a href="{{ route('site.product', $pro) }}" class="btn-line">Everything about it</a>@endif
            </div>
        </div>
    </div>
</section>

{{-- KraalTrac Watch, short --}}
<section id="watch" class="relative overflow-hidden bg-char text-sand scroll-mt-20">
    <img src="{{ Img::url('windpomp-storm', true) }}" srcset="{{ Img::srcset('windpomp-storm') }}" sizes="100vw" alt="{{ Img::alt('windpomp-storm') }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover opacity-55 img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char via-char/75 to-char/20"></div>
    <div class="relative wrap py-24 sm:py-32 grid lg:grid-cols-2 gap-14 items-center">
        <div>
            <span class="chip bg-ochre text-char">{{ $watch?->stock_status === 'coming_soon' ? 'Coming soon · reserve now' : 'KraalTrac Watch' }}</span>
            <h2 class="h-display mt-6 text-[clamp(3rem,6.5vw,5.6rem)]">Know who didn't come to drink.</h2>
            <p class="mt-6 text-lg text-sand/80 max-w-md">It sits at the trough and reads every ear tag. Skipped water for a day? Your phone tells you.</p>
            <div class="mt-9 flex flex-wrap gap-3 items-center">
                @if ($watch)
                    @include('shop._buy', ['l' => $watch, 'class' => '!bg-sand !text-char !border-sand hover:!bg-white'])
                    <a href="{{ route('site.product', $watch) }}" class="btn-line-light">How it works</a>
                @endif
            </div>
        </div>
        <div class="rounded-[28px] bg-sand text-char p-6 sm:p-8 shadow-[0_40px_100px_-30px_rgba(0,0,0,.6)] max-w-md lg:ml-auto w-full">
            <div class="flex items-center justify-between text-sm text-stone"><span>North trough · today</span><span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#3F7A3A] animate-pulse"></span>Live</span></div>
            <div class="mt-3 font-headline text-6xl">412<span class="text-2xl text-stone"> / 418</span></div>
            <div class="text-sm text-stone">have been to drink in the last 24 hours</div>
            <div class="mt-4 h-2 rounded-full bg-sand-deep overflow-hidden"><div class="h-full bg-[#3F7A3A]" style="width: 98.5%"></div></div>
            <ul class="mt-5 divide-y divide-hairline text-sm">
                @foreach ([['DVS 25 5010', '31 h', '#B0452F'], ['DVS 24 3107', '27 h', '#B0452F'], ['DVS 25 5058', '25 h', '#B8732E']] as [$id, $h, $c])
                    <li class="py-2.5 flex justify-between"><span class="font-num">{{ $id }}</span><span style="color: {{ $c }}">last drink {{ $h }} ago</span></li>
                @endforeach
            </ul>
            <p class="mt-4 text-[12px] text-stone">Example screen with sample animals.</p>
        </div>
    </div>
</section>

{{-- Pricing: three numbers, the rest on tap --}}
<section id="pricing" class="wrap py-20 sm:py-28 scroll-mt-20" x-data="{ more: false }">
    <h2 class="h-display text-[clamp(2.8rem,6vw,5rem)]">What it costs.</h2>
    <div class="mt-10 grid md:grid-cols-3 gap-4">
        <div class="rounded-[24px] bg-white border border-hairline p-7">
            <div class="text-stone">KraalTrac Pro</div>
            <div class="font-headline text-5xl mt-2">{{ $price }}</div>
            <div class="text-sm text-stone mt-1">once-off, yours to keep</div>
        </div>
        <div class="rounded-[24px] bg-char text-sand p-7">
            <div class="text-sand/70">Herd Manager</div>
            <div class="font-headline text-5xl mt-2">{{ $monthly }}<span class="text-xl text-sand/60"> /month</span></div>
            <div class="text-sm text-sand/70 mt-1">First {{ $freeMonths }} months free · whole farm, every device</div>
        </div>
        <div class="rounded-[24px] bg-white border border-hairline p-7">
            <div class="text-stone">EID ear tags</div>
            <div class="font-headline text-5xl mt-2">R722</div>
            <div class="text-sm text-stone mt-1">for 50 · sheep, cattle, pigs</div>
        </div>
    </div>
    <button type="button" @click="more = !more" :aria-expanded="more" class="mt-6 text-sm font-medium underline" x-text="more ? 'Less' : 'Yearly price, contracts and what happens if you stop'">Yearly price, contracts and what happens if you stop</button>
    <div x-show="more" x-collapse x-cloak class="mt-5 grid md:grid-cols-3 gap-6 text-stone max-w-5xl">
        <p><strong class="text-char">Pay yearly:</strong> {{ $yearly }}, two months free. Pay by EFT or card.</p>
        <p><strong class="text-char">No contract, no debit order.</strong> You choose when to pay. Paying early adds on, you lose no days.</p>
        <p><strong class="text-char">Stop any time.</strong> Nothing is deleted: after {{ config('billing.grace_days') }} days it turns read-only, and you can still see and download everything.</p>
    </div>

    {{-- Payback, folded away --}}
    <details class="mt-12 group rounded-[24px] border border-hairline bg-white/60 open:bg-white"
             x-data="{ n: 300, kg: 1, price: 50, saved: 3, worth: 1800, device: {{ $proCents / 100 }}, sub: {{ Billing::price('monthly') / 100 }}, free: {{ $freeMonths }},
                get value() { return Math.round(this.n * this.kg * this.price + this.saved * this.worth); },
                get yearOne() { return this.device + this.sub * Math.max(0, 12 - this.free); },
                get months() { const b = this.value / 12; if (b * this.free >= this.device) return Math.max(1, Math.ceil(this.device / b)); return b > this.sub ? Math.ceil((this.device - this.sub * this.free) / (b - this.sub)) : null; },
                fmt(v) { return 'R' + Math.round(v).toLocaleString('en-US').replace(/,/g, ' '); } }">
        <summary class="cursor-pointer list-none p-6 sm:p-7 flex items-center justify-between gap-4">
            <span class="font-headline text-3xl">Does it pay for itself?</span>
            <span class="shrink-0 w-9 h-9 rounded-full border border-hairline grid place-items-center transition-transform group-open:rotate-45">+</span>
        </summary>
        <div class="px-6 sm:px-7 pb-8 grid lg:grid-cols-2 gap-10">
            <div class="space-y-6">
                @foreach ([
                    ['n', 'Animals you weigh a year', 20, 3000, 10, ''],
                    ['kg', 'Extra kg sold per animal (better timing)', 0, 5, 0.5, ' kg'],
                    ['price', 'Your live price per kg', 20, 120, 1, '/kg'],
                    ['saved', 'Animals saved a year by catching trouble early', 0, 20, 1, ''],
                    ['worth', 'What one of those animals is worth', 500, 20000, 100, ''],
                ] as [$m, $label, $min, $max, $step, $unit])
                    <div>
                        <div class="flex justify-between gap-4 text-sm"><label for="calc-{{ $m }}">{{ $label }}</label><span class="font-num font-medium shrink-0" x-text="{{ in_array($m, ['price', 'worth']) ? "fmt($m)" : $m }} + '{{ $unit }}'"></span></div>
                        <input id="calc-{{ $m }}" type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" x-model.number="{{ $m }}" class="mt-3 w-full accent-[#B8732E]">
                    </div>
                @endforeach
            </div>
            <div class="rounded-[24px] bg-char text-sand p-7 sm:p-9 self-start">
                <div class="text-sand/60">Worth to you, every year</div>
                <div class="mt-2 font-headline text-[clamp(3rem,7vw,5rem)] leading-none" x-text="fmt(value)"></div>
                <div class="mt-7 grid grid-cols-2 gap-6 border-t border-white/10 pt-7">
                    <div><div class="text-sand/60 text-sm">Cost in year one</div><div class="font-headline text-3xl mt-1" x-text="fmt(yearOne)"></div><div class="text-sand/50 text-xs">scanner + software</div></div>
                    <div><div class="text-sand/60 text-sm">Paid back in about</div><div class="font-headline text-3xl mt-1" x-text="months ? months + (months === 1 ? ' month' : ' months') : 'more than a year'"></div><div class="text-sand/50 text-xs">on these numbers</div></div>
                </div>
                <p class="mt-6 text-sm text-sand/50">A rough sum with your numbers, not a promise. It leaves out the hours you save.</p>
            </div>
        </div>
    </details>
</section>

{{-- Photo band --}}
<section class="relative h-[70svh] min-h-[480px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('merino-rams', true) }}" srcset="{{ Img::srcset('merino-rams') }}" sizes="100vw" alt="{{ Img::alt('merino-rams') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char/85 via-char/35 to-transparent"></div>
    <div class="relative wrap h-full flex items-center">
        <div class="max-w-xl">
            <h2 class="h-display text-[clamp(3rem,7vw,6.2rem)]">Built for the kraal, not the office.</h2>
            <p class="mt-5 text-lg text-white/80 max-w-md">Dust, cold fingers, no signal and 300 lambs before lunch.</p>
        </div>
    </div>
</section>

@push('head')<script type="application/ld+json">{!! $faqLd !!}</script>@endpush
<section id="faq" class="wrap py-20 sm:py-28 scroll-mt-20 grid lg:grid-cols-12 gap-12">
    <div class="lg:col-span-4">
        <h2 class="h-display text-[clamp(2.4rem,4.5vw,4rem)]">Questions.</h2>
        <p class="mt-4 text-stone">Something else? <a href="{{ route('site.contact') }}" class="underline">Ask us</a>.</p>
    </div>
    <div class="lg:col-span-8 divide-y divide-hairline border-y border-hairline" x-data="{ open: null }">
        @foreach ($faqs as $i => [$q, $a])
            <div>
                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}" class="w-full flex items-center justify-between gap-6 py-5 text-left">
                    <span class="text-lg">{{ $q }}</span>
                    <span class="shrink-0 w-9 h-9 rounded-full border border-hairline grid place-items-center transition-transform duration-300" :class="open === {{ $i }} && 'rotate-45 bg-char text-sand border-char'">+</span>
                </button>
                <div x-show="open === {{ $i }}" x-collapse x-cloak><p class="pb-6 max-w-2xl text-stone leading-relaxed">{{ $a }}</p></div>
            </div>
        @endforeach
    </div>
</section>

<section class="wrap pb-16 sm:pb-20">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach ([
            ['6-month warranty', route('legal.show', 'returns')],
            ['7 days to change your mind', route('legal.show', 'returns')],
            ['Help from the people who built it', route('site.contact')],
            ['Your data stays yours', route('legal.show', 'data')],
        ] as [$t, $href])
            <a href="{{ $href }}" class="block border-t-2 border-char pt-4 font-medium hover:border-ochre transition">{{ $t }}</a>
        @endforeach
    </div>
</section>

<section class="relative overflow-hidden bg-char text-sand">
    <img src="{{ Img::url('dirt-road', true) }}" srcset="{{ Img::srcset('dirt-road') }}" sizes="100vw" alt="{{ Img::alt('dirt-road') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-60 img-grade">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/50 to-char/20"></div>
    <div class="relative wrap py-28 sm:py-36 text-center">
        <h2 class="h-display text-[clamp(3rem,8vw,7rem)]">Ken jou kudde.</h2>
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <a href="#shop" class="btn-light">Shop</a>
            <a href="{{ route('site.contact') }}" class="btn-line-light">Talk to us first</a>
        </div>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['windpomp-pink', 'karoo-mist', 'windpomp-storm', 'merino-rams', 'dirt-road']" dark />@endsection
