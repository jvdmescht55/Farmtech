@extends('layouts.site')
@section('title', 'Your cart — Farmtech')
@push('head')<meta name="robots" content="noindex">@endpush
@section('content')
<section class="wrap pt-32 sm:pt-36 pb-24">
    <h1 class="h-display text-[clamp(3rem,7vw,5.5rem)]">Your cart</h1>
    @error('stock')<div class="mt-6 rounded-2xl bg-[#B0452F]/10 border border-[#B0452F]/30 px-5 py-4">{{ $message }}</div>@enderror
    @if ($lines->isEmpty())
        <div class="mt-10 rounded-[28px] bg-white border border-hairline p-12 text-center">
            <div class="font-headline text-4xl">Nothing in here yet.</div>
            <p class="mt-3 text-stone">Have a look at the KraalTrac Pro, the Watch and our ear tags.</p>
            <a href="{{ route('site.store') }}#shop" class="btn-dark mt-8">Go to the shop</a>
        </div>
    @else
        <div class="mt-10 grid lg:grid-cols-[1fr_24rem] gap-10 items-start">
            <div class="rounded-[28px] bg-white border border-hairline px-6 sm:px-8">
                @foreach ($lines as $line)@include('shop._line', ['line' => $line])@endforeach
                <div class="py-5"><a href="{{ route('site.store') }}#shop" class="text-sm underline text-stone">← Keep shopping</a></div>
            </div>
            @include('shop._summary', ['cta' => true])
        </div>
    @endif
</section>
@endsection
