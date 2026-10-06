@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', $listing->name.' — '.($listing->tagline ?: 'Farmtech'))
@section('description', \Illuminate\Support\Str::limit(strip_tags($listing->overview), 155))

@section('content')
@unless ($listing->is_published)
    <div class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 rounded-full bg-ochre text-char px-5 py-2 text-sm font-medium shadow-lg">Draft preview. Not visible to the public yet</div>
@endunless
<section class="pt-28 sm:pt-32 pb-16 wrap">
    <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">
        <div x-data="{ i: 0 }">
            @php($photos = $listing->images ?: [])
            <div class="relative {{ $photos || $listing->module !== 'rfid' ? 'aspect-[4/3]' : 'h-[480px] sm:h-auto sm:aspect-[5/4]' }} rounded-[28px] overflow-hidden bg-char">
                @forelse ($photos as $n => $p)
                    <img x-show="i === {{ $n }}" src="{{ asset('storage/'.$p) }}" alt="{{ $listing->name }}" class="absolute inset-0 h-full w-full object-cover" @if($n) x-cloak @endif>
                @empty
                    @if ($listing->module === 'rfid')
                        <div class="absolute inset-0 bg-gradient-to-b from-sand-deep to-sand flex items-center justify-center p-6">@include('site._device')</div>
                    @else
                        <img src="{{ $listing->photoUrl(false) ?? Img::url('windpomp-storm', true) }}" alt="{{ $listing->name }}" class="absolute inset-0 h-full w-full object-cover {{ $listing->photo_key === 'ear-tags' ? '' : 'img-grade' }}">
                        @if ($listing->photo_key !== 'ear-tags')<div class="absolute inset-0 bg-gradient-to-t from-char/60 to-transparent"></div>
                        <span class="absolute bottom-5 left-5 chip bg-sand/90 text-char">Product photos coming soon</span>@endif
                    @endif
                @endforelse
            </div>
            @if (count($photos) > 1)
                <div class="mt-3 flex gap-2 overflow-x-auto scrollbar-none">
                    @foreach ($photos as $n => $p)
                        <button type="button" @click="i = {{ $n }}" class="shrink-0 w-20 h-16 rounded-xl overflow-hidden border-2" :class="i === {{ $n }} ? 'border-char' : 'border-transparent'"><img src="{{ asset('storage/'.$p) }}" alt="" class="h-full w-full object-cover"></button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lg:sticky lg:top-28">
            <h1 class="h-display text-[clamp(3rem,7vw,5.5rem)]">{{ $listing->name }}</h1>
            @if ($listing->tagline)<p class="mt-3 text-xl text-stone">{{ $listing->tagline }}</p>@endif
            <div class="mt-8 flex flex-wrap items-baseline gap-3">
                @if ($listing->priceLabel())
                    <span class="font-headline text-5xl">{{ $listing->priceLabel() }}</span>
                    @if ($listing->compare_at_cents)<span class="text-stone line-through">{{ \App\Models\StoreListing::rand($listing->compare_at_cents) }}</span>@endif
                    <span class="text-sm text-stone">{{ $listing->unit_label ? $listing->unit_label.' · ' : '' }}incl. VAT</span>
                @else
                    <span class="font-headline text-3xl">Price confirmed before it ships</span>
                @endif
            </div>
            <div class="mt-5 rounded-2xl border {{ $listing->buyable() ? 'border-[#3F7A3A]/30 bg-[#3F7A3A]/5' : 'border-ochre/40 bg-ochre/5' }} p-4 text-sm">
                <div class="flex items-center gap-2 font-medium"><span class="w-2 h-2 rounded-full {{ $listing->buyable() ? 'bg-[#3F7A3A]' : 'bg-ochre' }}"></span>{{ $listing->stockLabel() }}</div>
                @if ($listing->buyable())
                    <p class="mt-1 text-stone">{{ $listing->availability ?: 'Ships within a few working days.' }}</p>
                @else
                    <p class="mt-1 text-stone">{{ $listing->next_batch ?: 'Reserve one now. You pay only when your unit is ready, and you can cancel before then.' }}</p>
                @endif
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                @include('shop._buy', ['l' => $listing, 'qty' => true, 'class' => 'px-8'])
                <a href="{{ route('site.contact') }}" class="btn-line">Ask us something</a>
            </div>
            <ul class="mt-8 space-y-2 text-sm text-stone">
                @foreach ($listing->module
                    ? ['Herd Manager included, no monthly subscription', 'Programmed and tested in South Africa before it ships', '7 days to change your mind · 6-month warranty', 'Help from the people who built it']
                    : ['Reads on the KraalTrac Pro and shows up in Herd Manager', 'Couriered with your order, or collect', '7 days to change your mind (unused packs)'] as $t)
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-ochre shrink-0"></span>{{ $t }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

