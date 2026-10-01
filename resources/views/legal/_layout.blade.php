@extends('layouts.site')
@section('title', $title.' — Farmtech')
@php
    $l = config('legal');
    $v = fn ($key, $hint = null) => filled($l[$key] ?? null) ? e($l[$key]) : '<mark class="bg-ochre/20 text-ochre-dark px-1 rounded">['.e($hint ?? 'to be completed').']</mark>';
@endphp
@section('content')
<div class="pt-32 pb-24">
    <div class="wrap grid lg:grid-cols-12 gap-12">
        <aside class="lg:col-span-3 lg:sticky lg:top-28 self-start">
            <a href="{{ route('legal.index') }}" class="eyebrow hover:text-char">Legal</a>
            <nav class="mt-5 flex lg:flex-col flex-wrap gap-x-5 gap-y-2.5 text-[15px]">
                @foreach (\App\Http\Controllers\LegalController::PAGES as $k => $label)
                    <a href="{{ route('legal.show', $k) }}" class="{{ ($page ?? '') === $k ? 'text-char font-medium' : 'text-stone hover:text-char' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>
        <article class="lg:col-span-8 lg:col-start-5 max-w-3xl">
            <p class="eyebrow">Last updated {{ $l['updated'] }}</p>
            <h1 class="h-display mt-4 text-[clamp(2.8rem,6vw,4.8rem)]">{{ $title }}</h1>
            <div class="mt-10 space-y-6 text-[17px] leading-[1.75] text-stone [&_h2]:font-headline [&_h2]:text-char [&_h2]:text-3xl [&_h2]:mt-12 [&_h2]:mb-2 [&_strong]:text-char [&_a]:text-char [&_a]:underline [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2 [&_li]:pl-1">
                @yield('legal')
            </div>
        </article>
    </div>
</div>
@endsection
