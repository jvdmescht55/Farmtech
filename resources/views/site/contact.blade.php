@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Kontak — Farmtech')

@section('content')
<section class="grid lg:grid-cols-2 min-h-[100svh]">
    <div class="relative hidden lg:block overflow-hidden bg-char">
        <img src="{{ Img::url('karoo-mist', true) }}" srcset="{{ Img::srcset('karoo-mist') }}" sizes="50vw" alt="{{ Img::alt('karoo-mist') }}" class="absolute inset-0 h-full w-full object-cover img-grade">
    </div>
    <div class="px-5 sm:px-12 lg:px-20 pt-36 pb-24 flex flex-col justify-center">
        <p class="eyebrow">Kontak</p>
        <h1 class="h-display mt-6 text-[clamp(3rem,6vw,5.5rem)]">Howzit. <em>Gesels met ons.</em></h1>
        <p class="mt-6 text-lg text-stone max-w-md">Vrae oor die skandeerder, die app, of jou stamboek-data? Ons antwoord gewoonlik binne een werksdag.</p>

        @if (session('lead_ok'))
            <div class="mt-12 rounded-[24px] bg-white border border-hairline p-10">
                <div class="font-headline text-4xl">Dankie, ons het dit.</div>
                <p class="mt-3 text-stone">Ons kom gou terug na jou toe.</p>
            </div>
        @else
            <form method="POST" action="{{ route('site.contact') }}" class="mt-12 grid sm:grid-cols-2 gap-4 max-w-xl">
                @csrf
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                <div class="sm:col-span-2"><label class="field-label" for="c-name">Naam *</label><input id="c-name" name="name" value="{{ old('name') }}" required class="field"></div>
                <div><label class="field-label" for="c-email">E-pos *</label><input id="c-email" type="email" name="email" value="{{ old('email') }}" required class="field"></div>
                <div><label class="field-label" for="c-phone">Selfoon</label><input id="c-phone" name="phone" value="{{ old('phone') }}" class="field"></div>
                <div class="sm:col-span-2"><label class="field-label" for="c-msg">Boodskap *</label><textarea id="c-msg" name="message" rows="5" required class="field">{{ old('message') }}</textarea></div>
                @if ($errors->any())<p class="sm:col-span-2 text-sm text-[#B0452F]">{{ $errors->first() }}</p>@endif
                <button class="btn-dark sm:col-span-2 sm:justify-self-start mt-2">Stuur</button>
            </form>
        @endif
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['karoo-mist']" dark />@endsection
