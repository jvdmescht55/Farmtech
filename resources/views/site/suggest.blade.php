@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Made for your farm — Farmtech')

@section('content')
<section class="grid lg:grid-cols-2 min-h-[100svh]">
    <div class="relative hidden lg:block overflow-hidden bg-char">
        <img src="{{ Img::url('golden-valley', true) }}" srcset="{{ Img::srcset('golden-valley') }}" sizes="50vw" alt="{{ Img::alt('golden-valley') }}" class="absolute inset-0 h-full w-full object-cover img-grade">
        <div class="absolute inset-0 bg-gradient-to-t from-char/80 to-transparent"></div>
        <div class="absolute bottom-12 left-12 right-12 text-sand">
            <h2 class="h-display text-6xl">Boer maak 'n plan.<br><em class="text-ochre-light">We help build it.</em></h2>
        </div>
    </div>
    <div class="px-5 sm:px-12 lg:px-20 pt-32 pb-24 flex flex-col justify-center" x-data="{ kind: '{{ old('kind', request('kind', 'device')) }}' }">
        <p class="eyebrow">Made for your farm</p>
        <h1 class="h-display mt-5 text-[clamp(3rem,6vw,5rem)]">What should we build <em>for you?</em></h1>
        <p class="mt-5 text-lg text-stone max-w-md">Every device and every feature in Herd Manager started with a farmer saying "I wish…". Tell us yours.</p>
        @if (session('suggest_ok'))
            <div class="mt-10 rounded-[24px] bg-white border border-hairline p-10"><div class="font-headline text-4xl">Lekker, thanks!</div><p class="mt-3 text-stone">We read every one and we'll get back to you.</p></div>
        @else
            <form method="POST" action="{{ route('site.suggest.store') }}" class="mt-10 space-y-4 max-w-xl">
                @csrf
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                <div class="grid gap-2">
                    @foreach (\App\Models\Suggestion::KINDS as $k => $l)
                        <label class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer transition bg-white" :class="kind === '{{ $k }}' ? 'border-char' : 'border-hairline'"><input type="radio" name="kind" value="{{ $k }}" x-model="kind" class="text-char"> {{ $l }}</label>
                    @endforeach
                </div>
                <div><label class="field-label">In one line *</label><input name="title" value="{{ old('title') }}" required maxlength="160" placeholder="e.g. A sensor that tells me when the trough runs dry" class="field"></div>
                <div><label class="field-label">Tell us more</label><textarea name="details" rows="4" class="field">{{ old('details') }}</textarea></div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="field-label">Name *</label><input name="name" value="{{ old('name') }}" required class="field"></div>
                    <div><label class="field-label">Email *</label><input type="email" name="email" value="{{ old('email') }}" required class="field"></div>
                </div>
                @if ($errors->any())<p class="text-sm text-[#B0452F]">{{ $errors->first() }}</p>@endif
                <button class="btn-dark">Send it</button>
                <p class="text-xs text-stone">We'll only use your details to reply. <a href="{{ route('legal.show', 'privacy') }}" class="underline">Privacy</a>.</p>
            </form>
        @endif
    </div>
</section>
@endsection
@section('credits')<x-photo-credits :keys="['golden-valley']" dark />@endsection
