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
        return view('site.store', ['provinces' => self::PROVINCES, 'listings' => \App\Models\StoreListing::published()->get()]);
    }

    public function product(\App\Models\StoreListing $listing)
    {
        abort_unless($listing->is_published || auth()->user()?->canAccessAdminPanel(), 404);

        return view('site.product', ['listing' => $listing, 'provinces' => self::PROVINCES]);
    }

    public function suggest()
    {
        return view('site.suggest');
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
            'message' => ['nullable', 'string', 'max:3000'],
            'listing' => ['nullable', 'integer', 'exists:store_listings,id'],
            'consent' => ['accepted'],
            'website' => ['prohibited'], // honeypot
        ]);

        $listing = isset($data['listing']) ? \App\Models\StoreListing::find($data['listing']) : null;
        unset($data['listing'], $data['consent']);
        Lead::create($data + ['type' => $listing && $listing->price_cents ? 'order' : 'interest', 'interest' => $listing?->name ?? 'KraalTrac', 'store_listing_id' => $listing?->id]);

        return redirect()->to(url()->previous().'#order')->with('lead_ok', true);
    }

    public function sendContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:3000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ]);

        unset($data['consent']);
        Lead::create($data + ['type' => 'contact']);

        return back()->with('lead_ok', true);
    }
}
