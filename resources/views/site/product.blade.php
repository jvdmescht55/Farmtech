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
            <div class="relative {{ $photos ? 'aspect-[4/3]' : 'aspect-[4/3] sm:aspect-[5/4]' }} rounded-[28px] overflow-hidden bg-char min-h-[420px]">
                @forelse ($photos as $n => $p)
                    <img x-show="i === {{ $n }}" src="{{ asset('storage/'.$p) }}" alt="{{ $listing->name }}" class="absolute inset-0 h-full w-full object-cover" @if($n) x-cloak @endif>
                @empty
                    @if ($listing->module === 'rfid')
                        <div class="absolute inset-0 bg-gradient-to-b from-sand-deep to-sand flex items-center justify-center p-6">@include('site._device')</div>
                    @else
                        <img src="{{ Img::url('windpomp-storm', true) }}" alt="{{ Img::alt('windpomp-storm') }}" class="absolute inset-0 h-full w-full object-cover img-grade">
                        <div class="absolute inset-0 bg-gradient-to-t from-char/70 to-transparent"></div>
                        <span class="absolute bottom-5 left-5 chip bg-sand/90 text-char">Product photos coming soon</span>
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
            <div class="mt-8 flex flex-wrap items-baseline gap-4">
                @if ($listing->priceLabel())<span class="font-headline text-5xl">{{ $listing->priceLabel() }}</span><span class="text-sm text-stone">incl. VAT where applicable · delivery confirmed with your order</span>@endif
            </div>
            @if ($listing->availability)<p class="mt-3 inline-flex items-center gap-2 text-sm"><span class="w-2 h-2 rounded-full bg-[#3F7A3A]"></span>{{ $listing->availability }}</p>@endif
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#order" class="btn-dark">{{ $listing->price_cents ? 'Order yours' : 'Register interest' }}</a>
                <a href="{{ route('site.contact') }}" class="btn-line">Ask us something</a>
            </div>
            <ul class="mt-8 space-y-2 text-sm text-stone">
                <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-ochre shrink-0"></span>Herd Manager included, no monthly subscription</li>
                <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-ochre shrink-0"></span>7 days to change your mind · 6-month warranty</li>
                <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-ochre shrink-0"></span>Help from the people who built it</li>
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

@include('site._order', ['heading' => $listing->price_cents ? 'Order your '.$listing->name : 'Be first for the '.$listing->name, 'listing' => $listing])
@endsection

@section('credits')<x-photo-credits :keys="[$listing->module === 'watch' ? 'windpomp-storm' : 'merino-rams', 'dirt-road']" dark />@endsection
