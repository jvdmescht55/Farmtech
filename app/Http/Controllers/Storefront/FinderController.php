<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\Industry;
use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;

class FinderController extends Controller
{
    /**
     * A 3-step guided picker (industry -> goal/category -> results) built
     * entirely from real Industry/ProductCategory data — every "goal" is a
     * real category with real products behind it, and the results step is
     * just a link into the existing, already-tested /category page rather
     * than a second product-listing implementation.
     */
    public function index()
    {
        $industries = collect(Industry::cases())->map(fn (Industry $industry) => [
            'value' => $industry->value,
            'label' => $industry->label(),
            'goals' => collect($industry->categories())->map(fn ($category) => [
                'label' => $category->label(),
                'description' => $category->description(),
                'icon' => $category->icon(),
                'url' => route('category.show', $category),
            ])->all(),
        ]);

        // Same real-icon-lookup-table pattern as the trending strip — goals
        // render client-side from JSON, so the icon SVG has to travel with them.
        $iconSvgs = collect(ProductCategory::cases())->unique(fn (ProductCategory $c) => $c->icon())
            ->mapWithKeys(fn (ProductCategory $c) => [$c->icon() => (string) view('components.category-icon', ['icon' => $c->icon(), 'class' => 'w-4.5 h-4.5'])]);

        return view('storefront.finder', ['industries' => $industries, 'iconSvgs' => $iconSvgs]);
    }
}
