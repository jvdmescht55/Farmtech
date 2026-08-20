@extends('layouts.admin')

@section('heading', 'Settings')

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="POST" class="bg-white border rounded-xl p-6 max-w-lg space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Default Target Margin (%)</label>
            <input type="number" step="0.1" name="target_margin_pct" value="{{ $settings['target_margin_pct'] }}" class="w-full border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Air Freight Rate (USD / kg)</label>
            <input type="number" step="0.01" name="air_freight_usd_per_kg" value="{{ $settings['air_freight_usd_per_kg'] }}" class="w-full border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Clearing Agent Flat Fee (ZAR)</label>
            <input type="number" step="0.01" name="clearing_agent_fee_zar" value="{{ $settings['clearing_agent_fee_zar'] }}" class="w-full border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">SARS VAT Rate (fraction, e.g. 0.15)</label>
            <input type="number" step="0.01" name="vat_rate" value="{{ $settings['vat_rate'] }}" class="w-full border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Support WhatsApp Number</label>
            <input type="text" name="support_whatsapp" value="{{ $settings['support_whatsapp'] }}" placeholder="e.g. +27821234567" class="w-full border rounded-md px-3 py-2">
            <p class="text-xs text-gray-400 mt-1">Digits only (optional leading +). Leave blank to hide the WhatsApp CTA on the storefront entirely.</p>
        </div>

        <p class="text-xs text-gray-400">API keys (Gemini, exchange rate, payment gateways) are managed via the server's .env file, not here — they're too sensitive for a web form without additional encryption-at-rest work.</p>

        <button type="submit" class="bg-farmtech-green text-white font-semibold px-6 py-2 rounded-md hover:bg-farmtech-green-dark">Save Settings</button>
    </form>
@endsection
