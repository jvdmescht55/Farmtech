@extends('layouts.rfid')
@section('title', 'Auction books')
@section('eyebrow')Your veiling catalogue — lot numbers, pedigree and EBVs, ready to print @endsection

@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-3 space-y-4">
        @forelse ($catalogues as $c)
            <a href="{{ route('rfid.catalogues.show', $c) }}" class="panel p-6 flex items-center gap-6 hover:border-char hover:-translate-y-0.5 transition">
                <div class="w-14 h-16 shrink-0 rounded-lg bg-sand-deep grid place-items-center font-headline text-2xl text-stone">{{ $c->lots_count }}</div>
                <div class="min-w-0 flex-1">
                    <div class="font-headline text-2xl truncate">{{ $c->title }}</div>
                    <div class="text-sm text-stone">{{ $c->section }} · {{ $c->sale_date?->format('j M Y') ?? 'no date yet' }} · {{ $c->lots_count }} {{ \Illuminate\Support\Str::plural('lot', $c->lots_count) }}</div>
                </div>
                <span class="text-stone">→</span>
            </a>
        @empty
            <div class="panel p-10">
                <div class="font-headline text-4xl">No auction books yet.</div>
                <p class="text-stone mt-3 max-w-lg">Three steps and you're done: give it a name, pick the animals, number the lots. It prints in the same layout as the Logix sale catalogue — tiers, EBVs, lambing records and pedigree included.</p>
            </div>
        @endforelse
    </div>

    <div class="xl:col-span-2">
        <div class="rounded-[24px] bg-char text-sand p-7 sm:p-8">
            <div class="font-headline text-4xl">Make an auction book</div>
            <p class="text-sand/70 mt-2">Start with the basics — you can change them later.</p>
            <form method="POST" action="{{ route('rfid.catalogues.store') }}" class="mt-6 space-y-3">
                @csrf
                <input name="title" required placeholder="Name — e.g. Kenhardt Production Sale 2026" class="w-full h-12 rounded-xl bg-white text-char px-4 focus:outline-none focus:ring-4 focus:ring-ochre/40">
                <div class="grid grid-cols-2 gap-3">
                    <select name="section" class="w-full h-12 rounded-xl bg-white text-char px-3">@foreach (\App\Models\SaleCatalogue::SECTIONS as $s)<option>{{ $s }}</option>@endforeach</select>
                    <input type="date" name="sale_date" class="w-full h-12 rounded-xl bg-white text-char px-3">
                </div>
                <input name="breed" value="{{ auth()->user()->breed }}" placeholder="Breed (e.g. Meatmaster)" class="w-full h-12 rounded-xl bg-white text-char px-4">
                <button class="btn-light w-full mt-2">Next: pick the animals →</button>
            </form>
            <p class="text-xs text-sand/50 mt-4">Your stud name and number come from Farm settings.</p>
        </div>
    </div>
</div>
@endsection
