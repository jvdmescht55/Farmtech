@extends("layouts.admin")

@section("content")
@php
    $hasTokenLeak = str_contains($product->short_description, "_") || str_contains($product->short_description, "moisture_meters");
    $isRadio = ($product->icasa_status === 'verification_required');

    // $breakdown comes from ProductController::show() — a real
    // LandedCostCalculator run against this product's own stored supplier
    // cost/weight/exchange rate and the current admin Settings, not ad-hoc
    // numbers computed here.
    $trueLandedCost = $breakdown['landed_cost_zar'];
    $sellingPrice = $product->retail_price_zar > 0 ? (float) $product->retail_price_zar : $breakdown['retail_price_zar'];
    $grossProfit = $sellingPrice - $trueLandedCost;
    $grossMarginPct = $sellingPrice > 0 ? (($grossProfit / $sellingPrice) * 100) : 0;

    $blockers = [];
    if ($isRadio && !$product->radio_frequency_confirmed) {
        $blockers[] = "Radio / LoRa frequency plan not yet confirmed by supplier.";
    }
    if (!$product->datasheet_uploaded) {
        $blockers[] = "Technical datasheet PDF not yet attached.";
    }
    if ($hasTokenLeak) {
        $blockers[] = "Internal category slug token detected in description.";
    }
    $readyToPublish = (count($blockers) === 0);
    $readinessPct = round(((7 - count($blockers)) / 7) * 100);

    $statusBadge = match ($product->status) {
        'approved' => ['label' => 'Approved & Live', 'class' => 'bg-emerald-100 text-emerald-800'],
        'pending_review' => ['label' => 'Vetting Pending', 'class' => 'bg-amber-100 text-amber-800'],
        'rejected' => ['label' => 'Rejected', 'class' => 'bg-red-100 text-red-800'],
        'rejected_uncompetitive' => ['label' => 'Rejected — Uncompetitive', 'class' => 'bg-red-100 text-red-800'],
        'archived' => ['label' => 'Archived', 'class' => 'bg-gray-200 text-gray-700'],
        default => ['label' => ucfirst(str_replace('_', ' ', $product->status)), 'class' => 'bg-gray-100 text-gray-700'],
    };
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-lg font-bold text-gray-900">{{ $product->title }}</h1>
            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-500 mt-1">
            <span>SKU: <strong class="font-mono text-gray-700">{{ $product->sku }}</strong></span>
            <span>Supplier: <strong text-gray-700">{{ $product->supplier_name ?? 'Not yet identified' }}</strong></span>
            <span>Price Checked: <strong text-gray-700">{{ $product->supplier_last_checked_at?->format('d M Y') ?? 'Not yet checked' }}</strong></span>
            @if ($product->source_url)
                <a href="{{ $product->source_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-orange-700 hover:text-orange-800 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    View on Alibaba
                </a>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-4 divide-x divide-gray-200">
        <div class="text-right pr-4">
            <div class="text-xs text-gray-500 uppercase font-semibold">Est. Profit</div>
            <div class="text-sm font-bold text-emerald-600">R {{ number_format($grossProfit, 2) }}</div>
        </div>
        <div class="text-right pr-4">
            <div class="text-xs text-gray-500 uppercase font-semibold">Gross Margin</div>
            <div class="text-sm font-bold text-emerald-600">{{ number_format($grossMarginPct, 1) }}%</div>
        </div>
        <div class="text-right pl-4">
            <div class="text-xs text-gray-500 uppercase font-semibold">Readiness</div>
            <div class="text-sm font-bold text-emerald-600">{{ $readinessPct }}%</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
    @csrf
    @method('PATCH')

    {{-- Supplier WhatsApp Outreach Hub — see app/Services/SupplierOutreachMessageBuilder
         and SupplierContactExtractor::resolve(). The WhatsApp link is built live in the
         browser from whatever's currently in the phone field below (so it updates before
         you've even saved), preferring a manually-entered number over the rarely-found
         auto-extracted one. For the majority of listings with no phone at all, the
         Alibaba listing link (real scraped source_url, never guessed) and the copy
         button are always available as the fallback outreach route. --}}
    <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4"
         x-data="{
            phone: {{ Js::from(old('supplier_phone', $product->supplier_phone ?? '')) }},
            message: {{ Js::from($outreachMessage) }},
            copied: false,
            get waUrl() {
                let d = (this.phone || '').replace(/[^\d+]/g, '');
                if (!d) return null;
                if (d.startsWith('+')) { d = d.slice(1); }
                else if (d.startsWith('00')) { d = d.slice(2).replace(/^0+/, ''); }
                else {
                    d = d.replace(/^0+/, '');
                    if (!d.startsWith('86')) { d = '86' + d; }
                }
                return d.length >= 8 ? 'https://wa.me/' + d + '?text=' + encodeURIComponent(this.message) : null;
            },
            async copyMessage() {
                try {
                    await navigator.clipboard.writeText(this.message);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                } catch (e) {
                    window.prompt('Copy this message manually (Ctrl/Cmd+C, then Enter):', this.message);
                }
            },
         }">
        <h2 class="font-semibold text-sm text-gray-900">Supplier WhatsApp Outreach</h2>

        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Supplier WhatsApp / Phone</label>
            <input type="text" name="supplier_phone" x-model="phone" placeholder="e.g. +86 138 0013 8000"
                   class="w-full text-sm font-mono border-gray-300 rounded-md shadow-sm">
            <p class="text-[11px] text-gray-400 mt-1">Spaces, hyphens, and a leading + are all fine — everything else is stripped automatically. A number with no country code is assumed Chinese mainland (+86). Save the form to keep this on file.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <a :href="waUrl" x-show="waUrl" x-cloak target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg shadow-sm transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
                💬 Reach Out on WhatsApp for Raw Video
            </a>
            <span x-show="!waUrl" x-cloak class="inline-flex items-center gap-2 bg-gray-100 text-gray-400 font-semibold text-sm px-4 py-2.5 rounded-lg select-none">
                💬 Enter a phone number above to unlock a direct WhatsApp link
            </span>

            <button type="button" @click="copyMessage()"
                    class="inline-flex items-center gap-2 border border-gray-300 hover:border-emerald-400 hover:bg-emerald-50 text-gray-700 font-semibold text-sm px-4 py-2.5 rounded-lg transition">
                <span x-show="!copied">📋 Copy Bilingual Outreach Message</span>
                <span x-show="copied" x-cloak class="text-emerald-700">✓ Copied — paste into TradeManager or WhatsApp</span>
            </button>

            @if ($product->source_url)
                <a href="{{ $product->source_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 border border-orange-300 hover:bg-orange-50 text-orange-700 font-semibold text-sm px-4 py-2.5 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Open Alibaba Listing / TradeManager Chat
                </a>
            @else
                <span class="inline-flex items-center gap-2 bg-gray-100 text-gray-400 font-semibold text-sm px-4 py-2.5 rounded-lg select-none">No Alibaba listing URL on file either</span>
            @endif
        </div>
    </div>


    {{-- Supplier Direct Connect — quick actions for the direct-contact detail on
         this product (see App\Services\SupplierContactExtractor). Each field below
         is a value an admin looked up in Alibaba's own TradeManager / company
         profile and typed in, OR one `suppliers:extract-contacts` found already
         sitting in the product's stored listing text. The wa.me / WeChat / mailto
         actions are built live in the browser from whatever's currently in the
         fields, so they work before you've even saved. Nothing here is fetched
         from Alibaba or guessed — empty is the honest default for most listings. --}}
    <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4"
         x-data="{
            whatsapp: {{ Js::from(old('supplier_whatsapp', $product->supplier_whatsapp ?? '')) }},
            wechat: {{ Js::from(old('supplier_wechat_id', $product->supplier_wechat_id ?? '')) }},
            demoMessage: {{ Js::from($demoVideoMessage) }},
            copiedWechat: false,
            copiedDemo: false,
            showDemo: false,
            get waUrl() {
                let d = (this.whatsapp || '').replace(/[^\d+]/g, '');
                if (!d) return null;
                if (d.startsWith('+')) { d = d.slice(1); }
                else if (d.startsWith('00')) { d = d.slice(2).replace(/^0+/, ''); }
                else { d = d.replace(/^0+/, ''); if (!d.startsWith('86')) { d = '86' + d; } }
                return d.length >= 8 ? 'https://wa.me/' + d : null;
            },
            get demoUrl() { return this.waUrl ? this.waUrl + '?text=' + encodeURIComponent(this.demoMessage) : null; },
            async copy(text, flag) {
                try { await navigator.clipboard.writeText(text); this[flag] = true; setTimeout(() => this[flag] = false, 2000); }
                catch (e) { window.prompt('Copy manually (Ctrl/Cmd+C, then Enter):', text); }
            },
         }">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <h2 class="font-semibold text-sm text-gray-900">Supplier Direct Connect</h2>
            <span class="text-[11px] text-gray-400">Manually sourced / found in listing text — never fetched from Alibaba</span>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Sales Rep / Manager Name</label>
                <input type="text" name="supplier_contact_name" value="{{ old('supplier_contact_name', $product->supplier_contact_name) }}"
                       placeholder="e.g. Lucy Zhang" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">WhatsApp Number</label>
                <input type="text" name="supplier_whatsapp" x-model="whatsapp" placeholder="e.g. +86 138 0013 8000"
                       class="w-full text-sm font-mono border-gray-300 rounded-md shadow-sm">
                @if (empty($product->supplier_whatsapp) && $supplierContact['whatsapp'])
                    <p class="text-[11px] text-amber-600 mt-1">Found in listing text: <span class="font-mono">{{ $supplierContact['whatsapp'] }}</span> — type it above and save to keep.</p>
                @endif
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">WeChat ID</label>
                <input type="text" name="supplier_wechat_id" x-model="wechat" placeholder="e.g. farmtech_lucy"
                       class="w-full text-sm font-mono border-gray-300 rounded-md shadow-sm">
                @if (empty($product->supplier_wechat_id) && $supplierContact['wechat_id'])
                    <p class="text-[11px] text-amber-600 mt-1">Found in listing text: <span class="font-mono">{{ $supplierContact['wechat_id'] }}</span> — type it above and save to keep.</p>
                @endif
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Direct Sales Email</label>
                <input type="email" name="supplier_email" value="{{ old('supplier_email', $product->supplier_email) }}"
                       placeholder="sales@supplier.com" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                @if (empty($product->supplier_email) && $supplierContact['email'])
                    <p class="text-[11px] text-amber-600 mt-1">Found in listing text: <span class="font-mono">{{ $supplierContact['email'] }}</span> — type it above and save to keep.</p>
                @endif
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Media Drive URL (Dropbox / Google Drive)</label>
                <input type="url" name="supplier_media_url" value="{{ old('supplier_media_url', $product->supplier_media_url) }}"
                       placeholder="https://drive.google.com/..." class="w-full text-sm border-gray-300 rounded-md shadow-sm">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100">
            <a :href="waUrl" x-show="waUrl" x-cloak target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg shadow-sm transition mt-2">
                💬 Click-to-Chat on WhatsApp
            </a>

            <a :href="demoUrl" x-show="demoUrl" x-cloak target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 border border-emerald-300 hover:bg-emerald-50 text-emerald-800 font-semibold text-sm px-4 py-2.5 rounded-lg transition mt-2">
                🎬 Request High-Res Demo Videos
            </a>
            <button type="button" @click="copy(demoMessage, 'copiedDemo')"
                    class="inline-flex items-center gap-2 border border-gray-300 hover:border-emerald-400 hover:bg-emerald-50 text-gray-700 font-semibold text-sm px-4 py-2.5 rounded-lg transition mt-2">
                <span x-show="!copiedDemo">📋 Copy Demo-Video Request</span>
                <span x-show="copiedDemo" x-cloak class="text-emerald-700">✓ Copied</span>
            </button>
            <button type="button" @click="showDemo = !showDemo"
                    class="text-xs text-gray-500 hover:text-gray-800 underline mt-2" x-text="showDemo ? 'Hide message' : 'Preview message'"></button>

            <button type="button" @click="copy(wechat, 'copiedWechat')" x-show="wechat" x-cloak
                    class="inline-flex items-center gap-2 border border-gray-300 hover:border-emerald-400 hover:bg-emerald-50 text-gray-700 font-semibold text-sm px-4 py-2.5 rounded-lg transition mt-2">
                <span x-show="!copiedWechat">📋 Copy WeChat ID</span>
                <span x-show="copiedWechat" x-cloak class="text-emerald-700">✓ Copied</span>
            </button>

            @if ($product->supplier_email || $supplierContact['email'])
                <a href="mailto:{{ $product->supplier_email ?: $supplierContact['email'] }}?subject={{ rawurlencode('High-res demo videos for '.$product->title) }}"
                   class="inline-flex items-center gap-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold text-sm px-4 py-2.5 rounded-lg transition mt-2">
                    ✉️ Email Sales Rep
                </a>
            @endif
            @if ($product->supplier_media_url)
                <a href="{{ $product->supplier_media_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 border border-blue-300 hover:bg-blue-50 text-blue-700 font-semibold text-sm px-4 py-2.5 rounded-lg transition mt-2">
                    📁 Open Media Drive
                </a>
            @endif
        </div>

        <div x-show="showDemo" x-cloak>
            <textarea readonly rows="6" x-text="demoMessage"
                      class="w-full text-xs font-mono bg-gray-50 border-gray-200 rounded-md"></textarea>
        </div>

        <p x-show="!waUrl" x-cloak class="text-[11px] text-gray-400">
            Enter a WhatsApp number above to unlock the click-to-chat and demo-video request links.
        </p>
    </div>


    <div class="grid lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
                <h2 class="font-semibold text-sm flex items-center justify-between">
                    <span>Product Images</span>
                    <span class="text-xs text-emerald-600">✓ Supplier Sourced ({{ $product->images->count() }})</span>
                </h2>
                @if ($product->images->isNotEmpty())
                    <div class="w-full aspect-square bg-gray-50 border border-gray-200 rounded flex items-center justify-center p-2">
                        <img id="gallery-main-img" src="{{ $product->images->first()->url }}" class="max-h-56 w-full object-contain rounded" alt="">
                    </div>
                    <div class="grid grid-cols-5 gap-1.5">
                        @foreach ($product->images as $img)
                            <button type="button" onclick="document.getElementById('gallery-main-img').src = '{{ $img->url }}';" class="aspect-square border border-gray-200 rounded p-0.5 hover:border-emerald-500">
                                <img src="{{ $img->url }}" class="w-full h-full object-cover" alt="">
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Listing video — URL found by `catalog:extract-videos` in this product's
                     own stored listing data, or pasted here manually. Saved with the form. --}}
                <div class="pt-3 border-t border-gray-100 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase text-gray-500">Listing Video</span>
                        @if ($product->has_video)
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg> On file
                            </span>
                        @else
                            <span class="text-[11px] text-gray-400">None</span>
                        @endif
                    </div>
                    @if ($product->video_url)
                        <video controls preload="none"
                               @if ($product->images->isNotEmpty()) poster="{{ $product->images->first()->url }}" @endif
                               class="w-full rounded border border-gray-200 bg-black aspect-video object-contain">
                            <source src="{{ $product->video_url }}">
                        </video>
                    @endif
                    <input type="url" name="video_url" value="{{ old('video_url', $product->video_url) }}"
                           placeholder="https://…/clip.mp4"
                           class="w-full text-xs font-mono border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            @if ($product->brand_name || $product->model_number || $product->warranty_period || !empty($product->specifications))
                <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
                    <h2 class="font-semibold text-sm">Technical Specifications &amp; Attributes</h2>
                    <div class="space-y-1 text-xs">
                        @if ($product->brand_name)
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-600">Brand</span>
                                <span class="font-medium text-gray-800">{{ $product->brand_name }}</span>
                            </div>
                        @endif
                        @if ($product->model_number)
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-600">Model</span>
                                <span class="font-mono text-gray-800">{{ $product->model_number }}</span>
                            </div>
                        @endif
                        @if ($product->warranty_period)
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-600">Supplier Warranty</span>
                                <span class="font-medium text-gray-800">{{ $product->warranty_period }}</span>
                            </div>
                        @endif
                        @if ($product->package_dimensions)
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-600">Package Dimensions (cm)</span>
                                <span class="font-mono text-gray-800">{{ $product->package_dimensions }}</span>
                            </div>
                        @endif
                        @if ($product->gross_weight_kg)
                            <div class="flex justify-between py-1">
                                <span class="text-gray-600">Gross Weight</span>
                                <span class="font-mono text-gray-800">{{ number_format($product->gross_weight_kg, 2) }} kg</span>
                            </div>
                        @endif
                    </div>
                    @if (!empty($product->specifications))
                        <table class="w-full text-xs mt-2">
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($product->specifications as $key => $value)
                                    <tr>
                                        <td class="py-1.5 pr-2 text-gray-500 w-2/5 align-top">{{ \App\Support\SpecLabelHumanizer::humanize($key) }}</td>
                                        <td class="py-1.5 font-mono text-gray-800">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
                <div class="flex items-center justify-between border-b pb-2">
                    <h2 class="font-semibold text-sm">Publishing Readiness</h2>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                        {{ $readyToPublish ? 'Ready to Publish' : 'Blockers Exist' }}
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-600">Supplier Identity</span>
                        <span class="font-medium text-emerald-600">✓ Alibaba Vetted</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-600">Technical Specs</span>
                        <span class="font-medium text-amber-600">Supplier Claimed</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-600">ICASA / Radio Status</span>
                        @if ($isRadio)
                            <span class="font-medium text-amber-700">{{ $product->radio_frequency_confirmed ? 'Confirmed' : 'Verification Required' }}</span>
                        @else
                            <span class="text-gray-400">Not Applicable</span>
                        @endif
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-600">Technical Datasheet</span>
                        <span class="font-medium text-emerald-600">{{ $product->datasheet_uploaded ? 'Attached' : 'Pending PDF' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-600">Landed Cost Breakdown</span>
                        <span class="font-medium text-emerald-600">✒I Calculated</span>
                    </div>
                </div>

                @if (count($blockers) > 0)
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded text-xs text-amber-900 space-y-1">
                        <strong class="font-semibold">Action Required Before Publishing:</strong>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($blockers as $block)
                                <li>{{ $block }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>


        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                <h2 class="font-semibold text-sm text-gray-900">Customer-Facing Copy</h2>
                
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Commercial Title</label>
                    <input type="text" name="title" value="{{ old('title', $product->title) }}" class="w-full text-sm font-medium border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Functional Use-Case Description</label>
                    <textarea name="short_description" rows="3" class="w-full text-sm border-gray-300 rounded-md shadow-sm">{{ old('short_description', $product->short_description) }}</textarea>
                </div>


                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">What's Included (Manifest)</label>
                        <textarea name="included_items" rows="3" class="w-full text-xs font-mono border-gray-300 rounded-md shadow-sm">{{ is_array($product->included_items) ? implode("\n", $product->included_items) : $product->included_items }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">What You Need (Requirements)</label>
                        <textarea name="requirements_notes" rows="3" class="w-full text-xs border-gray-300 rounded-md shadow-sm">{{ old('requirements_notes', $product->requirements_notes) }}</textarea>
                    </div>
                </div>
            </div>


            <div class="bg-gray-50/60 border border-gray-200 rounded-lg p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-200 pb-2">
                    <h2 class="font-semibold text-sm text-gray-900">Commercial & Profitability Guide</h2>
                    <span class="text-xs font-medium text-emerald-700">Commercial Breakdown</span>
                </div>

                <div class="grid md:grid-cols-4 gap-3 text-xs">
                    <div class="p-2.5 bg-white border border-gray-200 rounded">
                        <div class="text-gray-500">Supplier Cost (USD)</div>
                        <div class="font-semibold text-gray-800 mt-0.5">${{ number_format($product->original_price_usd ?? 50, 2) }}</div>
                    </div>
                    <div class="p-2.5 bg-white border border-gray-200 rounded">
                        <div class="text-gray-500">Intl. Freight</div>
                        <div class="font-semibold text-gray-800 mt-0.5">R {{ number_format($breakdown['intl_freight_zar'], 2) }}</div>
                    </div>
                    <div class="p-2.5 bg-white border border-gray-200 rounded">
                        <div class="text-gray-500">Customs & VAT</div>
                        <div class="font-semibold text-gray-800 mt-0.5">R {{ number_format($breakdown['customs_vat_zar'], 2) }}</div>
                    </div>
                    <div class="p-2.5 bg-white border-gray-200 rounded">
                        <div class="text-gray-500">True Landed Cost</div>
                        <div class="font-bold text-gray-900 mt-0.5">R {{ number_format($trueLandedCost, 2) }}</div>
                    </div>
                </div>


                <div class="grid md:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Selling Price (ZAR)</label>
                        <input type="number" step="0.01" name="retail_price_zar" value="{{ old('retail_price_zar', $sellingPrice) }}" class="w-full text-sm font-bold border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Stock Designation</label>
                        <select name="stock_availability_type" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="available_to_order" {{ $product->stock_availability_type === 'available_to_order' ? 'selected' : '' }}>Available to Order (Import)</option>
                            <option value="in_stock_local" {{ $product->stock_availability_type === 'in_stock_local' ? 'selected' : '' }}>In Stock (SA) - Physical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Est. Delivery</label>
                        <input type="text" name="lead_time_days" value="{{ old('lead_time_days', $product->lead_time_days) }}" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            </div>

            @if ($product->variants->isNotEmpty())
                <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
                    <h2 class="font-semibold text-sm text-gray-900">Variant Pricing Breakdown ({{ $product->variants->count() }})</h2>
                    <table class="w-full text-xs">
                        <thead class="text-gray-500 uppercase text-[10px]">
                            <tr>
                                <th class="text-left py-1.5 pr-2">Variant</th>
                                <th class="text-right py-1.5 px-2">Supplier USD</th>
                                <th class="text-right py-1.5 px-2">Landed (ZAR)</th>
                                <th class="text-right py-1.5 px-2">Selling (ZAR)</th>
                                <th class="text-right py-1.5 pl-2">Est. Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($product->variants as $variant)
                                <tr class="{{ $variant->is_default ? 'bg-emerald-50/50' : '' }}">
                                    <td class="py-1.5 pr-2 text-gray-800">
                                        {{ $variant->option_name }}
                                        @if ($variant->is_default) <span class="text-emerald-600 font-semibold">(default)</span> @endif
                                    </td>
                                    <td class="text-right py-1.5 px-2 font-mono text-gray-700">${{ number_format($variant->supplier_cost_usd, 2) }}</td>
                                    <td class="text-right py-1.5 px-2 font-mono text-gray-700">R{{ number_format($variant->landed_cost_zar, 2) }}</td>
                                    <td class="text-right py-1.5 px-2 font-mono font-semibold text-gray-900">R{{ number_format($variant->retail_price_zar, 2) }}</td>
                                    <td class="text-right py-1.5 pl-2 font-mono text-emerald-700">R{{ number_format($variant->est_profit_zar, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($product->reviews->isNotEmpty())
                <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
                    <h2 class="font-semibold text-sm text-gray-900 flex items-center justify-between">
                        <span>Supplier Reviews ({{ $product->reviews->count() }})</span>
                        <span class="text-xs text-gray-500">Avg {{ number_format($product->average_rating, 1) }}/5</span>
                    </h2>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach ($product->reviews as $review)
                            <div class="text-xs border-b border-gray-50 pb-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-800">{{ $review->author_name }}</span>
                                    <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                                </div>
                                @if ($review->review_text)
                                    <p class="text-gray-600 mt-0.5">{{ $review->review_text }}</p>
                                @endif
                                @if ($review->review_date)
                                    <p class="text-gray-400 mt-0.5">{{ $review->review_date->format('d M Y') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
                <h2 class="font-semibold text-sm text-gray-900">Technical & Regulatory Checks</h2>
                <div class="space-y-2 text-xs">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="radio_frequency_confirmed" value="1" {{ $product->radio_frequency_confirmed ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600">
                        <span>Radio Frequency plan (EU 868 / AS 923 / ISO 11784) confirmed for SA use</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="datasheet_uploaded" value="1" {{ $product->datasheet_uploaded ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600">
                        <span>Official manufacturer datasheet / user manual on file</span>
                    </label>
                </div>
            </div>
        </div>
    </div>


    <div class="sticky bottom-0 bg-white/95 backdrop-blur-md border-t border-gray-200 py-3.5 px-6 mt-8 -mx-6 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-2 text-xs">
            @if ($readyToPublish)
                <span class="font-medium text-emerald-700">✓ All mandatory vetting requirements met. Ready to publish.</span>
            @else
                <span class="font-medium text-amber-800">⚠ {{ count($blockers) }} blocking issues require attention before publishing.</span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.index') }}" class="px-3 py-2 text-xs text-gray-600 hover:text-gray-900">← Back to Queue</a>

            @if (in_array($product->status, ['archived', 'rejected', 'rejected_uncompetitive']))
                <form method="POST" action="{{ route('admin.products.relist', $product) }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100">
                        Put Back Up for Review
                    </button>
                </form>
            @endif

            @if ($product->status !== 'archived')
                <form method="POST" action="{{ route('admin.products.archive', $product) }}" onsubmit="return confirm('Remove this listing from the store and archive it?');">
                    @csrf
                    <button type="submit" class="px-3 py-2 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-100">
                        Remove Listing
                    </button>
                </form>
            @endif

            <button type="submit" name="action" value="save" class="px-4 py-2 bg-gray-200 text-gray-800 text-xs font-medium rounded-md hover:bg-gray-300">
                Save Draft
            </button>
            <button type="submit" name="action" value="publish" class="px-5 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-md hover:bg-emerald-700 shadow-sm">
                Publish to Farmtech Store
            </button>
        </div>
    </div>
</form>
@endsection
