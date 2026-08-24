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
                'support_whatsapp' => Setting::get('support_whatsapp', ''),
                'auto_publish_cap_per_category' => Setting::get('auto_publish_cap_per_category', 2),
            ],
        ]);
    }

    public function update(Request $request)
    {
        // Blank the field to clear the number rather than tripping the format
        // rule — an empty WhatsApp CTA is a valid "not configured yet" state.
        if ($request->input('support_whatsapp') === '') {
            $request->merge(['support_whatsapp' => null]);
        }

        $validated = $request->validate([
            'target_margin_pct' => ['required', 'numeric', 'min:0', 'max:95'],
            'air_freight_usd_per_kg' => ['required', 'numeric', 'min:0'],
            'clearing_agent_fee_zar' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            // E.164-ish: optional leading +, 8-15 digits — matches wa.me's own accepted format.
            'support_whatsapp' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            // How many products the AI auto-publish command will put live per
            // category on its own, without a human approving each one.
            'auto_publish_cap_per_category' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'support_whatsapp') {
                Setting::set($key, $value ?? '', 'string');

                continue;
            }

            if ($key === 'auto_publish_cap_per_category') {
                Setting::set($key, $value, 'integer');

                continue;
            }

            Setting::set($key, $value, 'decimal');
        }

        return back()->with('status', 'Settings saved.');
    }
}
