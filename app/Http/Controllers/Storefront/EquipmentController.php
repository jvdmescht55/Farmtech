<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\Industry;
use App\Http\Controllers\Controller;

class EquipmentController extends Controller
{
    /**
     * Traditional catalogue browse: all 15 real categories grouped under
     * their 4 real industries — the mega-menu's own grouping, as a full page
     * rather than a hover-only dropdown, for buyers who'd rather scan than
     * search.
     */
    public function index()
    {
        return view('storefront.equipment', [
            'industries' => Industry::cases(),
        ]);
    }
}
