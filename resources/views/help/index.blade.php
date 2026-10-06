@extends('layouts.herd', ['module' => null])
@section('title', 'Help & guides')
@section('eyebrow')Plain-English, step by step. No manual needed. @endsection
@section('content')
<div x-data="{ q: '' }">
<label class="relative block mb-6 max-w-md"><span class="sr-only">Search the guides</span>
    <input x-model="q" type="search" placeholder="Search the guides, e.g. Wi-Fi, Excel, sold…" class="field pl-11">
    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
</label>
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach (\App\Http\Controllers\HelpController::GUIDES as $key => [$title, $sub, $icon])
        <a href="{{ route('help.show', $key) }}" x-show="!q || @js(strtolower($title.' '.$sub.' '.$key)).includes(q.toLowerCase())" class="group panel p-6 flex gap-5 hover:border-char hover:-translate-y-0.5 transition {{ in_array($key, ['getting-started', 'excel', 'esp32']) ? 'ring-1 ring-ochre/40' : '' }}">
            <span class="w-12 h-12 shrink-0 rounded-2xl {{ in_array($key, ['getting-started', 'excel', 'esp32']) ? 'bg-ochre text-char' : 'bg-sand-deep text-char' }} grid place-items-center">@include('partials.icon', ['name' => $icon, 'class' => 'w-6 h-6'])</span>
            <div>
                <div class="font-headline text-2xl leading-tight">{{ $title }}</div>
                <p class="text-sm text-stone mt-1">{{ $sub }}</p>
            </div>
        </a>
    @endforeach
</div>
</div>
<div class="mt-8 grid md:grid-cols-2 gap-4">
    <button type="button" onclick="location.href='{{ route('rfid.dashboard') }}?tour=1'" class="panel p-6 text-left hover:border-char transition">
        <div class="font-medium">Replay the welcome tour</div><div class="text-sm text-stone mt-1">The one-minute look around the KraalTrac Pro overview.</div>
    </button>
    <a href="{{ route('site.contact') }}" class="panel p-6 hover:border-char transition">
        <div class="font-medium">Still stuck? Talk to a person.</div><div class="text-sm text-stone mt-1">We usually reply within one working day. <a class="link-u text-char" href="{{ route('legal.index') }}">Terms, privacy &amp; legal</a></div>
    </a>
</div>
@endsection
