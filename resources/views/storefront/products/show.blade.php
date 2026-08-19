@extends('layouts.storefront')

@section('title', $product->title.' — Farmtech')
@section('meta_description', $product->short_description)

@php
    $audit = $product->complianceAudit;
    $dutyPct = number_format($product->customs_duty_rate * 100, 1);
    $vatPct = number_format($product->vat_rate * 100, 0);
    $checks = collect([
        $audit?->frequency_checked,
        $audit?->icasa_status ? 'ICASA: '.ucfirst(str_replace('_', ' ', $audit->icasa_status)) : null,
        $audit?->battery_transport_cert ? 'Battery transport cert: '.$audit->battery_transport_cert : null,
        $audit ? 'Supplier: '.$audit->supplier_name.($audit->supplier_years ? " ({$audit->supplier_years}+ years trading)" : '') : null,
    ])->filter();
@endphp

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-3 text-xs text-steel-600 font-mono">
        <a href="{{ route('home') }}" class="hover:text-tag transition">Farmtech</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('category.show', $product->category) }}" class="hover:text-tag transition">{{ $product->category_label }}</a>
        <span class="mx-1.5">/</span>
        <span class="text-field-900">{{ $product->title }}</span>
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-16">
        <div class="grid lg:grid-cols-2 gap-12">
            {{-- Gallery --}}
            <div x-data="{ active: 0 }">
                <div class="relative aspect-square bg-white border border-steel-200 rounded-2xl overflow-hidden flex items-center justify-center mb-3">
                    @if ($product->images->isNotEmpty())
                        @foreach ($product->images as $i => $image)
                            <img x-show="active === {{ $i }}" x-transition.opacity.duration.300ms
                                 src="{{ $image->url }}" alt="{{ $product->title }}"
                                 class="absolute inset-0 object-contain w-full h-full p-4">
                        @endforeach
                    @else
                        <span class="text-steel-500">No image available</span>
                    @endif

                    @if ($audit?->audit_verdict === 'PASS')
                        <span class="verified-stamp animate-stamp-in absolute top-4 right-4 bg-white/95 shadow">Verified<br>&amp; Cleared</span>
                    @endif
                </div>
                @if ($product->images->count() > 1)
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($product->images as $i => $image)
                            <button type="button" @click="active = {{ $i }}"
                                    :class="active === {{ $i }} ? 'border-tag' : 'border-steel-200'"
                                    class="border-2 rounded-lg overflow-hidden aspect-square hover:border-tag/60 transition">
                                <img src="{{ $image->url }}" alt="" class="object-cover w-full h-full">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div x-reveal>
                <p class="text-xs uppercase tracking-widest text-tag font-semibold">{{ $product->category_label }}</p>
                <h1 class="font-display font-bold text-2xl sm:text-3xl mt-1 text-field-950 leading-tight">{{ $product->title }}</h1>
                <p class="text-steel-700 mt-3 leading-relaxed">{{ $product->short_description }}</p>

                <div class="mt-6 flex items-baseline gap-2">
                    <span class="font-mono text-3xl font-bold text-field-950">R{{ number_format($product->retail_price_zar, 2) }}</span>
                    <span class="text-sm text-steel-600">incl. duty &amp; 15% VAT</span>
                </div>

                <div class="mt-2 text-sm flex items-center gap-2">
                    @if ($product->stock_status === 'in_stock')
                        <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>In Stock</span>
                    @else
                        <span class="inline-flex items-center gap-1 text-tag font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-tag"></span>Pre-Order</span>
                    @endif
                    <span class="text-steel-400">·</span>
                    <span class="text-steel-700">Lead time: {{ $product->lead_time_days }}</span>
                </div>

                <form action="{{ route('cart.add', $product) }}" method="POST" class="mt-6 flex gap-3">
                    @csrf
                    <input type="number" name="quantity" value="1" min="1" class="w-20 border border-steel-200 rounded-full px-4 py-2.5 text-center focus:outline-none focus:ring-2 focus:ring-tag/30">
                    <button type="submit" class="flex-1 bg-tag hover:bg-tag-dark text-white font-semibold px-6 py-2.5 rounded-full transition active:scale-95">
                        Add to Cart
                    </button>
                </form>

                {{-- One unified transparency card: cost, delivery, and what was checked — no color-switching between sections --}}
                <div class="mt-8 bg-steel-100/50 rounded-2xl p-5 divide-y divide-steel-200">
                    <div class="pb-4">
                        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                            <span><span class="text-steel-600">Import duty</span> <span class="font-mono font-semibold text-field-950">{{ $dutyPct }}%</span></span>
                            <span><span class="text-steel-600">SARS VAT</span> <span class="font-mono font-semibold text-field-950">{{ $vatPct }}%</span></span>
                            <span><span class="text-steel-600">HS Code</span> <span class="font-mono font-semibold text-field-950">{{ $product->hs_code ?? '—' }}</span></span>
                        </div>
                        <p class="text-xs text-steel-600 mt-2">Already included above — customs is handled for you, nothing extra on delivery.</p>
                    </div>

                    <div class="py-4">
                        <div class="flex items-center text-[11px] text-steel-600">
                            @foreach (['Order Placed', 'Customs Clearance', 'Delivered'] as $i => $step)
                                <div class="flex-1 flex flex-col items-center text-center">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $i === 0 ? 'bg-tag' : 'bg-steel-300' }}"></span>
                                    <span class="mt-1.5">{{ $step }}</span>
                                </div>
                                @if (!$loop->last)
                                    <div class="flex-1 h-px bg-steel-300 -mt-4"></div>
                                @endif
                            @endforeach
                        </div>
                        <p class="text-center text-xs text-steel-500 mt-2">Typically {{ $product->lead_time_days }} via tracked Direct Express air freight.</p>
                    </div>

                    @if ($checks->isNotEmpty())
                        <div class="pt-4">
                            <p class="text-xs font-semibold text-field-900 uppercase tracking-wide mb-2">What we checked before listing this</p>
                            <ul class="space-y-1.5 text-sm text-steel-700">
                                @foreach ($checks as $check)
                                    <li class="flex gap-2"><span class="text-emerald-600 flex-shrink-0">✓</span> {{ $check }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Specs — one card, group headers as internal dividers rather than N separate boxes --}}
        @if ($product->specs->isNotEmpty())
            <div x-reveal class="mt-16 max-w-3xl">
                <h2 class="font-display font-bold text-xl text-field-950 mb-5">Technical Specifications</h2>
                <div class="border border-steel-200 rounded-xl overflow-hidden bg-white divide-y divide-steel-200">
                    @foreach ($product->specs->groupBy('spec_group') as $group => $specs)
                        <div>
                            <p class="text-xs font-semibold text-tag uppercase tracking-widest px-4 pt-3 pb-1">{{ $group }}</p>
                            <table class="w-full text-sm">
                                <tbody>
                                    @foreach ($specs as $spec)
                                        <tr class="{{ $spec->is_highlight ? 'bg-tag/5' : '' }}">
                                            <td class="px-4 py-2 font-medium text-steel-700 w-1/2">{{ $spec->spec_key }}</td>
                                            <td class="px-4 py-2 font-mono text-field-950">{{ $spec->spec_value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($product->description_html)
            <div x-reveal class="mt-10 max-w-3xl prose prose-sm prose-headings:font-display max-w-none text-field-900">
                {!! $product->description_html !!}
            </div>
        @endif
    </div>

    @if ($related->isNotEmpty())
        <section class="bg-steel-100/60 border-t border-steel-200 mt-8 py-14">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="font-display font-bold text-xl text-field-950 mb-6">You Might Also Need</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($related as $i => $item)
                        @include('storefront.products._card', ['product' => $item, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
