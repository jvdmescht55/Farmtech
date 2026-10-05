@extends('layouts.site')
@php
    use App\Support\SiteImages as Img;
    $pro = $pro ?? null;
    $watch = $listings->firstWhere('slug', 'kraaltrac-watch');
    $price = $pro?->price_cents ? 'R'.number_format($pro->price_cents / 100, 0, '.', ' ') : 'R7 999';
@endphp
@section('title', 'KraalTrac Pro & Herd Manager — Farmtech')
@section('description', 'KraalTrac Pro: a handheld EID reader and weigh logger built in South Africa, with Herd Manager included. Scan the tag, punch in the weight — your herd book, gains and auction book do the rest. '.$price.' once-off.')
@section('hero_dark', '1')
@push('head')<link rel="preload" as="image" href="{{ Img::url('windpomp-pink', true) }}" imagesrcset="{{ Img::srcset('windpomp-pink') }}" imagesizes="100vw">@endpush

@section('content')
{{-- Hero --}}
<section class="relative h-[100svh] min-h-[640px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('windpomp-pink') }}" srcset="{{ Img::srcset('windpomp-pink') }}" sizes="100vw" alt="{{ Img::alt('windpomp-pink') }}" class="absolute inset-0 h-full w-full object-cover object-[50%_65%] img-grade kenburns" fetchpriority="high">
    <div class="absolute inset-0 bg-gradient-to-t from-char/90 via-char/25 to-char/45"></div>
    <div class="relative wrap h-full flex flex-col justify-end pb-14 sm:pb-20">
        <h1 class="h-display text-[clamp(4.2rem,15vw,13rem)] leading-[0.86] reveal-up">Ken jou<br>kudde.</h1>
        <div class="mt-10 grid lg:grid-cols-[1fr_auto] gap-8 items-end reveal-up" style="animation-delay:.15s">
            <p class="max-w-xl text-lg sm:text-xl text-white/85 leading-relaxed">Scan the ear tag, punch in the weight. Your herd book, daily gains and auction book sort themselves out on your phone, even when there's no signal in the kraal.</p>
            <div class="flex flex-wrap gap-3">
                <a href="#shop" class="btn-light">Shop now</a>
                <a href="#pro" class="btn-line-light">See how it works</a>
            </div>
        </div>
        <ul class="mt-12 pt-6 border-t border-white/15 flex flex-wrap gap-x-8 gap-y-2 text-sm text-white/65">
            <li>Designed and built in South Africa</li>
            <li>Reads standard ISO ear tags</li>
            <li>Once-off price, Herd Manager included</li>
            <li>6-month warranty</li>
        </ul>
    </div>
</section>

