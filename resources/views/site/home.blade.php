@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Farmtech — Ken elke dier. Elke kilo.')
@section('hero_dark', '1')
@push('head')<link rel="preload" as="image" href="{{ Img::url('windpomp-pink') }}" imagesrcset="{{ Img::srcset('windpomp-pink') }}" imagesizes="100vw">@endpush

@section('content')
{{-- 1 · Hero --}}
<section class="relative h-[100svh] min-h-[640px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('windpomp-pink') }}" srcset="{{ Img::srcset('windpomp-pink') }}" sizes="100vw" alt="{{ Img::alt('windpomp-pink') }}"
         class="absolute inset-0 h-full w-full object-cover object-[50%_65%] img-grade scale-[1.03]" fetchpriority="high">
    <div class="absolute inset-0 bg-gradient-to-t from-char/85 via-char/20 to-char/35"></div>

    <div class="relative wrap h-full flex flex-col justify-end pb-16 sm:pb-24">
        <p class="eyebrow text-white/70 reveal-up">RFID Scanner V1 &nbsp;·&nbsp; Kuddebestuur</p>
        <h1 class="h-display mt-6 text-[clamp(3.6rem,11vw,10rem)] reveal-up" style="animation-delay:.08s">
            Ken elke dier.<br><em class="text-ochre-light">Elke kilo.</em>
        </h1>
        <div class="mt-10 flex flex-col lg:flex-row lg:items-end justify-between gap-10 reveal-up" style="animation-delay:.18s">
            <p class="max-w-md text-lg text-white/80 leading-relaxed">Skandeer in die kraal en jou kuddeboek werk homself by — gewigte, groei, stamboom en veilingkatalogusse. Sonder die notaboekie in die bakkie.</p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('site.store') }}" class="btn-light">Kry die skandeerder</a>
                <a href="{{ route('login') }}" class="btn-line-light">Teken in by Kuddebestuur</a>
            </div>
        </div>
    </div>
</section>

