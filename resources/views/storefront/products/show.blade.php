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
    $hasVariants = $product->hasVariants();
@endphp

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-3 text-xs text-ink-secondary font-mono">
        <a href="{{ route('home') }}" class="hover:text-mint-dark transition">Farmtech</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('category.show', $product->category) }}" class="hover:text-mint-dark transition">{{ $product->category_label }}</a>
        <span class="mx-1.5">/</span>
        <span class="text-brand-900">{{ $product->title }}</span>
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-28 lg:pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            {{-- Sticky gallery with hover-zoom lens (desktop) and a click-to-fullscreen modal (all sizes) --}}
            <div x-data="{
                    active: 0, zoomed: false, hovering: false, lensX: 50, lensY: 50,
                    images: {{ Js::from($product->images->pluck('url')) }},
                    track(e) {
                        const r = e.currentTarget.getBoundingClientRect();
                        this.lensX = Math.max(0, Math.min(100, ((e.clientX - r.left) / r.width) * 100));
                        this.lensY = Math.max(0, Math.min(100, ((e.clientY - r.top) / r.height) * 100));
                    },
                 }" class="min-w-0 lg:sticky lg:top-24 self-start">
                <div class="relative w-full aspect-square bg-white border border-border rounded-xl overflow-hidden flex items-center justify-center mb-3"
                     @mousemove="track($event)" @mouseenter="hovering = true" @mouseleave="hovering = false">
                    @if ($product->images->isNotEmpty())
                        @foreach ($product->images as $i => $image)
                            <img x-show="active === {{ $i }}" x-transition.opacity.duration.300ms
                                 src="{{ $image->url }}" alt="{{ $product->title }}"
                                 @click="zoomed = true" onerror="this.style.display='none'"
                                 class="absolute inset-0 object-contain w-full h-full p-4 drop-shadow-sm cursor-zoom-in">
                        @endforeach

                        {{-- Magnify lens — desktop only, follows the cursor over the real image at 2.2x --}}
                        <div x-show="hovering && images[active]" x-cloak
                             class="hidden lg:block absolute inset-0 pointer-events-none"
                             :style="'background-image: url(' + JSON.stringify(images[active]) + '); background-repeat: no-repeat; background-size: 220%; background-position: ' + lensX + '% ' + lensY + '%;'">
                        </div>

                        <button type="button" @click="zoomed = true" aria-label="Zoom image"
                                class="absolute bottom-3 right-3 w-9 h-9 rounded-full bg-white/90 border border-border shadow-sm flex items-center justify-center text-ink-secondary hover:text-brand-900 hover:scale-110 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                    @else
                        <x-product-image-fallback :category="$product->category" icon-class="w-16 h-16" />
                    @endif

                    @if ($audit?->audit_verdict === 'PASS')
                        <span class="verified-stamp animate-stamp-in absolute top-4 right-4 bg-white/95 shadow">Verified<br>&amp; Cleared</span>
                    @endif

                    @if ($product->video_url)
                        <span class="absolute top-4 left-4 inline-flex items-center gap-1 bg-brand-900/90 text-white text-[11px] font-semibold px-2 py-1 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg> Video
                        </span>
                    @endif
                </div>

                @if ($product->video_url)
                    {{-- Supplier listing video — a direct link found in this product's own
                         stored listing data (or added by an admin), hotlinked the same way
                         supplier images are. Degrades to its poster / a direct link if the
                         host blocks hotlinking or the asset is gone. --}}
                    <div class="mb-3">
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-mint-dark mb-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Supplier product video
                        </p>
                        <video controls preload="none" playsinline
                               @if ($product->images->isNotEmpty()) poster="{{ $product->images->first()->url }}" @endif
                               class="w-full rounded-xl border border-border bg-black aspect-video object-contain">
                            <source src="{{ $product->video_url }}">
                            <span class="block p-3 text-xs text-ink-secondary">Your browser can’t play this video —
                                <a href="{{ $product->video_url }}" target="_blank" rel="noopener" class="text-mint-dark underline">open it directly</a>.</span>
                        </video>
                    </div>
                @endif
                @if ($product->images->count() > 1)
                    <div x-data="{ showAll: false }" class="grid grid-cols-5 gap-2">
                        @foreach ($product->images as $i => $image)
                            <button type="button" x-show="{{ $i }} < 10 || showAll" @click="active = {{ $i }}"
                                    :class="active === {{ $i }} ? 'border-mint' : 'border-border'"
                                    class="border-2 rounded-lg overflow-hidden aspect-square hover:border-mint/60 transition">
                                <img src="{{ $image->url }}" alt="" class="object-cover w-full h-full" onerror="this.remove()">
                            </button>
                        @endforeach
                        @if ($product->images->count() > 10)
                            <button type="button" @click="showAll = !showAll" x-show="!showAll"
                                    class="border-2 border-dashed border-border rounded-lg aspect-square flex items-center justify-center text-xs font-semibold text-ink-secondary hover:border-mint/60 transition"
                                    x-text="'+{{ $product->images->count() - 10 }} more'"></button>
                        @endif
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
                <h1 class="font-display font-semibold text-3xl sm:text-4xl mt-2 text-brand-900 leading-[1.15]">{{ $product->title }}</h1>
                <p class="text-ink-secondary mt-4 leading-relaxed">{{ $product->short_description }}</p>

                @if (!empty($product->key_features))
                    <ul class="mt-3 grid sm:grid-cols-2 gap-x-4 gap-y-1.5 text-sm text-charcoal">
                        @foreach ($product->key_features as $feature)
                            <li class="flex gap-1.5"><span class="text-mint-dark flex-shrink-0">✓</span> {{ $feature }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-6 glass-card rounded-xl p-5">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span id="pdp-price" class="font-mono text-3xl font-bold text-brand-900">{{ $hasVariants ? $product->price_range_display : 'R'.number_format($product->retail_price_zar, 0, '', ' ') }}</span>
                        <span class="text-sm text-ink-secondary">incl. duty &amp; {{ $vatPct }}% VAT</span>
                    </div>
                    <p class="text-xs text-ink-secondary font-mono mt-1">Duty {{ $dutyPct }}% + VAT {{ $vatPct }}% already included — nothing extra on delivery</p>

                    @if ($hasVariants)
                        <p id="pdp-sku" class="text-xs text-ink-muted font-mono mt-1">SKU: {{ $product->sku }}</p>

                        <div class="mt-4 pt-4 border-t border-border">
                            <p class="text-xs font-semibold text-ink-secondary uppercase tracking-wide mb-1.5">
                                Choose an option — <span id="pdp-selected-option" class="normal-case text-brand-900">select below</span>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($product->variants as $variant)
                                    <button type="button"
                                            id="variant-btn-{{ $variant->id }}"
                                            class="variant-option-btn border rounded-full px-4 py-1.5 text-sm font-medium transition border-border text-ink-secondary hover:border-mint/50"
                                            onclick="ftSelectVariant({{ $variant->id }}, {{ (float) $variant->retail_price_zar }}, '{{ addslashes($variant->sku) }}', '{{ addslashes($variant->option_name) }}', this)">
                                        {{ $variant->option_name }}
                                        <span class="font-mono text-xs opacity-75">— R{{ number_format($variant->retail_price_zar, 2) }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <p id="pdp-variant-required" class="hidden text-xs font-semibold text-alert-dark bg-alert/10 rounded-lg px-3 py-2 mt-3">Please select an option above before adding this item to your cart.</p>
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-mint-dark bg-mint/10 rounded-full px-3 py-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            All-In Landed Pricing &middot; R0 Extra At Door
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-alert-dark bg-alert/10 rounded-full px-3 py-1.5">
                            ⚡ Express Air-Import &middot; {{ $product->lead_time_days }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-900 bg-canvas border border-border rounded-full px-3 py-1.5">
                            🇿🇦 Vetted for African Conditions &middot; Built for the working farm
                        </span>
                    </div>

                    <div class="mt-3 text-sm flex items-center gap-2 flex-wrap">
                        @if ($product->stock_status === 'in_stock')
                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>In Stock</span>
                        @else
                            <span class="inline-flex items-center gap-1 text-mint-dark font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-mint"></span>Pre-Order</span>
                        @endif
                        <span class="text-ink-muted">·</span>
                        <span class="text-ink-secondary">Lead time: {{ $product->lead_time_days }}</span>
                        @if ($product->isLowStock())
                            <span class="text-ink-muted">·</span>
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
                        <div class="mt-5 bg-canvas border border-border rounded-full px-6 py-3 text-center text-sm text-ink-secondary font-medium">
                            Out of stock — check back soon
                        </div>
                    @else
                        <form id="add-to-cart-form" action="{{ route('cart.add', $product) }}" method="POST" class="mt-5 flex gap-3" onsubmit="return ftHandleAddToCart(event, {{ $hasVariants ? 'true' : 'false' }})">
                            @csrf
                            <input type="hidden" id="variant_id_input" name="variant_id" value="">
                            <input type="number" name="quantity" value="1" min="1" @if($product->isTracked() && !$product->allow_backorder) max="{{ $product->stock_quantity }}" @endif
                                   class="w-20 border border-border rounded-full px-4 py-2.5 text-center focus:outline-none focus:ring-2 focus:ring-mint/30">
                            <button type="submit" id="add-to-cart-btn"
                                    class="flex-1 bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-2.5 rounded-full transition active:scale-95 flex items-center justify-center gap-2">
                                <span>Add to Cart</span>
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Farmtech Assurance — the fixed, store-wide policy promise (every
                     order, every product), distinct from x-compliance-badges below
                     which shows only the per-product audit signals actually on file. --}}
                <div class="mt-4 border border-mint/30 bg-mint/5 rounded-xl p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-mint-dark mb-2.5">Farmtech Assurance</p>
                    <ul class="space-y-1.5 text-sm text-charcoal">
                        <li class="flex gap-2"><span class="flex-shrink-0">🛡️</span> 1-Year Local Replacement Guarantee</li>
                        <li class="flex gap-2"><span class="flex-shrink-0">📦</span> Customs, Duties &amp; Door Courier Included (No hidden fees)</li>
                        <li class="flex gap-2"><span class="flex-shrink-0">⚡</span> South African Power &amp; Frequency Compatibility Verified</li>
                        <li class="flex gap-2"><span class="flex-shrink-0">💬</span> Direct WhatsApp setup assistance</li>
                    </ul>
                    <p class="text-xs text-ink-secondary mt-3 pt-3 border-t border-mint/20">Express Air Freight Delivery: 7&ndash;12 business days directly to your door with end-to-end tracking.</p>
                </div>

                <x-compliance-badges :product="$product" />

                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                       class="mt-4 flex items-center gap-3 border border-border bg-white rounded-xl px-4 py-3 hover:border-emerald-300 hover:bg-emerald-50/50 transition group">
                        <span class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-brand-900 group-hover:text-emerald-700 transition">Need custom technical specifications?</span>
                            <span class="block text-xs text-ink-secondary">Chat with an equipment specialist on WhatsApp</span>
                        </span>
                    </a>
                @endif

                {{-- Tabbed spec matrix --}}
                <div x-data="{ tab: 'specs' }" class="mt-8">
                    <div class="flex gap-1 border-b border-border overflow-x-auto">
                        <button type="button" @click="tab = 'specs'" :class="tab === 'specs' ? 'border-mint text-brand-900' : 'border-transparent text-ink-secondary hover:text-charcoal'" class="px-4 py-2.5 text-sm font-semibold border-b-2 whitespace-nowrap transition">Specifications</button>
                        <button type="button" @click="tab = 'included'" :class="tab === 'included' ? 'border-mint text-brand-900' : 'border-transparent text-ink-secondary hover:text-charcoal'" class="px-4 py-2.5 text-sm font-semibold border-b-2 whitespace-nowrap transition">What's Included</button>
                        <button type="button" @click="tab = 'compliance'" :class="tab === 'compliance' ? 'border-mint text-brand-900' : 'border-transparent text-ink-secondary hover:text-charcoal'" class="px-4 py-2.5 text-sm font-semibold border-b-2 whitespace-nowrap transition">ISO Compliance &amp; ICASA</button>
                        <button type="button" @click="tab = 'delivery'" :class="tab === 'delivery' ? 'border-mint text-brand-900' : 'border-transparent text-ink-secondary hover:text-charcoal'" class="px-4 py-2.5 text-sm font-semibold border-b-2 whitespace-nowrap transition">Delivery &amp; Warranty</button>
                    </div>

                    <div x-show="tab === 'specs'" x-cloak class="pt-5">
                        @if ($product->specs->isNotEmpty())
                            <div x-data="{ openGroups: @js($product->specs->groupBy('spec_group')->keys()->mapWithKeys(fn ($g) => [$g => true])) }"
                                 class="border border-border rounded-xl overflow-hidden bg-white divide-y divide-border">
                                @foreach ($product->specs->groupBy('spec_group') as $group => $specs)
                                    <div>
                                        <button type="button" @click="openGroups['{{ $group }}'] = !openGroups['{{ $group }}']"
                                                class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-mint/5 transition">
                                            <span class="text-xs font-semibold text-mint-dark uppercase tracking-widest">{{ $group }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-ink-muted transition-transform" :class="openGroups['{{ $group }}'] ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        <table x-show="openGroups['{{ $group }}']" x-transition class="w-full text-sm">
                                            <tbody>
                                                @foreach ($specs as $spec)
                                                    <tr class="{{ $spec->is_highlight ? 'bg-mint/5' : '' }}">
                                                        <td class="px-4 py-2 font-medium text-ink-secondary w-1/2">{{ $spec->spec_key }}</td>
                                                        <td class="px-4 py-2 font-mono text-brand-900">{{ $spec->spec_value }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-ink-secondary">No detailed specifications recorded for this listing.</p>
                        @endif

                        @if (!empty($product->specifications))
                            <div class="mt-4 border border-border rounded-xl bg-white p-4">
                                <p class="text-xs font-semibold text-ink-secondary uppercase tracking-widest mb-2">Full Manufacturer Attributes</p>
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-border">
                                        @foreach ($product->specifications as $key => $value)
                                            <tr>
                                                <td class="px-2 py-1.5 text-ink-secondary w-1/2">{{ \App\Support\SpecLabelHumanizer::humanize($key) }}</td>
                                                <td class="px-2 py-1.5 font-mono text-brand-900">{{ $value }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'included'" x-cloak class="pt-5">
                        <div class="border border-border rounded-xl bg-white p-5">
                            @if (!empty($product->included_items))
                                <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-3">Equipment Manifest — what's in the box</p>
                                <ul class="space-y-2 text-sm text-charcoal">
                                    @foreach ($product->included_items as $item)
                                        <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> {{ $item }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-ink-secondary">Box contents haven't been confirmed for this specific listing yet — contact <a href="mailto:support@farmtech.co.za" class="text-mint-dark hover:underline">support@farmtech.co.za</a> with the SKU ({{ $product->sku }}) before ordering if this matters for your use case.</p>
                            @endif
                        </div>
                    </div>

                    <div x-show="tab === 'compliance'" x-cloak class="pt-5">
                        @if ($checks->isNotEmpty())
                            <div class="border border-border rounded-xl bg-white p-5">
                                <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-3">What we checked before listing this</p>
                                <ul class="space-y-2 text-sm text-charcoal">
                                    @foreach ($checks as $check)
                                        <li class="flex gap-2"><span class="text-emerald-600 flex-shrink-0">✓</span> {{ $check }}</li>
                                    @endforeach
                                </ul>
                                @if ($audit?->audit_verdict)
                                    <p class="text-xs text-ink-secondary mt-4 pt-3 border-t border-border">AI audit verdict: <span class="font-semibold text-brand-900">{{ $audit->audit_verdict }}</span></p>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-ink-secondary">No compliance audit recorded for this listing.</p>
                        @endif
                    </div>

                    <div x-show="tab === 'delivery'" x-cloak class="pt-5">
                        <div class="border border-border rounded-xl bg-white p-5">
                            <p class="text-sm font-semibold text-brand-900 mb-3">Free Express Door-to-Door Delivery Across South Africa (All Customs &amp; Clearance Handled)</p>
                            <div class="flex items-center text-[11px] text-ink-secondary">
                                @foreach (['Order Placed', 'Customs Clearance', 'Delivered'] as $i => $step)
                                    <div class="flex-1 flex flex-col items-center text-center">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $i === 0 ? 'bg-mint' : 'bg-border' }}"></span>
                                        <span class="mt-1.5">{{ $step }}</span>
                                    </div>
                                    @if (!$loop->last)
                                        <div class="flex-1 h-px bg-border -mt-4"></div>
                                    @endif
                                @endforeach
                            </div>
                            <p class="text-center text-xs text-ink-secondary mt-2">Typically {{ $product->lead_time_days }} via tracked Direct Express air freight.</p>

                            <div class="mt-5 pt-4 border-t border-border flex flex-wrap gap-x-6 gap-y-2 text-sm">
                                <span><span class="text-ink-secondary">Import duty</span> <span class="font-mono font-semibold text-brand-900">{{ $dutyPct }}%</span></span>
                                <span><span class="text-ink-secondary">SARS VAT</span> <span class="font-mono font-semibold text-brand-900">{{ $vatPct }}%</span></span>
                                <span><span class="text-ink-secondary">HS Code</span> <span class="font-mono font-semibold text-brand-900">{{ $product->hs_code ?? '—' }}</span></span>
                            </div>

                            <div class="mt-5 pt-4 border-t border-border">
                                <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-1.5">Warranty</p>
                                @if ($product->warranty_period)
                                    <p class="text-sm text-ink-secondary leading-relaxed">This item carries a manufacturer-backed <strong class="text-brand-900">{{ $product->warranty_period }}</strong> warranty. {{ $product->warranty_terms }}</p>
                                @else
                                    <p class="text-sm text-ink-secondary leading-relaxed">Manufacturer warranty terms vary by supplier and model — contact <a href="mailto:support@farmtech.co.za" class="text-mint-dark hover:underline">support@farmtech.co.za</a> with this product's SKU for the specific coverage on this listing.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($product->description_html)
            <div x-reveal class="mt-16 pt-12 border-t border-border max-w-3xl mx-auto lg:mx-0">
                <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-3">The Details</p>
                <div class="prose prose-lg prose-headings:font-display prose-headings:font-semibold prose-headings:text-brand-900 prose-p:leading-relaxed prose-p:text-ink-secondary max-w-none">
                    {!! $product->description_html !!}
                </div>
            </div>
        @endif
    </div>

    {{-- Trust section — real Alibaba buyer feedback for this listing's verified supplier.
         Deliberately labelled "for [supplier]", not "reviews of this product": Alibaba's
         review feed is store-wide (covers everything that supplier sells), not per-SKU —
         confirmed by cross-checking the raw scrape data, where individual review entries
         carry a productId that doesn't match this listing's own. Real, unedited feedback,
         just honestly attributed to the supplier rather than implied to be unboxing
         reviews of this exact item. Not rendered at all when there's nothing imported. --}}
    @if ($product->reviews->isNotEmpty())
        <section class="border-t border-border py-14">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                    <div>
                        <h2 class="font-display font-bold text-xl text-brand-900">Verified Buyer Feedback{{ $product->supplier_name ? ' for '.$product->supplier_name : '' }}</h2>
                        <p class="text-sm text-ink-secondary mt-1">Real trade-verified reviews from Alibaba, for the supplier behind this listing — not reviews of this specific item's unboxing.</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="font-display font-bold text-2xl text-brand-900">{{ number_format($product->average_rating, 1) }}</span>
                        <div>
                            <div class="flex text-amber-400">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="{{ $i <= round($product->average_rating) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                @endfor
                            </div>
                            <p class="text-xs text-ink-secondary">{{ $product->reviews->count() }} review{{ $product->reviews->count() === 1 ? '' : 's' }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($product->reviews->take(9) as $review)
                        <div class="border border-border rounded-xl bg-white p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-sm text-brand-900">{{ $review->author_name }}</span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 rounded-full px-2 py-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    Verified Buyer
                                </span>
                            </div>
                            <div class="flex text-amber-400 mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="{{ $i <= $review->rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                @endfor
                            </div>
                            @if ($review->review_text)
                                <p class="text-sm text-charcoal leading-relaxed">{{ $review->review_text }}</p>
                            @endif
                            @if ($review->review_date)
                                <p class="text-xs text-ink-muted mt-2">{{ $review->review_date->format('j M Y') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($product->bundleCompanions->isNotEmpty())
        <section class="border-t border-border mt-8 py-14">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="font-display font-bold text-xl text-brand-900 mb-1">Frequently Bought Together</h2>
                <p class="text-sm text-ink-secondary mb-6">Real companion equipment for this listing, curated by Farmtech — not an automated guess.</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($product->bundleCompanions as $i => $item)
                        @include('storefront.products._card', ['product' => $item, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="bg-canvas/60 border-t border-border mt-8 py-14">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="font-display font-bold text-xl text-brand-900 mb-6">Related Hardware &amp; Add-Ons</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($related as $i => $item)
                        @include('storefront.products._card', ['product' => $item, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($recentlyViewed->isNotEmpty())
        <section class="border-t border-border py-14">
            <div class="max-w-7xl mx-auto px-4">
                <h2 class="font-display font-bold text-xl text-brand-900 mb-6">Recently Viewed</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($recentlyViewed as $i => $item)
                        @include('storefront.products._card', ['product' => $item, 'delay' => $i * 70])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Desktop floating WhatsApp specialist CTA — mobile gets it inline in the sticky purchase bar below instead. --}}
    @if ($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="Chat with an equipment specialist on WhatsApp"
           class="hidden lg:flex fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full bg-emerald-500 hover:bg-emerald-600 shadow-lg items-center justify-center transition hover:scale-105">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
        </a>
    @endif

    {{-- Mobile sticky purchase bar — price + WhatsApp enquiry + Add to Cart together, so a buyer never has to hunt for either action. --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-border px-4 py-3 flex items-center gap-2 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
        <div class="min-w-0 flex-1">
            <p id="pdp-price-mobile" class="font-mono font-bold text-brand-900 text-lg leading-none">{{ $hasVariants ? $product->price_range_display : 'R'.number_format($product->retail_price_zar, 0, '', ' ') }}</p>
            <p class="text-[11px] text-ink-secondary mt-0.5 truncate">{{ $product->title }}</p>
        </div>
        @if ($whatsappUrl)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="Chat with an equipment specialist on WhatsApp"
               class="flex-shrink-0 w-11 h-11 rounded-full bg-emerald-500 hover:bg-emerald-600 shadow-sm flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
            </a>
        @endif
        @if (!$product->isOutOfStock())
            <button type="submit" form="add-to-cart-form" class="flex-shrink-0 bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-2.5 rounded-full transition active:scale-95">
                Add to Cart
            </button>
        @endif
    </div>

    {{-- Add-to-cart submit handler — routes through the cart drawer's Alpine store (AJAX,
         opens the drawer, no page reload) when it's available, falling back to a normal
         form submit otherwise. Deliberately plain JS, not an Alpine directive on the form
         itself, so it works identically whether or not $hasVariants pulls in the variant
         script below. --}}
    <script>
        function ftHandleAddToCart(event, hasVariants) {
            event.preventDefault();
            if (hasVariants && !document.getElementById('variant_id_input').value) {
                document.getElementById('pdp-variant-required').classList.remove('hidden');
                return false;
            }
            if (window.Alpine && window.Alpine.store('cart')) {
                window.Alpine.store('cart').submitForm(event.target);
            } else {
                event.target.submit();
            }
            return false;
        }
    </script>

    {{-- Vanilla JS variant selector — deliberately not Alpine, unlike the rest of this
         page: a flat list of real ProductVariant rows (each with its own real price),
         not a multi-attribute combination matrix, so there's no "invalid combination"
         state to manage — every button is always a real, purchasable SKU. --}}
    @if ($hasVariants)
        <script>
            function ftFormatZar(n) {
                return 'R' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function ftSelectVariant(variantId, priceZar, sku, optionName, buttonEl) {
                document.getElementById('variant_id_input').value = variantId;
                document.getElementById('pdp-price').textContent = ftFormatZar(priceZar);
                document.getElementById('pdp-price-mobile').textContent = ftFormatZar(priceZar);
                document.getElementById('pdp-sku').textContent = 'SKU: ' + sku;
                document.getElementById('pdp-selected-option').textContent = optionName;
                document.getElementById('pdp-variant-required').classList.add('hidden');

                document.querySelectorAll('.variant-option-btn').forEach(function (btn) {
                    btn.classList.remove('border-mint', 'bg-mint/10', 'text-brand-900');
                    btn.classList.add('border-border', 'text-ink-secondary');
                });
                buttonEl.classList.remove('border-border', 'text-ink-secondary');
                buttonEl.classList.add('border-mint', 'bg-mint/10', 'text-brand-900');
            }
        </script>
    @endif
@endsection