@if ($listing->overview)
<section class="border-y border-hairline">
    <div class="wrap py-20 sm:py-28 grid lg:grid-cols-12 gap-10">
        <p class="lg:col-span-3 eyebrow">Overview</p>
        <div class="lg:col-span-8 space-y-6 text-[clamp(1.25rem,2.2vw,1.6rem)] leading-[1.5] text-char">
            @foreach (preg_split('/\R{2,}/', $listing->overview) as $para)<p>{{ $para }}</p>@endforeach
        </div>
    </div>
</section>
@endif

{{-- How it works / programmed your way (scanner) and how to tag (tags) --}}
@if ($listing->module === 'rfid')
<section class="bg-char text-sand">
    <div class="wrap py-20 sm:py-28">
        <h2 class="h-display text-[clamp(2.6rem,5vw,4.2rem)]">How it works</h2>
        <ol class="mt-12 grid md:grid-cols-2 xl:grid-cols-4 gap-5">
            @foreach ([
                ['Scan', 'Hold the antenna end to the ear tag. The animal\'s number comes up on the screen. New animal? It offers the next birthday number.'],
                ['Weigh', 'Press A–D for birth, wean, post-wean or mature weight, type the weight off your scale and press #.'],
                ['Saved, even offline', 'The record is stored on the scanner straight away (up to 300), so a dead zone in the kraal doesn\'t matter.'],
                ['Synced to Herd Manager', 'On Wi-Fi or your phone\'s hotspot it sends everything to farmtech.site. Gains, alerts and auction books update by themselves.'],
            ] as $i => [$t, $d])
                <li class="rounded-[24px] bg-white/5 border border-white/10 p-6">
                    <span class="font-headline text-4xl text-ochre-light">{{ $i + 1 }}</span>
                    <h3 class="mt-4 text-xl font-medium">{{ $t }}</h3>
                    <p class="mt-2 text-sand/70 leading-relaxed">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<section class="wrap py-20 sm:py-28 grid lg:grid-cols-2 gap-12 items-center">
    <div>
        <h2 class="h-display text-[clamp(2.6rem,5vw,4.2rem)]">Programmed the way you farm.</h2>
        <p class="mt-6 text-lg text-stone leading-relaxed max-w-lg">Every KraalTrac Pro is set up for its farm before it leaves us. When you order, we phone you and ask how you work. Then we program it to match.</p>
        <p class="mt-4 text-stone max-w-lg">Want a change after a season? Tell us, and we send the update. You install it from your browser in a minute, free.</p>
        <a href="{{ route('site.contact') }}" class="btn-line mt-8">Ask us what's possible</a>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
        @foreach ([
            ['Your ID system', 'Birthday numbers (250912), stud numbers, or your own format.'],
            ['Your questions', 'Only the weigh types and fields you use: sex, sire, dam, or add FAMACHA, condition score, camp.'],
            ['Your animals', 'Screens that say cattle, goat or sheep, with the weigh types you use for each.'],
            ['Your language', 'English or Afrikaans screens.'],
            ['Your Wi-Fi', 'Farm networks and your phone\'s hotspot loaded before delivery.'],
            ['Your flock', 'Your existing tag list loaded, so it knows your animals on day one.'],
        ] as [$t, $d])
            <div class="rounded-2xl bg-white border border-hairline p-5"><div class="font-medium">{{ $t }}</div><p class="mt-1 text-sm text-stone">{{ $d }}</p></div>
        @endforeach
    </div>
