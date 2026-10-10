@extends('layouts.site')
@php
    use App\Support\SiteImages as Img;
    // [key, title, the problem, how it works, reading label, sample value, alert rule, sparkline points]
    $builds = [
        ['trough', 'Trough level sensor', 'The trough runs dry on a hot day and nobody notices until the sheep are standing at an empty trough.', 'A float sensor on the trough or reservoir reports the level every 15 minutes.', 'Level', '68%', 'Alert below 25%', '30,28,24,26,20,14,12,16,10'],
        ['fence', 'Electric fence monitor', 'A jackal or a fallen branch shorts the fence and you only find out when the lambs are gone.', 'A clip-on unit reads the fence voltage and alerts you the moment it drops.', 'Voltage', '7.8 kV', 'Alert below 4 kV', '12,12,13,12,12,30,12,12,12'],
        ['pump', 'Borehole pump watch', 'The solar pump stopped two days ago and the reservoir is half empty.', 'Measures flow and battery voltage, every hour.', 'Flow', '1.4 m³/h', 'Alert if flow stops 2 h', '18,14,10,12,16,18,14,10,12'],
        ['weigher', 'Walk-over weigher', 'Weighing the whole flock means a full day in the kraal.', 'A platform on the path to water weighs each tagged animal as it walks over. Weights land on the animal\'s page.', 'Avg weight', '43.1 kg', 'Alert on 5% loss', '30,28,27,25,24,22,21,19,18'],
        ['lambing', 'Lambing camp watch', 'Night checks in the lambing camp, every two hours, for weeks.', 'A motion and sound sensor in the pen tells you which pen to check.', 'Tonight', '3 alerts', 'Alert on activity 22:00–05:00', '30,30,10,30,30,20,30,8,30'],
        ['cold', 'Cold room & vaccine fridge', 'The vaccine fridge failed over the weekend and R6 000 of stock went off.', 'A temperature probe logs every 10 minutes and alarms outside 2–8 °C.', 'Temp', '4.1 °C', 'Alert outside 2–8 °C', '20,21,20,19,20,21,20,12,20'],
        ['rain', 'Rain gauge per camp', 'You want to know which camps got rain before you move the flock.', 'A tipping-bucket gauge in each camp, logged automatically.', 'This week', '24 mm', 'Weekly summary', '30,30,22,30,30,14,30,30,26'],
        ['gps', 'Bell-ewe GPS', 'The flock is somewhere in a 600 ha camp and the bakkie is low on diesel.', 'A small collar on one or two ewes shows where the flock is grazing.', 'Where', 'Rivierkamp', 'Alert if outside the camp', '20,18,22,16,20,24,18,22,20'],
    ];
@endphp
@section('title', 'Custom builds — Farmtech')
@section('description', 'Farm gadgets built for one farm at a time: trough level, fence voltage, pump flow, walk-over weighing and more — all reporting to Herd Manager. Or plug in a device you built yourself.')
@section('hero_dark', '1')

@section('content')
{{-- Hero --}}
<section class="relative min-h-[88svh] overflow-hidden bg-char text-white flex">
    <img src="{{ Img::url('windpomp-storm') }}" srcset="{{ Img::srcset('windpomp-storm') }}" sizes="100vw" alt="{{ Img::alt('windpomp-storm') }}" class="absolute inset-0 h-full w-full object-cover img-grade kenburns" fetchpriority="high">
    <div class="absolute inset-0 bg-gradient-to-t from-char/90 via-char/35 to-char/50"></div>
    <div class="relative wrap self-end pb-16 sm:pb-24 pt-40">
        <p class="text-white/70 text-lg">Custom builds</p>
        <h1 class="h-display mt-4 text-[clamp(3.4rem,9vw,8rem)] max-w-5xl">Your farm has a problem nobody sells a fix for.</h1>
        <p class="mt-8 max-w-xl text-lg sm:text-xl text-white/80 leading-relaxed">Tell us what keeps going wrong. We design a small device for it, build it, and it reports straight into Herd Manager, next to your animals.</p>
        <div class="mt-10 flex flex-wrap gap-3">
            <a href="#ask" class="btn-light">Tell us your problem</a>
            <a href="#examples" class="btn-line-light">See what we can build</a>
        </div>
    </div>
</section>

{{-- Two ways --}}
<section class="wrap py-24 sm:py-28">
    <h2 class="h-display text-[clamp(2.4rem,4.5vw,3.8rem)] max-w-3xl">There are two ways to get a custom device into Herd Manager.</h2>
    <div class="mt-12 grid lg:grid-cols-2 gap-5">
        <div class="rounded-[28px] bg-char text-sand p-8 sm:p-10 flex flex-col">
            <div class="text-ochre-light font-medium">Most farmers</div>
            <h3 class="mt-3 font-headline text-4xl sm:text-5xl">We build it for you</h3>
            <p class="mt-4 text-sand/70 leading-relaxed">You describe the problem. We work out the sensor, the power (usually a small solar panel) and the signal, quote you, build it and help you set it up.</p>
            <ol class="mt-8 space-y-4 text-sand/85">
                @foreach (['You tell us the problem. Two minutes, form below.', 'We phone you to understand the camp, power and signal.', 'You get a fixed quote before anything is built. No obligation.', 'We build and test it. It shows up in Herd Manager when you switch it on.'] as $i => $t)
                    <li class="flex gap-4"><span class="font-num text-ochre-light w-5 shrink-0">{{ $i + 1 }}</span>{{ $t }}</li>
                @endforeach
            </ol>
            <a href="#ask" class="btn-light mt-10 self-start">Tell us your problem</a>
        </div>
        <div class="rounded-[28px] bg-white border border-hairline p-8 sm:p-10 flex flex-col">
            <div class="text-ochre-dark font-medium">For tinkerers</div>
            <h3 class="mt-3 font-headline text-4xl sm:text-5xl">You built your own</h3>
            <p class="mt-4 text-stone leading-relaxed">Got an ESP32 or Arduino on the windmill already? Add it under <strong>Custom devices</strong> in Herd Manager, give it a name and limits, and point it at the link it gives you. Part of every Herd Manager account, at no extra cost.</p>
            <pre class="mt-8 rounded-2xl bg-char text-sand/90 p-5 text-[12.5px] leading-relaxed overflow-x-auto font-num"><span class="text-sand/45">// Send one reading. That's all it takes</span>
POST https://farmtech.site/api/v1/readings
Authorization: Bearer <span class="text-ochre-light">your-device-key</span>

level=68&amp;battery=12.6</pre>
            <p class="mt-4 text-sm text-stone">Readings get graphs, history and the alerts you set. Step-by-step guide inside Herd Manager.</p>
            <a href="{{ auth()->check() ? route('custom.index') : route('landing') }}" class="btn-line mt-8 self-start">{{ auth()->check() ? 'Open Custom devices' : 'Sign in to add a device' }}</a>
        </div>
    </div>
</section>

{{-- Examples --}}
<section id="examples" class="bg-sand-deep/60 border-y border-hairline scroll-mt-20">
    <div class="wrap py-24 sm:py-28">
        <div class="grid lg:grid-cols-12 gap-8 items-end">
            <h2 class="lg:col-span-7 h-display text-[clamp(2.6rem,5vw,4.4rem)]">Things we can build.</h2>
            <p class="lg:col-span-5 text-stone text-lg">Each one starts with a real farm problem. Tap “I want this” and the form fills itself in.</p>
        </div>
        <div class="mt-12 grid md:grid-cols-2 gap-5">
            @foreach ($builds as [$key, $title, $problem, $how, $label, $value, $rule, $points])
                @php
                    $pts = collect(explode(',', $points))->map(fn ($y, $x) => ($x * 25).','.$y)->implode(' ');
                @endphp
                <article class="rounded-[26px] bg-white border border-hairline p-6 sm:p-8 grid sm:grid-cols-[1fr_11rem] gap-6">
                    <div>
                        <h3 class="text-xl font-medium">{{ $title }}</h3>
                        <p class="mt-3 text-[15px] leading-relaxed"><span class="text-stone">The problem:</span> {{ $problem }}</p>
                        <p class="mt-2 text-[15px] leading-relaxed"><span class="text-stone">What it does:</span> {{ $how }}</p>
                        <a href="{{ route('site.custom', ['idea' => $title]) }}#ask" class="mt-5 btn-line btn-sm">I want this →</a>
                    </div>
                    {{-- How it looks in Herd Manager --}}
                    <div class="rounded-2xl bg-sand-light border border-hairline p-4 self-start">
                        <div class="text-[10px] uppercase tracking-[0.14em] text-stone">{{ $label }}</div>
                        <div class="font-headline text-3xl mt-1">{{ $value }}</div>
                        <svg viewBox="0 0 200 40" class="w-full h-10 mt-2" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ $pts }}" fill="none" stroke="#B8732E" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/></svg>
                        <div class="mt-2 text-[11px] text-stone">{{ $rule }}</div>
                    </div>
                </article>
            @endforeach
        </div>
        <p class="mt-6 text-[13px] text-stone">Readings shown are examples. Every build is quoted for your farm before anything is made.</p>
    </div>
