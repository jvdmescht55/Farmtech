@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'RFID Scanner V1 — Farmtech Winkel')
@section('description', 'Die Farmtech RFID Scanner V1 met Kuddebestuur ingesluit. ISO 11784/11785 FDX-B & HDX. Registreer vir die eerste bondel.')
@section('hero_dark', '1')

@section('content')
<section class="relative h-[92svh] min-h-[620px] overflow-hidden bg-char text-white">
    <img src="{{ Img::url('windpomp-storm') }}" srcset="{{ Img::srcset('windpomp-storm') }}" sizes="100vw" alt="{{ Img::alt('windpomp-storm') }}" class="absolute inset-0 h-full w-full object-cover img-grade" fetchpriority="high">
    <div class="absolute inset-0 bg-gradient-to-t from-char/85 via-char/15 to-char/40"></div>
    <div class="relative wrap h-full flex flex-col justify-end pb-16 sm:pb-24">
        <p class="eyebrow text-white/70">Die Winkel · Eerste bondel</p>
        <h1 class="h-display mt-6 text-[clamp(3.4rem,10vw,9rem)]">RFID Scanner <em class="text-ochre-light">V1.</em></h1>
        <div class="mt-10 flex flex-col lg:flex-row lg:items-end justify-between gap-10">
            <p class="max-w-md text-lg text-white/80 leading-relaxed">Een leser. Een app. Jou hele kudde. Die skandeerder kom met Kuddebestuur ingesluit — geen ekstra intekening om te begin nie.</p>
            <a href="#bestel" class="btn-light">Registreer vir die eerste bondel</a>
        </div>
    </div>
</section>

{{-- What you get --}}
<section class="wrap py-24 sm:py-32">
    <div class="grid lg:grid-cols-12 gap-14">
        <div class="lg:col-span-5">
            <p class="eyebrow">Wat jy kry</p>
            <h2 class="h-display mt-6 text-[clamp(2.6rem,5vw,4.6rem)]">Die leser is net <em>die begin.</em></h2>
            <p class="mt-8 text-lg text-stone leading-relaxed max-w-md">Die meeste lesers lees 'n nommer en los jou daar. Ons s'n gee jou die hele storie — van geboorte tot veiling.</p>
        </div>
        <div class="lg:col-span-7 grid sm:grid-cols-2 gap-px bg-hairline border border-hairline rounded-[24px] overflow-hidden">
            @foreach ([
                ['RFID Scanner V1', 'Lees ISO 11784/11785 FDX-B en HDX oormerke — die standaard vir skape, bokke en beeste.'],
                ['Kuddebestuur-app', 'Kuddeboek, weegsessies, groei per dag, vergelykings, sortering en katalogusse.'],
                ['Aktiveringskode', 'Een kode in die boks. Skep jou rekening en die app is oop. Klaar.'],
                ['Sinchroniseer', 'Laai die sessielêer op, of koppel direk met die leser se eie sinch-sleutel.'],
            ] as [$t, $d])
                <div class="bg-sand p-8 sm:p-10">
                    <h3 class="font-headline text-3xl">{{ $t }}</h3>
                    <p class="mt-3 text-stone leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Split image + software pitch --}}
<section class="grid lg:grid-cols-2 bg-bush text-sand">
    <div class="relative min-h-[420px] lg:min-h-[760px] overflow-hidden">
        <img src="{{ Img::url('sheep-portrait', true) }}" srcset="{{ Img::srcset('sheep-portrait') }}" sizes="(min-width: 1024px) 50vw, 100vw" alt="{{ Img::alt('sheep-portrait') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover img-grade">
    </div>
    <div class="px-6 sm:px-12 lg:px-20 py-20 lg:py-28 flex flex-col justify-center">
        <p class="eyebrow text-sand/50">Kuddebestuur · ingesluit</p>
        <h2 class="h-display mt-6 text-[clamp(2.6rem,4.8vw,4.4rem)]">Alles wat 'n weegskaal <em class="text-ochre-light">jou kan vertel.</em></h2>
        <ul class="mt-10 space-y-5 text-lg">
            @foreach ([
                ['Weegsessies', 'gemiddeld, min/maks, spreiding, en g/dag groei sedert die vorige weging'],
                ['Vergelyk', 'volgens ram, geslag, geboortetipe, ouderdom of status — sien wie werklik presteer'],
                ['Sorteer', 'stel jou snypunte, kry jou groepe, en sien wanneer elke dier markgewig bereik'],
                ['Waarskuwings', 'diere wat gewig verloor of lanklaas geweeg is, word uitgelig'],
                ['Katalogusse', 'veiling-gereed, met lotnommers, stamboom en EBV\'s'],
            ] as [$t, $d])
                <li class="flex gap-5 border-t border-sand/10 pt-5"><span class="w-40 shrink-0 font-medium text-sand">{{ $t }}</span><span class="text-sand/65">{{ $d }}</span></li>
            @endforeach
        </ul>
        <div class="mt-12"><a href="{{ route('login') }}" class="btn-line-light">Klaar 'n kliënt? Teken in</a></div>
    </div>