</section>
@elseif ($listing->category === 'Tags')
<section class="bg-char text-sand">
    <div class="wrap py-20 sm:py-24">
        <h2 class="h-display text-[clamp(2.6rem,5vw,4.2rem)]">How to tag</h2>
        <ol class="mt-12 grid md:grid-cols-3 gap-5">
            @foreach ([
                ['Load the applicator', 'Male pin on the spike, female button in the jaw of a standard button-tag applicator.'],
                ['Tag the ear', 'Between the two main ribs of the ear, about a third of the way out from the head. One firm squeeze.'],
                ['Scan it once', 'Scan with the KraalTrac Pro and give the animal its number. From then on a scan brings up its full record.'],
            ] as $i => [$t, $d])
                <li class="rounded-[24px] bg-white/5 border border-white/10 p-6"><span class="font-headline text-4xl text-ochre-light">{{ $i + 1 }}</span><h3 class="mt-4 text-xl font-medium">{{ $t }}</h3><p class="mt-2 text-sand/70 leading-relaxed">{{ $d }}</p></li>
            @endforeach
        </ol>
        <p class="mt-8 text-sm text-sand/50">Clean the applicator between animals. Tag in cool parts of the day and avoid fly season where you can.</p>
    </div>
</section>
@endif

@if ($listing->features)
<section class="wrap py-20 sm:py-28">
    <p class="eyebrow">Why farmers want it</p>
    <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-10 gap-y-14">
        @foreach ($listing->features as $i => $f)
            <div>
                <div class="font-num text-sm text-stone-light">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                <div class="mt-5 h-px bg-hairline"></div>
                <h3 class="mt-6 font-headline text-3xl">{{ $f['title'] }}</h3>
                @if ($f['body'])<p class="mt-3 text-stone leading-relaxed">{{ $f['body'] }}</p>@endif
            </div>
        @endforeach
    </div>
</section>
@endif

<section class="bg-white border-y border-hairline">
    <div class="wrap py-20 sm:py-28 grid lg:grid-cols-12 gap-12">
        @if ($listing->specs)
            <div class="lg:col-span-7">
                <p class="eyebrow">Specifications</p>
                <dl class="mt-8 divide-y divide-hairline border-y border-hairline">
                    @foreach ($listing->specs as $s)
                        <div class="grid sm:grid-cols-[14rem_1fr] gap-2 py-4"><dt class="text-stone">{{ $s['label'] }}</dt><dd>{{ $s['value'] }}</dd></div>
                    @endforeach
                </dl>
            </div>
        @endif
        <div class="lg:col-span-4 lg:col-start-9 space-y-10">
            @if ($listing->in_box)
                <div>
                    <p class="eyebrow">In the box</p>
                    <ul class="mt-6 space-y-3">@foreach ($listing->in_box as $item)<li class="flex gap-3"><span class="text-ochre">—</span>{{ $item }}</li>@endforeach</ul>
                </div>
            @endif
            @if ($listing->why)
                <div class="rounded-[24px] bg-sand p-7">
                    <p class="eyebrow">Why KraalTrac</p>
                    <p class="mt-4 leading-relaxed">{{ $listing->why }}</p>
                </div>
            @endif
        </div>
    </div>
</section>

@php($others = \App\Models\StoreListing::published()->where('id', '!=', $listing->id)->get())
@if ($others->isNotEmpty())
<section class="wrap py-20 sm:py-28">
    <h2 class="h-display text-[clamp(2.4rem,5vw,4rem)]">Goes well with</h2>
    <div class="mt-10 grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach ($others as $o)@include('shop._card', ['l' => $o])@endforeach
    </div>
</section>
@endif

{{-- Sticky buy bar on phones --}}
<div class="lg:hidden fixed inset-x-0 bottom-0 z-30 bg-sand/95 backdrop-blur border-t border-hairline px-4 py-3 flex items-center gap-3" style="padding-bottom: max(.75rem, env(safe-area-inset-bottom))">
    <div class="flex-1 min-w-0"><div class="text-sm truncate">{{ $listing->name }}</div><div class="font-headline text-xl">{{ $listing->priceLabel() ?? 'Price TBC' }}</div></div>
    @include('shop._buy', ['l' => $listing])
</div>
<div class="lg:hidden h-20"></div>
@endsection

@section('credits')<x-photo-credits :keys="array_filter([$listing->photo_key, 'karoo-mist'])" dark />@endsection
