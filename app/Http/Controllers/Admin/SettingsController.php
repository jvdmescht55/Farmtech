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
            'whatsapp' => Setting::get('support_whatsapp', ''),
            // Read-only: secrets live in .env (then `php artisan config:cache`).
            'checks' => [
                ['Email (orders, password resets)', config('mail.default') !== 'log', 'MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS'],
                ['EFT banking details', filled(config('shop.bank.account_number')), 'SHOP_BANK_NAME, SHOP_BANK_ACCOUNT_NAME, SHOP_BANK_ACCOUNT_NUMBER, SHOP_BANK_BRANCH_CODE'],
                ['Card payments (PayFast)', filled(config('services.payfast.merchant_id')) && filled(config('services.payfast.merchant_key')), 'PAYFAST_MERCHANT_ID, PAYFAST_MERCHANT_KEY, PAYFAST_PASSPHRASE, PAYFAST_SANDBOX=false'],
                ['Courier fee', config('shop.courier_cents') !== null, 'SHOP_COURIER_CENTS (e.g. 15000 = R150), SHOP_FREE_COURIER_OVER_CENTS'],
                ['Collection point', filled(config('shop.collect_from')), 'SHOP_COLLECT_FROM'],
                ['Company details on legal pages', filled(config('legal.legal_name')), 'LEGAL_NAME, LEGAL_REG_NO, LEGAL_ADDRESS … (see config/legal.php)'],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['support_whatsapp' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/']]);
        Setting::set('support_whatsapp', $data['support_whatsapp'] ?? '', 'string');

        return back()->with('status', 'Settings saved.');
    }
}