</section>

{{-- FAQ --}}
<section class="wrap py-24 sm:py-28 grid lg:grid-cols-12 gap-14">
    <div class="lg:col-span-4">
        <h2 class="h-display text-[clamp(2.4rem,4.5vw,3.8rem)]">Before you ask.</h2>
    </div>
    <div class="lg:col-span-8 divide-y divide-hairline border-y border-hairline" x-data="{ open: 0 }">
        @foreach ([
            ['What does a custom build cost?', 'It depends on the sensor, power and signal. You get a fixed quote before we build anything, and you can say no.'],
            ['There\'s no Wi-Fi at the trough.', 'Most builds run on a small solar panel and battery. Where there\'s no Wi-Fi we use a SIM card or a long-range farm radio back to the house.'],
            ['Do I need a KraalTrac Pro?', 'No. Custom devices is part of every Herd Manager account, at no extra cost.'],
            ['Who owns the device and the data?', 'You do. It\'s your device, and you can export every reading any time.'],
            ['What if it breaks?', 'Our builds carry the same 6-month warranty as the KraalTrac Pro. We\'ll help you fix or replace it.'],
        ] as $i => [$q, $a])
            <div>
                <button type="button" @click="open = open === {{ $i }} ? -1 : {{ $i }}" :aria-expanded="open === {{ $i }}" class="w-full flex items-center justify-between gap-6 py-6 text-left">
                    <span class="text-lg sm:text-xl">{{ $q }}</span>
                    <span class="shrink-0 w-9 h-9 rounded-full border border-hairline grid place-items-center transition-transform duration-300" :class="open === {{ $i }} && 'rotate-45 bg-char text-sand border-char'">+</span>
                </button>
                <div x-show="open === {{ $i }}" x-transition.opacity x-cloak><p class="pb-7 -mt-1 max-w-2xl text-stone leading-relaxed">{{ $a }}</p></div>
            </div>
        @endforeach
    </div>
</section>

{{-- Ask --}}
<section id="ask" class="relative overflow-hidden bg-char text-sand scroll-mt-20">
    <img src="{{ Img::url('windmill-red', true) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-35 img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char via-char/85 to-char/40"></div>
    <div class="relative wrap py-24 sm:py-28 grid lg:grid-cols-2 gap-14 items-center">
        <div>
            <h2 class="h-display text-[clamp(2.8rem,6vw,5rem)]">What keeps going wrong on your farm?</h2>
            <p class="mt-6 text-lg text-sand/70 max-w-md">Describe it in your own words. We'll phone or email you within a few working days with ideas. No obligation.</p>
        </div>
        <div class="rounded-[28px] bg-sand text-char p-7 sm:p-10">
            @if (session('suggest_ok'))
                <div class="py-10 text-center"><div class="font-headline text-5xl">Baie dankie!</div><p class="mt-4 text-stone">Got it. We'll be in touch with ideas soon.</p></div>
            @else
                <form method="POST" action="{{ route('site.suggest.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="kind" value="custom_build">
                    <input type="text" name="website" aria-label="Leave empty" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <div><label class="field-label">The problem, in one line *</label><input name="title" value="{{ old('title', request('idea')) }}" required maxlength="160" placeholder="e.g. The trough at the north camp runs dry" class="field"></div>
                    <div><label class="field-label">Tell us more (camp, power, signal, how often)</label><textarea name="details" rows="4" class="field">{{ old('details') }}</textarea></div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="field-label">Name *</label><input name="name" value="{{ old('name', auth()->user()?->name) }}" required class="field"></div>
                        <div><label class="field-label">Email *</label><input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required class="field"></div>
                    </div>
                    @if ($errors->any())<p class="text-sm text-[#B0452F]">{{ $errors->first() }}</p>@endif
                    <button class="btn-dark w-full">Send it</button>
                    <p class="text-xs text-stone">We only use your details to reply. <a href="{{ route('legal.show', 'privacy') }}" class="underline">Privacy</a>.</p>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['windpomp-storm', 'windmill-red']" dark />@endsection
