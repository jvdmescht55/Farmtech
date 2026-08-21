@extends('layouts.storefront')

@section('title', 'Shop by Equipment — Farmtech')
@section('meta_description', 'Browse all Farmtech equipment categories, grouped by industry — agriculture, construction, industrial & logistics, and solar power.')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-10">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold">Full catalogue</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mt-1">Shop by Equipment</h1>
        <p class="text-ink-secondary mt-2 max-w-2xl">Every category Farmtech imports, grouped by industry. Prefer to shop by task instead? See <a href="{{ route('home') }}#shop-by-application" class="text-mint-dark hover:underline">Shop by Application</a> on the homepage.</p>

        @foreach ($industries as $industry)
            <section class="mt-10">
                <div class="flex items-baseline justify-between gap-4 mb-4">
                    <h2 class="font-display font-bold text-lg text-brand-900">{{ $industry->label() }}</h2>
                    <a href="{{ route('industry.show', $industry) }}" class="text-sm font-semibold text-mint-dark hover:text-mint-darker transition flex-shrink-0">View all {{ $industry->label() }} &rarr;</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach ($industry->categories() as $category)
                        <x-category-tile :category="$category" />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection
