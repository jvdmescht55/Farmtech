@extends('layouts.site')
@php use App\Support\SiteImages as Img; @endphp
@section('title', 'Contact — Farmtech')

@section('content')
<section class="grid lg:grid-cols-2 min-h-[100svh]">
    <div class="relative hidden lg:block overflow-hidden bg-char">
        <img src="{{ Img::url('karoo-mist', true) }}" srcset="{{ Img::srcset('karoo-mist') }}" sizes="50vw" alt="{{ Img::alt('karoo-mist') }}" class="absolute inset-0 h-full w-full object-cover img-grade">
    </div>
    <div class="px-5 sm:px-12 lg:px-20 pt-36 pb-24 flex flex-col justify-center">
        <p class="eyebrow">Contact</p>
        <h1 class="h-display mt-6 text-[clamp(3rem,6vw,5.5rem)]">Howzit. <em>Let's chat.</em></h1>
        <p class="mt-6 text-lg text-stone max-w-md">Questions about a device, the software, or getting your stud records in? We usually reply within one working day.</p>

        @if (session('lead_ok'))
            <div class="mt-12 rounded-[24px] bg-white border border-hairline p-10">
                <div class="font-headline text-4xl">Baie dankie — got it.</div>
                <p class="mt-3 text-stone">We'll get back to you soon.</p>
            </div>
        @else
            <form method="POST" action="{{ route('site.contact') }}" class="mt-12 grid sm:grid-cols-2 gap-4 max-w-xl">
                @csrf
                <input type="text" name="website" aria-label="Leave empty" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                <div class="sm:col-span-2"><label class="field-label" for="c-name">Name *</label><input id="c-name" name="name" value="{{ old('name') }}" required class="field"></div>
                <div><label class="field-label" for="c-email">Email *</label><input id="c-email" type="email" name="email" value="{{ old('email') }}" required class="field"></div>
                <div><label class="field-label" for="c-phone">Cellphone</label><input id="c-phone" name="phone" value="{{ old('phone') }}" class="field"></div>
                <div class="sm:col-span-2"><label class="field-label" for="c-msg">Message *</label><textarea id="c-msg" name="message" rows="5" required class="field">{{ old('message') }}</textarea></div>
                @if ($errors->any())<p class="sm:col-span-2 text-sm text-[#B0452F]">{{ $errors->first() }}</p>@endif
                <label class="sm:col-span-2 flex gap-3 text-sm text-stone"><input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-hairline text-char"> <span>You may use these details to reply to me. <a href="{{ route('legal.show', 'privacy') }}" class="underline text-char" target="_blank">Privacy policy</a>.</span></label>
                <button class="btn-dark sm:col-span-2 sm:justify-self-start mt-2">Send</button>
            </form>
        @endif
        <div class="mt-12 text-sm text-stone space-y-1">
            <div>{{ config('legal.email') }}@if (config('legal.phone')) · {{ config('legal.phone') }}@endif</div>
            <div><a href="{{ route('site.suggest') }}" class="link-u text-char">Got an idea for a device?</a></div>
        </div>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['karoo-mist']" dark />@endsection