{{-- This week's prices --}}
<section class="wrap -mt-10 relative z-10"><x-market-strip class="shadow-[0_30px_70px_-40px_rgba(0,0,0,.4)]" /></section>

{{-- The shop --}}
<section id="shop" class="wrap pt-20 sm:pt-28 pb-20 scroll-mt-20">
    <div class="grid lg:grid-cols-12 gap-8 items-end">
        <h2 class="lg:col-span-7 h-display text-[clamp(2.8rem,6vw,5rem)]">Tag it, weigh it, watch it.</h2>
        <p class="lg:col-span-5 text-stone text-lg leading-relaxed">Everything here works with Herd Manager, included free. Sold out? Reserve from the next batch. You only pay when it's ready.</p>
    </div>
    <div class="mt-12 grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach ($listings as $l)
            @include('shop._card', ['l' => $l])
        @endforeach
        <article class="group relative rounded-[28px] overflow-hidden bg-char text-sand min-h-[420px] flex">
            <img src="{{ Img::url('karoo-mist', true) }}" alt="{{ Img::alt('karoo-mist') }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover opacity-70 img-grade transition duration-[1.2s] group-hover:scale-[1.04]">
            <div class="absolute inset-0 bg-gradient-to-t from-char via-char/40 to-transparent"></div>
            <div class="relative self-end p-7">
                <div class="text-sm text-sand/70">Custom builds</div>
                <h3 class="mt-1 font-headline text-3xl">Something only your farm needs</h3>
                <p class="mt-2 text-sand/70 text-[15px]">Trough levels, fence alarms, pump monitors. Quoted for you, built by us.</p>
                <a href="{{ route('site.custom') }}" class="btn-light mt-5">See what we build</a>
            </div>
        </article>
    </div>
</section>

{{-- Photo band --}}
<section class="relative h-[78svh] min-h-[520px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('merino-rams', true) }}" srcset="{{ Img::srcset('merino-rams') }}" sizes="100vw" alt="{{ Img::alt('merino-rams') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char/85 via-char/35 to-transparent"></div>
    <div class="relative wrap h-full flex items-center">
        <div class="max-w-xl">
            <h2 class="h-display text-[clamp(3rem,7vw,6.2rem)]">Built for the kraal, not the office.</h2>
            <p class="mt-6 text-lg text-white/80 max-w-md">Dust, cold fingers, no signal and 300 lambs before lunch. That's what we designed for, not a desk.</p>
            <div class="mt-8 flex flex-wrap gap-2 text-sm">
                @foreach (['Works without signal', 'Reads in under a second', 'Designed in South Africa'] as $t)<span class="rounded-full border border-white/30 px-4 py-2">{{ $t }}</span>@endforeach
            </div>
        </div>
    </div>
</section>

{{-- The device --}}
<section id="pro" class="scroll-mt-20 overflow-hidden">
    <div class="wrap py-24 sm:py-32 grid lg:grid-cols-2 gap-16 lg:gap-20 items-center">
        <div class="order-2 lg:order-1 rounded-[36px] bg-gradient-to-b from-sand-deep to-sand border border-hairline pt-12 pb-8 px-4 sm:px-8">
            @include('site._device')
            <p class="mt-6 text-center text-[13px] text-stone">The screens above are the real KraalTrac Pro screens, one weighing after another.</p>
        </div>
        <div class="order-1 lg:order-2">
            <h2 class="h-display text-[clamp(3rem,6vw,5.2rem)]">KraalTrac Pro</h2>
            <p class="mt-3 text-xl text-stone">{{ $pro?->tagline ?: 'EID handheld RFID reader & weight-logging terminal' }}</p>
            <div class="mt-8 flex items-baseline gap-4">
                <span class="font-headline text-6xl">{{ $price }}</span>
                <span class="text-stone">once-off, incl. VAT<br class="sm:hidden"> · no subscription</span>
            </div>
            <p class="mt-2 text-sm text-stone">{{ $pro?->availability ?: 'Built to order, fully assembled and tested' }}</p>

            <dl class="mt-10 divide-y divide-hairline border-y border-hairline">
                @foreach ([
                    ['Reads the tag for you', 'Hold the wand to the ear. The 134.2 kHz reader picks up standard FDX-B tags, so nobody types 15-digit numbers with cold fingers.'],
                    ['Keeps going without signal', 'Up to 300 records stay on the device. It sends them by itself when it finds Wi-Fi, or through your phone\'s hotspot.'],
                    ['Birth, wean, post-wean, mature', 'Weight type, sex, sire and dam straight from the keypad. New lambs get a birthday number like 250912.'],
                    ['Made for the crush', 'PETG shell with 3.5 mm walls and internal ribs. Survives dust, drops and the odd kick.'],
                ] as [$t, $d])
                    <div class="py-5 grid sm:grid-cols-[13rem_1fr] gap-1 sm:gap-6">
                        <dt class="font-medium">{{ $t }}</dt>
                        <dd class="text-stone leading-relaxed">{{ $d }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($pro)<p class="mt-6 inline-flex items-center gap-2 text-sm"><span class="w-2 h-2 rounded-full {{ $pro->buyable() ? 'bg-[#3F7A3A]' : 'bg-ochre' }}"></span>{{ $pro->stockLabel() }}@if (! $pro->buyable() && $pro->next_batch). {{ $pro->next_batch }}@endif</p>@endif
            <div class="mt-5 flex flex-wrap gap-3">
                @if ($pro)@include('shop._buy', ['l' => $pro, 'qty' => true])@endif
                <a href="#weighday" class="btn-line">How a weigh day goes</a>
            </div>

            <details class="mt-8 group">
                <summary class="cursor-pointer text-sm font-medium inline-flex items-center gap-2">Full specs &amp; what's in the box <span class="transition group-open:rotate-45">+</span></summary>
                <div class="mt-5 grid sm:grid-cols-2 gap-8 text-sm">
                    <dl class="space-y-3">
                        @foreach ($pro?->specs ?: [] as $s)
                            <div><dt class="text-stone">{{ $s['label'] }}</dt><dd>{{ $s['value'] }}</dd></div>
                        @endforeach
                    </dl>
                    <div>
                        <div class="text-stone mb-2">In the box</div>
                        <ul class="space-y-2">@foreach ($pro?->in_box ?: [] as $b)<li>{{ $b }}</li>@endforeach</ul>
                    </div>
                </div>
            </details>
        </div>
    </div>
</section>

{{-- A weigh day --}}
<section id="weighday" class="bg-char text-sand scroll-mt-20">
    <div class="wrap py-24 sm:py-32">
        <div class="grid lg:grid-cols-12 gap-10 items-end">
            <h2 class="lg:col-span-7 h-display text-[clamp(2.8rem,5.5vw,4.8rem)]">A weigh day with 312 lambs.</h2>
            <p class="lg:col-span-5 text-sand/70 text-lg leading-relaxed">Here's how a morning in the kraal goes. The numbers are an example. The steps are exactly how it works.</p>
        </div>
        <ol class="mt-16 grid md:grid-cols-2 xl:grid-cols-4 gap-5">
            @foreach ([
                ['06:30', 'flock-bakkie', 'In the kraal', 'Lambs come through the race. One person on the scale, one with the KraalTrac.'],
                ['06:35', 'tagged-ewe', 'Scan, weigh, next', 'Wand to the ear, type the weight, press #. About eight seconds a lamb, no clipboard.'],
                ['09:10', 'flock-golden', 'Done before tea', 'Your phone already shows 312 weighed, 186 g/day average gain and 14 lambs lighter than last time.'],
                ['09:15', 'dorper-ram', 'Sort and sell', 'Sort by weight: 58 are market-ready. The auction book with lot numbers takes one more click.'],
            ] as [$time, $img, $t, $d])
                <li class="group">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-[22px]">
                        <img src="{{ Img::url($img, true) }}" alt="{{ Img::alt($img) }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover img-grade transition duration-[1.2s] group-hover:scale-105">
                        <span class="absolute left-4 top-4 rounded-full bg-char/80 backdrop-blur px-3 py-1 font-num text-sm">{{ $time }}</span>
                    </div>
                    <h3 class="mt-5 text-xl font-medium">{{ $t }}</h3>
                    <p class="mt-2 text-sand/65 leading-relaxed">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- KraalTrac Watch --}}
<section id="watch" class="relative overflow-hidden bg-char text-sand scroll-mt-20">
    <img src="{{ Img::url('windpomp-storm', true) }}" srcset="{{ Img::srcset('windpomp-storm') }}" sizes="100vw" alt="{{ Img::alt('windpomp-storm') }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover opacity-55 img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char via-char/75 to-char/20"></div>
    <div class="relative wrap py-24 sm:py-32 grid lg:grid-cols-2 gap-14 items-center">
        <div>
            <span class="chip bg-ochre text-char">{{ $watch?->stock_status === 'coming_soon' ? 'Coming soon · reserve now' : 'KraalTrac Watch' }}</span>
            <h2 class="h-display mt-6 text-[clamp(3rem,6.5vw,5.6rem)]">Know who didn't come to drink.</h2>
            <p class="mt-6 text-lg text-sand/80 max-w-lg leading-relaxed">The KraalTrac Watch hangs at the trough or the gate and reads every ear tag that walks past. If an animal hasn't been to water in a day, your phone tells you. Sick animals stop drinking first.</p>
            <ul class="mt-8 space-y-3 text-sand/85">
                @foreach (['Counts every animal at every water point, day and night', 'Alerts when one skips the water for 24 hours (or your own limit)', 'Visits show on each animal\'s page next to its weights', 'One per trough or gate, all in the same Herd Manager'] as $t)
                    <li class="flex gap-3"><span class="mt-2.5 w-1.5 h-1.5 shrink-0 rounded-full bg-ochre-light"></span>{{ $t }}</li>
                @endforeach
            </ul>
            <div class="mt-10 flex flex-wrap gap-3 items-center">
                @if ($watch)
                    @include('shop._buy', ['l' => $watch, 'class' => '!bg-sand !text-char !border-sand hover:!bg-white'])
                    <a href="{{ route('site.product', $watch) }}" class="btn-line-light">Details</a>
                @endif
            </div>
            @if ($watch?->next_batch)<p class="mt-4 text-sm text-sand/60 max-w-md">{{ $watch->next_batch }}</p>@endif
        </div>
        {{-- What the farmer sees --}}
        <div class="rounded-[28px] bg-sand text-char p-6 sm:p-8 shadow-[0_40px_100px_-30px_rgba(0,0,0,.6)] max-w-md lg:ml-auto w-full">
            <div class="flex items-center justify-between text-sm text-stone"><span>North trough · today</span><span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#3F7A3A] animate-pulse"></span>Live</span></div>
            <div class="mt-3 font-headline text-6xl">412<span class="text-2xl text-stone"> / 418</span></div>
            <div class="text-sm text-stone">have been to drink in the last 24 hours</div>
            <div class="mt-4 h-2 rounded-full bg-sand-deep overflow-hidden"><div class="h-full bg-[#3F7A3A]" style="width: 98.5%"></div></div>
            <div class="mt-6 text-sm font-medium">Not seen at water</div>
            <ul class="mt-2 divide-y divide-hairline text-sm">
                @foreach ([['DVS 25 5010', '31 h', '#B0452F'], ['DVS 24 3107', '27 h', '#B0452F'], ['DVS 25 5058', '25 h', '#B8732E']] as [$id, $h, $c])
                    <li class="py-2.5 flex justify-between"><span class="font-num">{{ $id }}</span><span style="color: {{ $c }}">last drink {{ $h }} ago</span></li>
                @endforeach
            </ul>
            <p class="mt-4 text-[12px] text-stone">Example screen with sample animals.</p>
        </div>
    </div>
</section>

{{-- Software --}}
<section id="software" class="overflow-hidden scroll-mt-20">
    <div class="wrap py-24 sm:py-32 grid lg:grid-cols-12 gap-14 items-center">
        <div class="lg:col-span-5">
            <h2 class="h-display text-[clamp(2.8rem,5.5vw,4.6rem)]">Herd Manager comes with it.</h2>
            <p class="mt-6 text-stone text-lg leading-relaxed max-w-md">Every scan lands here: on your phone in the kraal, or on the laptop at night. Tap through the four screens to see them.</p>
            <ul class="mt-8 space-y-4">
                @foreach ([
                    ['Weigh days', 'Average weight, daily gain per animal, and who\'s falling behind.'],
                    ['Each animal', 'Growth curve, parents, lambing record and every treatment.'],
                    ['Auction books', 'Lot numbers like 66A, 66B and the Logix layout, ready to print.'],
                    ['Alerts', 'Weight loss, twins due, withdrawal dates, missed drinks.'],
                ] as [$t, $d])
                    <li class="flex gap-4"><span class="mt-2.5 w-1.5 h-1.5 shrink-0 rounded-full bg-ochre"></span><div><span class="font-medium">{{ $t }}.</span> <span class="text-stone">{{ $d }}</span></div></li>
                @endforeach
            </ul>
        </div>
        <div class="lg:col-span-7 store-showcase">
@include('site._showcase')
            <p class="mt-5 text-[13px] text-stone">Sample data from a demo flock.</p>
        </div>
    </div>
</section>

{{-- Custom builds --}}
<section class="relative overflow-hidden bg-char text-sand">
    <img src="{{ Img::url('golden-valley', true) }}" srcset="{{ Img::srcset('golden-valley') }}" sizes="100vw" alt="{{ Img::alt('golden-valley') }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover opacity-55 img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char via-char/80 to-char/30"></div>
    <div class="relative wrap py-24 sm:py-32">
        <div class="max-w-2xl">
            <h2 class="h-display text-[clamp(2.8rem,6vw,5rem)]">Need something that doesn't exist yet?</h2>
            <p class="mt-6 text-lg text-sand/75 leading-relaxed">We also build gadgets for one farm at a time. A sensor on the trough, a counter at the gate, an alarm on the fence. It shows up in Herd Manager next to your animals, with its own graphs and alerts.</p>
        </div>
        <div class="mt-12 grid sm:grid-cols-3 gap-4 max-w-4xl">
            @foreach ([
                ['Trough runs dry', 'A level sensor texts you before the sheep notice.'],
                ['Fence goes down', 'You get an alert the moment the voltage drops.'],
                ['Borehole pump stops', 'Flow and battery every hour, alarm if it stops.'],
            ] as [$t, $d])
                <div class="rounded-2xl bg-white/10 border border-white/10 backdrop-blur p-5">
                    <div class="font-medium">{{ $t }}</div>
                    <p class="mt-1.5 text-sm text-sand/65">{{ $d }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-10 flex flex-wrap gap-3">
            <a href="{{ route('site.custom') }}" class="btn-light">See custom builds</a>
            <a href="{{ route('site.suggest', ['kind' => 'custom_build']) }}" class="btn-line-light">Tell us your problem</a>
        </div>
    </div>
</section>

{{-- Compare --}}
<section class="bg-sand-deep/60 border-y border-hairline">
    <div class="wrap py-24 sm:py-32">
        <h2 class="h-display text-[clamp(2.6rem,5vw,4.2rem)] max-w-3xl">What you'd use instead.</h2>
        <p class="mt-5 text-stone text-lg max-w-2xl">Most farms weigh with a notebook and an evening in Excel, or save up for an imported system. Here's how they line up.</p>
        @php
            $yes = '<span class="text-[#3F7A3A] font-medium">Yes</span>';
            $rows = [
                ['What it costs', 'A notebook and your evenings', 'R25 000 – R50 000+', '<strong>'.$price.'</strong> once-off'],
                ['Monthly fees', 'None', 'Often a software subscription', 'None. Herd Manager is included'],
                ['Reads ear tags by itself', 'No', $yes, $yes],
                ['Works with no signal', 'Paper does', 'Depends on the model', $yes.', 300 records'],
                ['Daily gain & sorting', 'Calculator', $yes, $yes.', instantly'],
                ['Auction book, Logix layout', 'By hand', 'Rarely', $yes.', one click'],
                ['Warns you about problems', 'No', 'Some', 'Weight loss, lambing, water, withdrawals'],
                ['Who you phone for help', '—', 'An importer', 'The people who built it'],
            ];
        @endphp
        {{-- Phones: one card per question --}}
        <div class="mt-10 space-y-3 md:hidden">
            @foreach ($rows as [$k, $a, $b, $c])
                <div class="rounded-2xl bg-white border border-hairline p-5">
                    <div class="font-medium">{{ $k }}</div>
                    <dl class="mt-3 grid grid-cols-[7.5rem_1fr] gap-x-3 gap-y-1.5 text-sm">
                        <dt class="text-stone">Notebook</dt><dd>{!! $a !!}</dd>
                        <dt class="text-stone">Imported</dt><dd>{!! $b !!}</dd>
                        <dt class="font-medium">KraalTrac Pro</dt><dd class="font-medium">{!! $c !!}</dd>
                    </dl>
                </div>
            @endforeach
        </div>
        <div class="hidden md:block mt-12">
            <table class="w-full text-[15px] bg-white rounded-[24px] overflow-hidden border border-hairline">
                <thead>
                    <tr class="text-left">
                        <th class="p-5 w-[28%] font-normal text-stone"></th>
                        <th class="p-5 font-medium">Notebook &amp; Excel</th>
                        <th class="p-5 font-medium">Imported system</th>
                        <th class="p-5 font-medium bg-char text-sand">KraalTrac Pro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as [$k, $a, $b, $c])
                        <tr class="border-t border-hairline">
                            <td class="p-5 text-stone">{{ $k }}</td>
                            <td class="p-5">{!! $a !!}</td>
                            <td class="p-5">{!! $b !!}</td>
                            <td class="p-5 bg-sand-light">{!! $c !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-4 text-[13px] text-stone">Imported system prices are typical retail ranges in South Africa and vary by brand and package.</p>
    </div>
</section>

{{-- Calculator --}}
<section class="wrap py-24 sm:py-32" x-data="{ n: 300, kg: 1, price: 50, saved: 3, worth: 1800,
        get value() { return Math.round(this.n * this.kg * this.price + this.saved * this.worth); },
        get months() { return this.value ? Math.max(1, Math.round(7999 / (this.value / 12))) : null; },
        fmt(v) { return 'R' + v.toLocaleString('en-US').replace(/,/g, ' '); } }">
    <div class="grid lg:grid-cols-2 gap-14 items-start">
        <div>
            <h2 class="h-display text-[clamp(2.6rem,5vw,4.2rem)]">Does it pay for itself?</h2>
            <p class="mt-5 text-stone text-lg max-w-md">Put in your own numbers. Most of the value comes from selling at the right weight and catching sick animals sooner.</p>
            <div class="mt-10 space-y-7">
                @foreach ([
                    ['n', 'Lambs, kids or calves you weigh a year', 20, 3000, 10, ''],
                    ['kg', 'Extra kg sold per animal (better timing, fewer poor doers)', 0, 5, 0.5, ' kg'],
                    ['price', 'Your live price per kg', 20, 120, 1, '/kg'],
                    ['saved', 'Animals saved a year by spotting trouble early', 0, 20, 1, ''],
                    ['worth', 'What one of those animals is worth', 500, 8000, 100, ''],
                ] as [$m, $label, $min, $max, $step, $unit])
                    <div>
                        <div class="flex justify-between gap-4 text-sm"><label for="calc-{{ $m }}">{{ $label }}</label><span class="font-num font-medium shrink-0" x-text="{{ in_array($m, ['price', 'worth']) ? "fmt($m)" : $m }} + '{{ $unit }}'"></span></div>
                        <input id="calc-{{ $m }}" type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" x-model.number="{{ $m }}" class="mt-3 w-full accent-[#B8732E]">
                    </div>
                @endforeach
            </div>
        </div>
        <div class="lg:sticky lg:top-28 rounded-[32px] bg-char text-sand p-8 sm:p-12">
            <div class="text-sand/60">Worth to you, every year</div>
            <div class="mt-2 font-headline text-[clamp(3.6rem,8vw,6rem)] leading-none" x-text="fmt(value)"></div>
            <div class="mt-8 grid grid-cols-2 gap-6 border-t border-white/10 pt-8">
                <div><div class="text-sand/60 text-sm">KraalTrac Pro</div><div class="font-headline text-4xl mt-1">{{ $price }}</div><div class="text-sand/50 text-sm">once-off</div></div>
                <div><div class="text-sand/60 text-sm">Paid back in about</div><div class="font-headline text-4xl mt-1" x-text="months ? months + (months === 1 ? ' month' : ' months') : '—'"></div><div class="text-sand/50 text-sm">on these numbers</div></div>
            </div>
            <p class="mt-8 text-sm text-sand/50 leading-relaxed">This is a rough sum with your numbers, not a promise. It leaves out the time you save on weigh days and auction books.</p>
            <a href="#shop" class="btn-light mt-8">Reserve yours</a>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="bg-sand-deep/60 border-y border-hairline scroll-mt-20">
    <div class="wrap py-24 sm:py-32 grid lg:grid-cols-12 gap-14">
        <div class="lg:col-span-4">
            <h2 class="h-display text-[clamp(2.4rem,4.5vw,4rem)]">Questions farmers ask us.</h2>
            <p class="mt-5 text-stone">Something else? <a href="{{ route('site.contact') }}" class="underline">Ask us directly</a>.</p>
        </div>
        <div class="lg:col-span-8 divide-y divide-hairline border-y border-hairline" x-data="{ open: 0 }">
            @foreach ([
                ['Will it read my existing tags?', 'If they are standard animal EID tags (134.2 kHz FDX-B, ISO 11784/11785), yes. Those are the usual ones in South Africa.'],
                ['Does it work for goats and cattle?', 'Yes. Sheep, goats and cattle all live in the same herd book, each with their own birth-weight and alert limits.'],
                ['What if there\'s no signal in the kraal?', 'The KraalTrac keeps up to 300 records on the device and sends them once it finds Wi-Fi. Easiest of all: switch on your phone\'s hotspot while you weigh.'],
                ['And if there\'s no Wi-Fi at all?', 'Plug it into a laptop, open Herd Manager in Chrome and click Sync the scale. It uploads everything and clears the device only once every record is saved.'],
                ['Does it connect to my scale?', 'For now you type the weight from your scale\'s display. It takes about two seconds a lamb. A direct scale cable is on our list.'],
                ['Is there a monthly fee?', 'No. Herd Manager comes with the device.'],
                ['Can I bring my Logix or stud book records?', 'Yes. Upload the Excel or CSV export. We read the IDs, parents and EBVs and work out the SP, C and B tiers.'],
                ['How does buying work?', 'Add it to your cart and check out. Pay by EFT (or card, where offered) and we courier it to you or you collect. If something is sold out, reserve it: nothing is paid until your batch is ready, and you can cancel before then.'],
                ['What does "reserve" mean?', 'When a batch sells out, you can hold a unit from the next one. We phone you when it\'s ready, confirm the price and delivery date, and then you pay.'],
                ['Who owns my data?', 'You do. Export everything any time. We never sell it.'],
            ] as $i => [$q, $a])
                <div>
                    <button type="button" @click="open = open === {{ $i }} ? -1 : {{ $i }}" :aria-expanded="open === {{ $i }}" class="w-full flex items-center justify-between gap-6 py-6 text-left">
                        <span class="text-lg sm:text-xl">{{ $q }}</span>
                        <span class="shrink-0 w-9 h-9 rounded-full border border-hairline grid place-items-center transition-transform duration-300" :class="open === {{ $i }} && 'rotate-45 bg-char text-sand border-char'">+</span>
                    </button>
                    <div x-show="open === {{ $i }}" x-transition.opacity.duration.300ms x-cloak><p class="pb-7 -mt-1 max-w-2xl text-stone leading-relaxed">{{ $a }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Peace of mind --}}
<section class="wrap py-16 sm:py-20">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach ([
            ['6-month warranty', 'If it breaks from normal kraal use, we fix or replace it.', route('legal.show', 'returns')],
            ['7 days to change your mind', 'Send it back within 7 days of delivery.', route('legal.show', 'returns')],
            ['Help from the builders', 'Talk to the people who made it, not a call centre.', route('site.contact')],
            ['Your data stays yours', 'Export everything any time. POPIA-compliant. Never sold.', route('legal.show', 'data')],
        ] as [$t, $d, $href])
            <a href="{{ $href }}" class="block border-t-2 border-char pt-5 hover:border-ochre transition">
                <div class="font-medium">{{ $t }}</div>
                <p class="mt-1.5 text-sm text-stone leading-relaxed">{{ $d }}</p>
            </a>
        @endforeach
    </div>
</section>

<section class="relative overflow-hidden bg-char text-sand">
    <img src="{{ Img::url('dirt-road', true) }}" srcset="{{ Img::srcset('dirt-road') }}" sizes="100vw" alt="{{ Img::alt('dirt-road') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-60 img-grade">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/50 to-char/20"></div>
    <div class="relative wrap py-28 sm:py-40 text-center">
        <h2 class="h-display text-[clamp(3rem,8vw,7rem)]">Ken jou kudde.</h2>
        <p class="mt-5 text-lg text-sand/75 max-w-xl mx-auto">Start with one KraalTrac and a pack of tags. Herd Manager comes with it.</p>
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <a href="#shop" class="btn-light">Shop now</a>
            <a href="{{ route('site.contact') }}" class="btn-line-light">Talk to us first</a>
        </div>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['windpomp-pink', 'karoo-mist', 'merino-rams', 'flock-bakkie', 'tagged-ewe', 'flock-golden', 'dorper-ram', 'windpomp-storm', 'golden-valley', 'dirt-road']" dark />@endsection
