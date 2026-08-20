<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

class PolicyController extends Controller
{
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
}
