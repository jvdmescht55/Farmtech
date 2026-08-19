<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => [
                'target_margin_pct' => Setting::get('target_margin_pct', 35),
                'air_freight_usd_per_kg' => Setting::get('air_freight_usd_per_kg', 9.5),
                'clearing_agent_fee_zar' => Setting::get('clearing_agent_fee_zar', 450),
                'vat_rate' => Setting::get('vat_rate', 0.15),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'target_margin_pct' => ['required', 'numeric', 'min:0', 'max:95'],
            'air_freight_usd_per_kg' => ['required', 'numeric', 'min:0'],
            'clearing_agent_fee_zar' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:1'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'decimal');
        }

        return back()->with('status', 'Settings saved.');
    }
}
