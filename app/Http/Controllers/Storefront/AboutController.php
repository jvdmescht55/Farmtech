<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

class AboutController extends Controller
{
    public function index()
    {
        return view('storefront.about');
    }
}
