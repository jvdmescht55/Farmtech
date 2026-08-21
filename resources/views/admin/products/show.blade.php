@extends('layouts.admin')

@section('heading', 'Review: '.$product->title)

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Specs & Compliance Sidebar --}}
        <div class="card p-6 h-fit space-y-4">
            <h2 class="font-semibold">Compliance Sidebar</h2>

            @if ($product->status === 'rejected_uncompetitive')
                <div class="bg-amber-50 border border-amber-300 text-amber-900 rounded-md px-3 py-2 text-xs">
                    <strong>Rejected — uncompetitive.</strong> This item passed compliance but the AI flagged the required selling price as likely above SA retail for this spec — not worth importing even though nothing is technically wrong with it.
                </div>
            @endif

            @if ($audit = $product->complianceAudit)
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Frequency Match</span>
                        <x-badge :color="$audit->frequencyBadge()">{{ $audit->frequency_checked ?? 'Not applicable' }}</x-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">ICASA Status</span>
                        <x-badge :color="$audit->icasaBadge()">{{ $audit->icasa_status ? ucfirst(str_replace('_', ' ', $audit->icasa_status)) : 'N/A' }}</x-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">220V/50Hz Plug</span>
                        <x-badge :color="$audit->plugBadge()">{{ $audit->plug_type_checked ? 'Confirmed' : 'Unconfirmed' }}</x-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Battery Transport Cert</span>
                        <x-badge :color="$audit->battery_transport_cert ? 'green' : 'gray'">{{ $audit->battery_transport_cert ?? 'None' }}</x-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Supplier Trust</span>
                        <x-badge :color="$audit->supplierTrustBadge()">{{ $audit->supplier_name }} ({{ $audit->supplier_years ?? '?' }}y)</x-badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Risk Score</span>
                        <span class="font-semibold">{{ $audit->risk_score }}/100</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Verdict</span>
                        <x-badge :color="$audit->audit_verdict === 'PASS' ? 'green' : ($audit->audit_verdict === 'WARN' ? 'yellow' : 'red')">{{ $audit->audit_verdict }}</x-badge>
                    </div>

                    @if (!empty($audit->rejection_reasons))
                        <div class="mt-3">
                            <p class="text-gray-500 mb-1">Flags</p>
                            <ul class="list-disc list-inside text-red-700 space-y-1">
                                @foreach ($audit->rejection_reasons as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <details class="mt-3">
                        <summary class="cursor-pointer text-gray-500">Raw AI analysis</summary>
                        <pre class="mt-2 whitespace-pre-wrap text-xs bg-gray-50 border rounded-md p-3">{{ $audit->raw_ai_analysis }}</pre>
                    </details>
                </div>
            @else
                <p class="text-gray-400 text-sm">No compliance audit recorded for this product.</p>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            {{-- Profit Breakdown --}}
            <div class="card p-6">
                <h2 class="font-semibold mb-4">Profit Breakdown</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                    <div>
                        <p class="text-gray-500">Base USD</p>
                        <p class="font-semibold">${{ number_format($product->original_price_usd, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">USD → ZAR Rate</p>
                        <p class="font-semibold">{{ number_format($usdZarRate, 4) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Duty Rate</p>
                        <p class="font-semibold">{{ number_format($product->customs_duty_rate * 100, 1) }}%</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Weight</p>
                        <p class="font-semibold">{{ $product->est_weight_kg }} kg</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center py-4 border-y">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Base Cost</p>
                        <p id="breakdown-base" class="text-sm font-bold">R{{ number_format($breakdown['base_zar'], 2) }}</p>
                        <p class="text-xs text-gray-400">${{ number_format($product->original_price_usd, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Freight + Courier</p>
                        <p id="breakdown-freight" class="text-sm font-bold">R{{ number_format($breakdown['intl_freight_zar'] + $breakdown['domestic_delivery_zar'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Duty + VAT</p>
                        <p id="breakdown-customs" class="text-sm font-bold">R{{ number_format($breakdown['customs_vat_zar'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Landed Cost</p>
                        <p id="breakdown-landed" class="text-sm font-bold">R{{ number_format($breakdown['landed_cost_zar'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Retail Price</p>
                        <p id="breakdown-retail" class="text-sm font-bold text-farmtech-green-dark">R{{ number_format($breakdown['retail_price_zar'], 2) }}</p>
                    </div>
                </div>

                <div class="mt-4 bg-farmtech-cream/60 border border-farmtech-gold/30 rounded-lg px-4 py-3 flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-700">Your Cut (Net Profit)</span>
                    <span class="text-right">
                        <span id="breakdown-profit" class="text-lg font-bold text-farmtech-green-dark">R{{ number_format($breakdown['retail_price_zar'] - $breakdown['landed_cost_zar'], 2) }}</span>
                        <span id="breakdown-profit-pct" class="text-sm text-gray-500 ml-1">({{ number_format((($breakdown['retail_price_zar'] - $breakdown['landed_cost_zar']) / max($breakdown['retail_price_zar'], 0.01)) * 100, 1) }}% margin)</span>
                    </span>
                </div>

                <div class="mt-4">
                    <label class="flex items-center justify-between text-sm font-medium mb-1">
                        <span>Target Margin</span>
                        <span id="margin-value">{{ $product->profit_margin_pct ?? 35 }}%</span>
                    </label>
                    <input type="range" id="margin-slider" min="0" max="80" step="1"
                           value="{{ $product->profit_margin_pct ?? 35 }}"
                           data-recalculate-url="{{ route('admin.products.recalculate', $product) }}"
                           class="w-full">
                </div>
            </div>

            {{-- Quick Edit --}}
            <form action="{{ route('admin.products.update', $product) }}" method="POST" class="card p-6 space-y-4">
                @csrf
                @method('PATCH')
                <h2 class="font-semibold">Quick Edit</h2>
                <div>
                    <label class="block text-sm font-medium mb-1">Title</label>
                    <input type="text" name="title" value="{{ $product->title }}" class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Short Description</label>
                    <textarea name="short_description" rows="2" class="w-full border rounded-md px-3 py-2">{{ $product->short_description }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Equipment Manifest — one item per line</label>
                    <textarea name="included_items" rows="4" placeholder="e.g. 4-wire load cell probe&#10;12V power adapter&#10;Calibration certificate" class="w-full border rounded-md px-3 py-2 font-mono text-sm">{{ old('included_items', $product->included_items ? implode("\n", $product->included_items) : '') }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Shown on the product page's "What's Included" tab. Leave blank if contents aren't confirmed yet — the page will say so honestly rather than guessing.</p>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Retail Price (ZAR)</label>
                        <input type="number" step="0.01" name="retail_price_zar" id="retail_price_zar_input" value="{{ $product->retail_price_zar ?? $breakdown['retail_price_zar'] }}" class="w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Margin %</label>
                        <input type="number" step="0.1" name="profit_margin_pct" id="profit_margin_pct_input" value="{{ $product->profit_margin_pct ?? 35 }}" class="w-full border rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Stock Status</label>
                        <select name="stock_status" class="w-full border rounded-md px-3 py-2">
                            <option value="in_stock" @selected($product->stock_status === 'in_stock')>In Stock</option>
                            <option value="pre_order" @selected($product->stock_status === 'pre_order')>Pre-Order</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Lead Time</label>
                    <input type="text" name="lead_time_days" value="{{ $product->lead_time_days }}" class="w-full border rounded-md px-3 py-2">
                </div>

                <div class="border-t pt-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Inventory</p>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Stock Quantity</label>
                            <input type="number" name="stock_quantity" value="{{ $product->stock_quantity }}" placeholder="Untracked" class="w-full border rounded-md px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Blank = not tracked (unlimited)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Low Stock Alert Below</label>
                            <input type="number" name="low_stock_threshold" value="{{ $product->low_stock_threshold }}" min="0" class="w-full border rounded-md px-3 py-2">
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="allow_backorder" value="1" @checked($product->allow_backorder)>
                                Allow backorder
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="bg-gray-800 text-white font-semibold px-5 py-2 rounded-md hover:bg-gray-900">Save Changes</button>
            </form>

            {{-- Single-Click Actions --}}
            <div class="flex gap-3">
                <form action="{{ route('admin.products.approve', $product) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white font-semibold px-5 py-2 rounded-md hover:bg-green-700">Approve &amp; Publish</button>
                </form>

                <form action="{{ route('admin.products.reject', $product) }}" method="POST" onsubmit="return confirm('Reject this product?');" class="flex items-center gap-2">
                    @csrf
                    <label class="flex items-center gap-1 text-sm text-gray-500">
                        <input type="checkbox" name="blacklist_supplier" value="1"> Blacklist supplier
                    </label>
                    <button type="submit" class="bg-red-600 text-white font-semibold px-5 py-2 rounded-md hover:bg-red-700">Reject</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const slider = document.getElementById('margin-slider');
        const marginValue = document.getElementById('margin-value');
        const marginInput = document.getElementById('profit_margin_pct_input');
        const retailInput = document.getElementById('retail_price_zar_input');

        slider.addEventListener('input', async () => {
            marginValue.textContent = slider.value + '%';
            marginInput.value = slider.value;

            const response = await fetch(slider.dataset.recalculateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ margin_pct: slider.value }),
            });

            if (!response.ok) return;
            const data = await response.json();
            const fmt = (n) => 'R' + Number(n).toLocaleString('en-ZA', { minimumFractionDigits: 2 });

            document.getElementById('breakdown-base').textContent = fmt(data.base_zar);
            document.getElementById('breakdown-freight').textContent = fmt(data.intl_freight_zar + data.domestic_delivery_zar);
            document.getElementById('breakdown-customs').textContent = fmt(data.customs_vat_zar);
            document.getElementById('breakdown-landed').textContent = fmt(data.landed_cost_zar);
            document.getElementById('breakdown-retail').textContent = fmt(data.retail_price_zar);

            const profit = data.retail_price_zar - data.landed_cost_zar;
            const profitPct = data.retail_price_zar > 0 ? (profit / data.retail_price_zar) * 100 : 0;
            document.getElementById('breakdown-profit').textContent = fmt(profit);
            document.getElementById('breakdown-profit-pct').textContent = '(' + profitPct.toFixed(1) + '% margin)';

            retailInput.value = data.retail_price_zar;
        });
    </script>
@endsection
