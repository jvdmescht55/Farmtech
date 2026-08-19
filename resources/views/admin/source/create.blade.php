@extends('layouts.admin')

@section('heading', 'Source New Listing')

@section('content')
    <p class="text-sm text-gray-500 mb-6 max-w-2xl">
        Paste in what you see on the supplier listing. Submitting runs the same Gemini compliance
        vetting and landed-cost pipeline as the CLI — you'll land on the review page for whatever
        it produces, whether that's staged for approval or automatically rejected.
    </p>

    @if (session('pipeline_output'))
        <div class="mb-6 bg-gray-900 text-gray-100 text-xs font-mono rounded-lg p-4 overflow-x-auto max-h-64 overflow-y-auto">
            <pre>{{ session('pipeline_output') }}</pre>
        </div>
    @endif

    <form action="{{ route('admin.source.store') }}" method="POST" class="max-w-3xl space-y-6">
        @csrf

        <fieldset class="bg-white border rounded-xl p-6 space-y-4">
            <legend class="font-semibold px-1">Listing</legend>
            <div>
                <label class="block text-sm font-medium mb-1">Title (as shown on the supplier listing)</label>
                <input type="text" name="raw_title" value="{{ old('raw_title') }}" required class="w-full border rounded-md px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Category</label>
                    <select name="category_hint" required class="w-full border rounded-md px-3 py-2">
                        <option value="">Select…</option>
                        @foreach (['scales' => 'Scales', 'ultrasound' => 'Ultrasound', 'rfid' => 'RFID', 'accessories' => 'Accessories'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('category_hint') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Stock Status</label>
                    <select name="stock_status" class="w-full border rounded-md px-3 py-2">
                        <option value="in_stock" @selected(old('stock_status') === 'in_stock')>In Stock</option>
                        <option value="pre_order" @selected(old('stock_status', 'pre_order') === 'pre_order')>Pre-Order</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Specs / description dump (paste everything you have — the AI reads this)</label>
                <textarea name="raw_specs_text" rows="5" required class="w-full border rounded-md px-3 py-2 font-mono text-sm">{{ old('raw_specs_text') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Image URLs (one per line, optional)</label>
                <textarea name="image_urls" rows="2" class="w-full border rounded-md px-3 py-2 font-mono text-xs">{{ old('image_urls') }}</textarea>
            </div>
        </fieldset>

        <fieldset class="bg-white border rounded-xl p-6 space-y-4">
            <legend class="font-semibold px-1">Supplier</legend>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Supplier Name</label>
                    <input type="text" name="supplier_name" value="{{ old('supplier_name') }}" required class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Years Trading</label>
                    <input type="number" name="supplier_years" value="{{ old('supplier_years') }}" min="0" class="w-full border rounded-md px-3 py-2">
                </div>
            </div>
            <div class="flex gap-6">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_verified_supplier" value="1" @checked(old('is_verified_supplier'))> Verified supplier badge</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="has_trade_assurance" value="1" @checked(old('has_trade_assurance'))> Trade assurance</label>
            </div>
        </fieldset>

        <fieldset class="bg-white border rounded-xl p-6 space-y-4">
            <legend class="font-semibold px-1">Pricing</legend>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Supplier Price (USD)</label>
                    <input type="number" step="0.01" name="supplier_price_usd" value="{{ old('supplier_price_usd') }}" required class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Weight (kg)</label>
                    <input type="number" step="0.001" name="weight_kg" value="{{ old('weight_kg') }}" required class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Duty Rate (0–1)</label>
                    <input type="number" step="0.01" name="duty_rate" value="{{ old('duty_rate') }}" required class="w-full border rounded-md px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Category Median Price (USD, optional)</label>
                    <input type="number" step="0.01" name="category_price_hint" value="{{ old('category_price_hint') }}" class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Lead Time</label>
                    <input type="text" name="lead_time_days" value="{{ old('lead_time_days', '7-12 business days') }}" class="w-full border rounded-md px-3 py-2">
                </div>
            </div>
        </fieldset>

        <button type="submit" class="bg-farmtech-green text-white font-semibold px-6 py-3 rounded-md hover:bg-farmtech-green-dark">
            Run Sourcing Pipeline
        </button>
    </form>
@endsection
