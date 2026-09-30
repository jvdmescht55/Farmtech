<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

/** Public marketing site: home (choose Store or Herd Management), the store/scanner page, contact. */
class SiteController extends Controller
{
    public const PROVINCES = ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape', 'Outside South Africa'];

    /** The front door: choose the shop or herd management. */
    public function gateway()
    {
        return view('site.gateway');
    }

    public function store()
    {
        return view('site.store', ['provinces' => self::PROVINCES]);
    }

    public function contact()
    {
        return view('site.contact');
    }

    public function interest(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'farm_name' => ['nullable', 'string', 'max:160'],
            'herd_size' => ['nullable', 'string', 'max:40'],
            'province' => ['nullable', 'string', 'max:60'],
            'website' => ['prohibited'], // honeypot
        ]);

        Lead::create($data + ['type' => 'interest', 'interest' => 'RFID Scanner V1']);

        return redirect()->to(route('site.store').'#bestel')->with('lead_ok', true);
    }

    public function sendContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:3000'],
            'website' => ['prohibited'],
        ]);

        Lead::create($data + ['type' => 'contact']);

        return back()->with('lead_ok', true);
    }
}
