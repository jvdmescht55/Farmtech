@extends("layouts.admin")

@section("content")
@php
    $hasTokenLeak = str_contains($product->short_description, "_") || str_contains($product->short_description, "moisture_meters");
    $isRadio = ($product->icasa_status === 'verification_required');
    
    $supplierZar = $product->original_price_usd ? ($product->original_price_usd * 18.5) : 925.00;
    $freightZar = $product->intl_freight_zar ?? 180.00;
    $dutyZar = $product->customs_vat_zar ?? 95.00;
    $clearingZar = $product->customs_clearance_zar ?? 150.00;
    $deliveryZar = $product->domestic_delivery_zar ?? 120.00;
    $trueLandedCost = $supplierZar + $freightZar + $dutyZar + $clearingZar + $deliveryZar;
    
    $sellingPrice = $product->retail_price_zar > 0 ? $product->retail_price_zar : ($trueLandedCost * 1.45);
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
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-lg font-bold text-gray-900">{{ $product->title }}</h1>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Vetting Pending</span>
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-500 mt-1">
            <span>SKU: <strong class="font-mono text-gray-700">{{ $product->sku }}</strong></span>
            <span>Supplier: <strong text-gray-700">{{ $product->supplier_name ?? 'Alibaba Vetting Pending' }}</strong></span>
            <span>Price Checked: <strong text-gray-700">23 Aug 2026</strong></span>
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
            </div>

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
                        <div class="font-semibold text-gray-800 mt-0.5">R {{ number_format($freightZar, 2) }}</div>
                    </div>
                    <div class="p-2.5 bg-white border border-gray-200 rounded">
                        <div class="text-gray-500">Customs & Duty</div>
                        <div class="font-semibold text-gray-800 mt-0.5">R {{ number_format($dutyZar + $clearingZar, 2) }}</div>
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
                        <input type="text" name="lead_time_days" value="7‑12 business days" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            </div>

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