{{-- 2 · The two doors --}}
<section class="wrap py-24 sm:py-32">
    <div class="flex items-end justify-between gap-6 mb-12">
        <h2 class="h-display text-[clamp(2.6rem,6vw,5rem)]">Waarheen, <em>boer?</em></h2>
        <p class="hidden md:block max-w-xs text-stone">Koop die gereedskap, of spring reg in by jou kudde. Albei is net een klik weg.</p>
    </div>
    <div class="grid md:grid-cols-2 gap-5">
        @foreach ([
            ['site.store', 'koppie-dawn', '01', 'Winkel', 'Die Skandeerder', 'RFID Scanner V1 — gebou vir stof, reën en ramme wat skop.', 'Gaan kyk'],
            ['login', 'windpomp-storm', '02', 'Kuddebestuur', 'Jou kudde, aanlyn', 'Gewigte, groeikurwes, vergelykings en katalogusse. Alles uit een skandering.', 'Teken in'],
        ] as [$route, $img, $n, $eyebrow, $title, $body, $cta])
            <a href="{{ route($route) }}" class="group relative block aspect-[4/5] sm:aspect-[5/6] lg:aspect-[4/5] overflow-hidden rounded-[28px] bg-char text-white">
                <img src="{{ Img::url($img, true) }}" srcset="{{ Img::srcset($img) }}" sizes="(min-width: 768px) 50vw, 100vw" alt="{{ Img::alt($img) }}" loading="lazy"
                     class="absolute inset-0 h-full w-full object-cover img-grade transition-transform duration-[1.4s] ease-[cubic-bezier(.2,.7,.2,1)] group-hover:scale-[1.06]">
                <div class="absolute inset-0 bg-gradient-to-t from-char/85 via-char/10 to-transparent"></div>
                <div class="absolute top-7 left-7 right-7 flex justify-between eyebrow text-white/80"><span>{{ $eyebrow }}</span><span class="font-num">{{ $n }}</span></div>
                <div class="absolute bottom-0 inset-x-0 p-7 sm:p-10">
                    <h3 class="h-display text-5xl sm:text-6xl">{{ $title }}</h3>
                    <p class="mt-4 max-w-sm text-white/75">{{ $body }}</p>
                    <span class="mt-8 inline-flex items-center gap-3 text-[15px] font-medium">
                        <span class="grid place-items-center w-11 h-11 rounded-full bg-white text-char transition-transform duration-500 group-hover:translate-x-1">→</span>{{ $cta }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</section>

{{-- 3 · Statement --}}
<section class="border-y border-hairline">
    <div class="wrap py-28 sm:py-40">
        <p class="eyebrow mb-10">Die probleem</p>
        <p class="h-display text-[clamp(2.2rem,5.2vw,4.6rem)] leading-[1.05] max-w-[18ch] sm:max-w-[22ch]">
            Kryt op die rug. Notaboekies in die bakkie. <span class="text-stone-light">Raaiwerk by die skaal.</span> <em class="text-ochre">Ag nee, man.</em>
        </p>
        <p class="mt-10 max-w-xl text-lg text-stone leading-relaxed">Farmtech maak van elke skandering 'n rekord. Die leser lees die oormerk, die skaal gee die gewig, en Kuddebestuur doen die somme — groei per dag, wie vorentoe loop, en wie agter raak.</p>
    </div>
</section>

{{-- 4 · Features --}}
<section class="wrap py-24 sm:py-32">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-x-10 gap-y-16">
        @foreach ([
            ['01', 'Skandeer', 'ISO 11784/11785 FDX-B & HDX. Stick die leser by die oor — die dier se hele geskiedenis is daar.'],
            ['02', 'Weeg', 'Elke weegsessie opgesom: gemiddeld, spreiding, g/dag groei, en wie gewig verloor het.'],
            ['03', 'Vergelyk', 'Ramme teen ramme. Tweelinge teen enkelinge. Hierdie seisoen teen verlede seisoen.'],
            ['04', 'Verkoop', 'Veilingkatalogusse met lotnommers (66A, 66B…) en SP/C-status uit die stamboom. Druk en klaar.'],
        ] as [$n, $t, $d])
            <div>
                <div class="font-num text-sm text-stone-light">{{ $n }}</div>
                <div class="mt-5 h-px bg-hairline"></div>
                <h3 class="mt-6 font-headline text-4xl">{{ $t }}</h3>
                <p class="mt-3 text-stone leading-relaxed">{{ $d }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- 5 · App preview --}}
<section class="bg-bush text-sand overflow-hidden">
    <div class="wrap py-24 sm:py-32 grid lg:grid-cols-12 gap-14 items-center">
        <div class="lg:col-span-5">
            <p class="eyebrow text-sand/50">Kuddebestuur</p>
            <h2 class="h-display mt-6 text-[clamp(2.6rem,5.5vw,4.8rem)]">Lekker data.<br><em class="text-ochre-light">Nie net syfers nie.</em></h2>
            <p class="mt-8 text-sand/70 text-lg leading-relaxed max-w-md">Sien in een oogopslag hoe jou kudde groei, watter ram se lammers die beste doen, en wie klaar is vir die mark. Sorteer op gewig, vergelyk sessies, en druk jou veilingkatalogus.</p>
            <a href="{{ route('site.store') }}" class="btn-light mt-10">Sien wat dit kan doen</a>
        </div>
        <div class="lg:col-span-7">
            <div class="rounded-[24px] bg-sand text-char p-2 shadow-[0_40px_120px_-30px_rgba(0,0,0,.6)] rotate-[-1.2deg]">
                <div class="rounded-[18px] bg-white border border-hairline overflow-hidden">
                    <div class="flex items-center gap-2 px-5 h-11 border-b border-hairline text-[12px] text-stone">
                        <span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span>
                        <span class="ml-3">Weegsessie · 28 Sep</span>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-hairline border-b border-hairline">
                        @foreach ([['Gemiddeld', '42.8', 'kg'], ['Groei', '+214', 'g/dag'], ['Geweeg', '186', 'diere']] as [$l, $v, $u])
                            <div class="p-5"><div class="kpi-label !text-[10px]">{{ $l }}</div><div class="mt-2 font-headline text-4xl">{{ $v }}<span class="text-base text-stone ml-1 font-ui">{{ $u }}</span></div></div>
                        @endforeach
                    </div>
                    <div class="p-5 grid sm:grid-cols-2 gap-6">
                        <div>
                            <div class="kpi-label !text-[10px] mb-3">Gemiddelde gewig · 6 sessies</div>
                            <svg viewBox="0 0 300 110" class="w-full h-28" preserveAspectRatio="none" aria-hidden="true">
                                <defs><linearGradient id="g1" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#B8732E" stop-opacity=".25"/><stop offset="1" stop-color="#B8732E" stop-opacity="0"/></linearGradient></defs>
                                <path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22 L300,110 L0,110Z" fill="url(#g1)"/>
                                <path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22" fill="none" stroke="#B8732E" stroke-width="2.5"/>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label !text-[10px] mb-3">Verspreiding (kg)</div>
                            <div class="flex items-end gap-1.5 h-28">
                                @foreach ([12, 28, 46, 78, 100, 84, 58, 30, 14] as $h)<div class="flex-1 rounded-t bg-char/85" style="height: {{ $h }}%"></div>@endforeach
                            </div>
                        </div>
                    </div>
                    <div class="px-5 pb-5 grid grid-cols-3 gap-2 text-[12px]">
                        <div class="rounded-lg bg-sand-light px-3 py-2"><span class="text-stone">&lt; 38 kg</span><div class="font-medium">41 diere</div></div>
                        <div class="rounded-lg bg-sand-light px-3 py-2"><span class="text-stone">38–46 kg</span><div class="font-medium">97 diere</div></div>
                        <div class="rounded-lg bg-ochre/10 px-3 py-2"><span class="text-ochre-dark">&gt; 46 kg · mark</span><div class="font-medium">48 diere</div></div>
                    </div>
                </div>
            </div>
            <p class="mt-6 text-[13px] text-sand/40">Illustrasie van die app met voorbeelddata.</p>
        </div>
    </div>
</section>

{{-- 6 · Image band --}}
<section class="relative h-[80svh] min-h-[520px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('merino-rams', true) }}" srcset="{{ Img::srcset('merino-rams') }}" sizes="100vw" alt="{{ Img::alt('merino-rams') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char/75 via-char/25 to-transparent"></div>
    <div class="relative wrap h-full flex items-center">
        <div class="max-w-xl">
            <h2 class="h-display text-[clamp(3rem,7vw,6.5rem)]">Van die kraal<br><em class="text-ochre-light">tot die veiling.</em></h2>
            <p class="mt-6 text-lg text-white/75 max-w-md">Stamboom, SP/C-status en EBV's reg langs die gewig. Jou beste ramme verkoop hulself.</p>
        </div>
    </div>
</section>

{{-- 7 · How it works --}}
<section id="hoe" class="wrap py-24 sm:py-32">
    <div class="grid lg:grid-cols-12 gap-14">
        <div class="lg:col-span-4">
            <p class="eyebrow">Hoe dit werk</p>
            <h2 class="h-display mt-6 text-[clamp(2.6rem,5vw,4.4rem)]">Drie stappe. <em>Sommer so.</em></h2>
        </div>
        <ol class="lg:col-span-8 divide-y divide-hairline border-y border-hairline">
            @foreach ([
                ['Kry jou skandeerder', 'Bestel die RFID Scanner V1. In die boks is \'n aktiveringskode net vir jou.'],
                ['Aktiveer Kuddebestuur', 'Skep jou rekening met die kode. Laai jou stamregister op — of begin net skandeer.'],
                ['Skandeer. Weeg. Klaar.', 'Elke sessie sinchroniseer. Jy sien die somme; jy maak die besluite. Boer maak \'n plan.'],
            ] as $i => [$t, $d])
                <li class="grid sm:grid-cols-[5rem_1fr] gap-4 py-10">
                    <span class="font-headline text-5xl text-ochre">{{ $i + 1 }}</span>
                    <div><h3 class="text-2xl font-medium">{{ $t }}</h3><p class="mt-2 text-stone leading-relaxed max-w-lg">{{ $d }}</p></div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- 8 · FAQ --}}
