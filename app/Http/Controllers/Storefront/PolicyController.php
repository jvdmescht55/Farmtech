<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

class PolicyController extends Controller
{
    public function shipping()
    {
        return view('storefront.policies.shipping');
    }

    public function returns()
    {
        return view('storefront.policies.returns');
    }

    public function terms()
    {
        return view('storefront.policies.terms');
    }

    public function icasaCompliance()
    {
        return view('storefront.policies.icasa-compliance');
    }

    public function privacy()
    {
        return view('storefront.policies.privacy');
    }
}
