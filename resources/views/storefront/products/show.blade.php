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
    $whatsapp = \App\Models\Setting::get('support_whatsapp', '');
    $whatsappUrl = $whatsapp ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $whatsapp).'?text='.rawurlencode("Hi Farmtech, I have a technical question about {$product->title} ({$product->sku}).") : null;
@endphp

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-3 text-xs text-slate-500 font-mono">
        <a href="{{ route('home') }}" class="hover:text-mint-dark transition">Farmtech</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('category.show', $product->category) }}" class="hover:text-mint-dark transition">{{ $product->category_label }}</a>
        <span class="mx-1.5">/</span>
        <span class="text-brand-900">{{ $product->title }}</span>
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-28 lg:pb-16">
        <div class="grid lg:grid-cols-2 gap-12">
            {{-- Sticky gallery with zoom modal --}}
            <div x-data="{ active: 0, zoomed: false }" class="lg:sticky lg:top-24 self-start">
                <div class="relative aspect-square bg-white border border-slate-200 rounded-2xl overflow-hidden flex items-center justify-center mb-3">
                    @if ($product->images->isNotEmpty())
                        @foreach ($product->images as $i => $image)
                            <img x-show="active === {{ $i }}" x-transition.opacity.duration.300ms
                                 src="{{ $image->url }}" alt="{{ $product->title }}"
                                 @click="zoomed = true" onerror="this.style.display='none'"
                                 class="absolute inset-0 object-contain w-full h-full p-4 drop-shadow-sm cursor-zoom-in">
                        @endforeach
                        <button type="button" @click="zoomed = true" aria-label="Zoom image"
                                class="absolute bottom-3 right-3 w-9 h-9 rounded-full bg-white/90 border border-slate-200 shadow-sm flex items-center justify-center text-slate-600 hover:text-brand-900 hover:scale-110 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                    @else
                        <x-product-image-fallback :category="$product->category" icon-class="w-16 h-16" />
                    @endif

                    @if ($audit?->audit_verdict === 'PASS')
                        <span class="verified-stamp animate-stamp-in absolute top-4 right-4 bg-white/95 shadow">Verified<br>&amp; Cleared</span>
                    @endif
                </div>
                @if ($product->images->count() > 1)
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($product->images as $i => $image)
                            <button type="button" @click="active = {{ $i }}"
                                    :class="active === {{ $i }} ? 'border-mint' : 'border-slate-200'"
                                    class="border-2 rounded-lg overflow-hidden aspect-square hover:border-mint/60 transition">
                                <img src="{{ $image->url }}" alt="" class="object-cover w-full h-full" onerror="this.remove()">
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Zoom modal --}}
                <div x-show="zoomed" x-cloak x-transition.opacity @keydown.escape.window="zoomed = false"
                     class="fixed inset-0 z-50 bg-brand-950/90 backdrop-blur-sm flex items-center justify-center p-6" @click="zoomed = false">
                    <button type="button" @click="zoomed = false" aria-label="Close" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                    @if ($product->images->isNotEmpty())
                        <img :src="[{{ $product->images->map(fn ($img) => "'{$img->url}'")->implode(',') }}][active]" alt="{{ $product->title }}"
                             onerror="this.style.display='none'"
                             class="max-w-full max-h-full object-contain rounded-lg" @click.stop>
                    @endif
                </div>
            </div>

            {{-- Info --}}
            <div x-reveal>
                <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold">{{ $product->category_label }}</p>
                <h1 class="font-display font-bold text-2xl sm:text-3xl mt-1 text-brand-900 leading-tight">{{ $product->title }}</h1>
                <p class="text-slate-600 mt-3 leading-relaxed">{{ $product->short_description }}</p>

                <div class="mt-6 glass-card rounded-2xl p-5">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="font-mono text-3xl font-bold text-brand-900">R{{ number_format($product->retail_price_zar, 2) }}</span>
                        <span class="text-sm text-slate-500">incl. duty &amp; {{ $vatPct }}% VAT</span>
                    </div>
                    <p class="text-xs text-slate-500 font-mono mt-1">Duty {{ $dutyPct }}% + VAT {{ $vatPct }}% already included — nothing extra on delivery</p>

                    <div class="mt-3 text-sm flex items-center gap-2 flex-wrap">
                        @if ($product->stock_status === 'in_stock')
                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>In Stock</span>
                        @else
                            <span class="inline-flex items-center gap-1 text-mint-dark font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-mint"></span>Pre-Order</span>
                        @endif
                        <span class="text-slate-300">·</span>
                        <span class="text-slate-600">Lead time: {{ $product->lead_time_days }}</span>
                        @if ($product->isLowStock())
                            <span class="text-slate-300">·</span>
                            <span class="text-alert-dark font-semibold flex items-center gap-1.5">
                                <span class="relative flex w-1.5 h-1.5">
                                    <span class="animate-ping absolute inline-flex w-full h-full rounded-full bg-alert opacity-75"></span>
                                    <span class="relative inline-flex rounded-full w-1.5 h-1.5 bg-alert"></span>
                                </span>
                                Only {{ $product->stock_quantity }} left in stock
                            </span>
                        @endif
                    </div>

                    @if ($product->isOutOfStock())
                        <div class="mt-5 bg-slate-100 border border-slate-200 rounded-full px-6 py-3 text-center text-sm text-slate-600 font-medium">
                            Out of stock — check back soon
                        </div>
                    @else
                        <form id="add-to-cart-form" action="{{ route('cart.add', $product) }}" method="POST" class="mt-5 flex gap-3">
                            @csrf
                            <input type="number" name="quantity" value="1" min="1" @if($product->isTracked() && !$product->allow_backorder) max="{{ $product->stock_quantity }}" @endif
                                   class="w-20 border border-slate-200 rounded-full px-4 py-2.5 text-center focus:outline-none focus:ring-2 focus:ring-mint/30">
                            <button type="submit"
                                    x-data="{ loading: false }" @click="loading = true"
                                    class="flex-1 bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-2.5 rounded-full transition active:scale-95 flex items-center justify-center gap-2">
                                <svg x-show="loading" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="9" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
                                <span x-text="loading ? 'Adding…' : 'Add to Cart'"></span>
                            </button>
                        </form>
                    @endif
                </div>

                <x-compliance-badges :product="$product" />

                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                       class="mt-4 flex items-center gap-3 border border-slate-200 bg-white rounded-xl px-4 py-3 hover:border-emerald-300 hover:bg-emerald-50/50 transition group">
                        <span class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-brand-900 group-hover:text-emerald-700 transition">Need custom technical specifications?</span>
                            <span class="block text-xs text-slate-500">Chat with an equipment specialist on WhatsApp</span>
                        </span>
                    </a>
                @endif

                {{-- Tabbed spec matrix --}}
                <div x-data="{ tab: 'specs' }" class="mt-8">
                    <div class="flex gap-1 border-b border-slate-200 overflow-x-auto">
                        <button type="button" @click="tab = 'specs'" :class="tab === 'specs' ? 'border-mint text-brand-900' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2.5 text-sm font-semibold border-b-2 whitespace-nowrap transition">Specifications</button>
                        <button type="button" @click="tab = 'compliance'" :class="tab === 'compliance' ? 'border-mint text-brand-900' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2.5 text-sm font-semibold border-b-2 whitespace-nowrap transition">ISO Compliance &amp; ICASA</button>
                        <button type="button" @click="tab = 'delivery'" :class="tab === 'delivery' ? 'border-mint text-brand-900' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-2.5 text-sm font-semibold border-b-2 whitespace-nowrap transition">Delivery &amp; Warranty</button>
                    </div>

                    <div x-show="tab === 'specs'" x-cloak class="pt-5">
                        @if ($product->specs->isNotEmpty())
                            <div class="border border-slate-200 rounded-xl overflow-hidden bg-white divide-y divide-slate-200">
                                @foreach ($product->specs->groupBy('spec_group') as $group => $specs)
                                    <div>
                                        <p class="text-xs font-semibold text-mint-dark uppercase tracking-widest px-4 pt-3 pb-1">{{ $group }}</p>
                                        <table class="w-full text-sm">
                                            <tbody>
                                                @foreach ($specs as $spec)
                                                    <tr class="{{ $spec->is_highlight ? 'bg-mint/5' : '' }}">
                                                        <td class="px-4 py-2 font-medium text-slate-600 w-1/2">{{ $spec->spec_key }}</td>
                                                        <td class="px-4 py-2 font-mono text-brand-900">{{ $spec->spec_value }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No detailed specifications recorded for this listing.</p>
                        @endif
                    </div>

                    <div x-show="tab === 'compliance'" x-cloak class="pt-5">
                        @if ($checks->isNotEmpty())
                            <div class="border border-slate-200 rounded-xl bg-white p-5">
                                <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-3">What we checked before listing this</p>
                                <ul class="space-y-2 text-sm text-slate-700">
                                    @foreach ($checks as $check)
                                        <li class="flex gap-2"><span class="text-emerald-600 flex-shrink-0">✓</span> {{ $check }}</li>
                                    @endforeach
                                </ul>
                                @if ($audit?->audit_verdict)
                                    <p class="text-xs text-slate-500 mt-4 pt-3 border-t border-slate-100">AI audit verdict: <span class="font-semibold text-brand-900">{{ $audit->audit_verdict }}</span></p>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No compliance audit recorded for this listing.</p>
                        @endif
                    </div>

                    <div x-show="tab === 'delivery'" x-cloak class="pt-5">
                        <div class="border border-slate-200 rounded-xl bg-white p-5">
                            <p class="text-sm font-semibold text-brand-900 mb-3">Free Express Door-to-Door Delivery Across South Africa (All Customs &amp; Clearance Handled)</p>
                            <div class="flex items-center text-[11px] text-slate-500">
                                @foreach (['Order Placed', 'Customs Clearance', 'Delivered'] as $i => $step)
                                    <div class="flex-1 flex flex-col items-center text-center">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $i === 0 ? 'bg-mint' : 'bg-slate-300' }}"></span>
                                        <span class="mt-1.5">{{ $step }}</span>
                                    </div>
                                    @if (!$loop->last)
                                        <div class="flex-1 h-px bg-slate-300 -mt-4"></div>
                                    @endif
                                @endforeach
                            </div>
                            <p class="text-center text-xs text-slate-500 mt-2">Typically {{ $product->lead_time_days }} via tracked Direct Express air freight.</p>

                            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                                <span><span class="text-slate-500">Import duty</span> <span class="font-mono font-semibold text-brand-900">{{ $dutyPct }}%</span></span>
                                <span><span class="text-slate-500">SARS VAT</span> <span class="font-mono font-semibold text-brand-900">{{ $vatPct }}%</span></span>
                                <span><span class="text-slate-500">HS Code</span> <span class="font-mono font-semibold text-brand-900">{{ $product->hs_code ?? '—' }}</span></span>
                            </div>

                            <div class="mt-5 pt-4 border-t border-slate-100">
                                <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-1.5">Warranty</p>
                                <p class="text-sm text-slate-600 leading-relaxed">Manufacturer warranty terms vary by supplier and model — contact <a href="mailto:support@farmtech.co.za" class="text-mint-dark hover:underline">support@farmtech.co.za</a> with this product's SKU for the specific coverage on this listing.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($product->description_html)
            <div x-reveal class="mt-10 max-w-3xl prose prose-sm prose-headings:font-display max-w-none text-brand-900">
                {!! $product->description_html !!}
            </div>
        @endif
    </div>

    @if ($related->isNotEmpty())
        <section class="bg-slate-100/60 border-t border-slate-200 mt-8 py-14">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="font-display font-bold text-xl text-brand-900 mb-6">You Might Also Need</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($related as $i => $item)
                        @include('storefront.products._card', ['product' => $item, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Floating WhatsApp specialist CTA --}}
    @if ($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="Chat with an equipment specialist on WhatsApp"
           class="hidden lg:flex fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full bg-emerald-500 hover:bg-emerald-600 shadow-lg items-center justify-center transition hover:scale-105">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
        </a>
        {{-- Mobile: above the sticky add-to-cart bar rather than overlapping it --}}
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="Chat with an equipment specialist on WhatsApp"
           class="lg:hidden fixed {{ $product->isOutOfStock() ? 'bottom-6' : 'bottom-20' }} right-4 z-40 w-12 h-12 rounded-full bg-emerald-500 shadow-lg flex items-center justify-center transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
        </a>
    @endif

    {{-- Mobile sticky add-to-cart bar --}}
    @if (!$product->isOutOfStock())
        <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 flex items-center gap-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
            <div class="min-w-0">
                <p class="font-mono font-bold text-brand-900 text-lg leading-none">R{{ number_format($product->retail_price_zar, 2) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5 truncate">{{ $product->title }}</p>
            </div>
            <button type="submit" form="add-to-cart-form" class="flex-shrink-0 ml-auto bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-2.5 rounded-full transition active:scale-95">
                Add to Cart
            </button>
        </div>
    @endif
@endsection