<section class="bg-sand-deep/60 border-y border-hairline">
    <div class="wrap py-24 sm:py-32 grid lg:grid-cols-12 gap-14">
        <div class="lg:col-span-4">
            <p class="eyebrow">Vrae</p>
            <h2 class="h-display mt-6 text-[clamp(2.4rem,4.5vw,4rem)]">Ja-nee, <em>ons weet.</em></h2>
        </div>
        <div class="lg:col-span-8 divide-y divide-hairline border-y border-hairline" x-data="{ open: 0 }">
            @foreach ([
                ['Werk dit met my bestaande oormerke?', 'As jou oormerke ISO 11784/11785 (FDX-B of HDX) is — die standaard vir vee-oormerke in Suid-Afrika — lees die skandeerder hulle.'],
                ['Kan ek my Logix/stamboek-data inbring?', 'Ja. Laai \'n CSV op met ID\'s, ouers, grootouers en EBV\'s. Kuddebestuur bou die stamboom en werk SP/C/B-status self uit.'],
                ['Wat as ek geen sein in die kraal het nie?', 'Die leser hou die sessie. Sinchroniseer later wanneer jy terug by die huis is — of laai die sessielêer op.'],
                ['Wanneer kan ek bestel?', 'Die eerste bondel is op pad. Registreer jou belangstelling en jy hoor eerste — met vroeë-voël pryse.'],
            ] as $i => [$q, $a])
                <div>
                    <button type="button" @click="open = open === {{ $i }} ? -1 : {{ $i }}" class="w-full flex items-center justify-between gap-6 py-7 text-left">
                        <span class="text-xl">{{ $q }}</span>
                        <span class="shrink-0 w-9 h-9 rounded-full border border-hairline grid place-items-center transition-transform duration-300" :class="open === {{ $i }} && 'rotate-45 bg-char text-sand border-char'">+</span>
                    </button>
                    <div x-show="open === {{ $i }}" x-transition.opacity.duration.300ms x-cloak>
                        <p class="pb-8 -mt-2 max-w-2xl text-stone leading-relaxed">{{ $a }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- 9 · CTA --}}
<section class="relative overflow-hidden bg-char text-white">
    <img src="{{ Img::url('golden-valley', true) }}" srcset="{{ Img::srcset('golden-valley') }}" sizes="100vw" alt="{{ Img::alt('golden-valley') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-70 img-grade">
    <div class="absolute inset-0 bg-char/40"></div>
    <div class="relative wrap py-32 sm:py-44 text-center">
        <h2 class="h-display text-[clamp(3rem,8vw,7.5rem)]">Moenie worry nie.<br><em class="text-ochre-light">Ons help jou op dreef.</em></h2>
        <div class="mt-12 flex flex-wrap justify-center gap-3">
            <a href="{{ route('site.store') }}#bestel" class="btn-light">Bestel vroeg</a>
            <a href="{{ route('site.contact') }}" class="btn-line-light">Gesels met ons</a>
        </div>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['windpomp-pink', 'koppie-dawn', 'windpomp-storm', 'merino-rams', 'golden-valley']" dark />@endsection
