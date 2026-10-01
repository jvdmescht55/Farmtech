<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    public const PAGES = [
        'terms' => 'Terms of use',
        'sale' => 'Terms of sale',
        'returns' => 'Returns, cooling-off & warranty',
        'shipping' => 'Delivery',
        'privacy' => 'Privacy (POPIA)',
        'cookies' => 'Cookies',
        'data' => 'Your farm data & device connections',
        'paia' => 'PAIA & information requests',
        'icasa' => 'Radio equipment (ICASA)',
    ];

    public function index()
    {
        return view('legal.index');
    }

    public function show(string $page)
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        return view('legal.'.$page, ['title' => self::PAGES[$page], 'page' => $page]);
    }
}
