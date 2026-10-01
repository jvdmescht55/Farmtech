@extends('layouts.site')
@section('title', 'Legal — Farmtech')
@section('content')
<div class="pt-32 pb-24 wrap">
    <p class="eyebrow">Legal</p>
    <h1 class="h-display mt-4 text-[clamp(3rem,7vw,5.5rem)]">The fine print, <em>in plain English.</em></h1>
    <p class="mt-6 max-w-xl text-lg text-stone">No legalese if we can help it, oom. If anything here is unclear, ask us — <a class="text-char underline" href="{{ route('site.contact') }}">contact page</a>.</p>
    <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-3 gap-px bg-hairline border border-hairline rounded-[24px] overflow-hidden">
        @foreach (\App\Http\Controllers\LegalController::PAGES as $k => $label)
            <a href="{{ route('legal.show', $k) }}" class="bg-sand p-8 hover:bg-white transition group">
                <div class="font-headline text-3xl">{{ $label }}</div>
                <div class="mt-3 text-sm text-stone group-hover:text-char">Read →</div>
            </a>
        @endforeach
    </div>
</div>
@endsection
