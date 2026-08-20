<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

class HowItWorksController extends Controller
{
    public function index()
    {
        return view('storefront.how-it-works');
    }
}