</section>

{{-- Specs, typographic --}}
<section class="wrap py-24 sm:py-32">
    <p class="eyebrow">Die kort weergawe</p>
    <dl class="mt-10 grid sm:grid-cols-2 lg:grid-cols-4 border-t border-hairline">
        @foreach ([
            ['Standaard', 'ISO 11784 / 11785'],
            ['Oormerke', 'FDX-B · HDX'],
            ['Diere', 'Skape · Bokke · Beeste'],
            ['Sagteware', 'Kuddebestuur ingesluit'],
        ] as [$k, $v])
            <div class="py-8 pr-8 border-b border-hairline sm:[&:nth-child(odd)]:border-r lg:border-r lg:last:border-r-0 sm:pl-0 lg:pl-8 lg:first:pl-0">
                <dt class="kpi-label">{{ $k }}</dt>
                <dd class="mt-3 font-headline text-3xl">{{ $v }}</dd>
            </div>
        @endforeach
    </dl>
</section>

{{-- Register interest --}}
<section id="bestel" class="relative overflow-hidden bg-char text-sand scroll-mt-20">
    <img src="{{ Img::url('dirt-road', true) }}" srcset="{{ Img::srcset('dirt-road') }}" sizes="100vw" alt="{{ Img::alt('dirt-road') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-45 img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char via-char/85 to-char/40"></div>
    <div class="relative wrap py-24 sm:py-32 grid lg:grid-cols-2 gap-16 items-center">
        <div>
            <p class="eyebrow text-sand/50">Eerste bondel</p>
            <h2 class="h-display mt-6 text-[clamp(2.8rem,6vw,5.5rem)]">Wees eerste <em class="text-ochre-light">in die ry.</em></h2>
            <p class="mt-8 text-lg text-sand/70 max-w-md leading-relaxed">Pryse en beskikbaarheid volg binnekort. Los jou besonderhede en ons laat weet jou eerste — met vroeë-voël pryse. Geen spam nie, belowe.</p>
        </div>
        <div class="rounded-[28px] bg-sand text-char p-7 sm:p-10">
            @if (session('lead_ok'))
                <div class="py-10 text-center">
                    <div class="font-headline text-5xl">Baie dankie!</div>
                    <p class="mt-4 text-stone">Jy's op die lys. Ons sal jou kontak sodra die eerste bondel land.</p>
                </div>
            @else
                <form method="POST" action="{{ route('site.interest') }}" class="grid sm:grid-cols-2 gap-4">
                    @csrf
                    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <div class="sm:col-span-2"><label class="field-label" for="i-name">Naam *</label><input id="i-name" name="name" value="{{ old('name') }}" required class="field"></div>
                    <div><label class="field-label" for="i-email">E-pos *</label><input id="i-email" type="email" name="email" value="{{ old('email') }}" required class="field"></div>
                    <div><label class="field-label" for="i-phone">Selfoon</label><input id="i-phone" name="phone" value="{{ old('phone') }}" class="field"></div>
                    <div><label class="field-label" for="i-farm">Plaas / stoet</label><input id="i-farm" name="farm_name" value="{{ old('farm_name') }}" class="field"></div>
                    <div><label class="field-label" for="i-herd">Kuddegrootte</label>
                        <select id="i-herd" name="herd_size" class="field"><option value="">—</option>@foreach (['< 100', '100 – 500', '500 – 2 000', '2 000+'] as $o)<option @selected(old('herd_size') === $o)>{{ $o }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="field-label" for="i-prov">Provinsie</label>
                        <select id="i-prov" name="province" class="field"><option value="">—</option>@foreach ($provinces as $p)<option @selected(old('province') === $p)>{{ $p }}</option>@endforeach</select></div>
                    @if ($errors->any())<p class="sm:col-span-2 text-sm text-[#B0452F]">{{ $errors->first() }}</p>@endif
                    <button class="btn-dark sm:col-span-2 mt-2">Sit my op die lys</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['windpomp-storm', 'sheep-portrait', 'dirt-road']" dark />@endsection
