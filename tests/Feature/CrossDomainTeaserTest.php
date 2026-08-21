<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Also Sourced by Commercial Buyers" shows real popular products from
 * OTHER industries — never products already shown in the current category's
 * own grid above it.
 */
class CrossDomainTeaserTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_products_from_other_industries_only(): void
    {
        $inCategory = Product::factory()->create(['category' => ProductCategory::Scales]);
        $otherIndustryProduct = Product::factory()->create(['category' => ProductCategory::LaserLevels]);
        $sameIndustryOtherCategory = Product::factory()->create(['category' => ProductCategory::Rfid]);

        $response = $this->get(route('category.show', ProductCategory::Scales));

        $response->assertOk();
        $response->assertSee('Also Sourced by Commercial Buyers');
        $response->assertViewHas('crossDomain', function ($crossDomain) use ($otherIndustryProduct, $sameIndustryOtherCategory) {
            $ids = $crossDomain->pluck('id');

            return $ids->contains($otherIndustryProduct->id) && ! $ids->contains($sameIndustryOtherCategory->id);
        });
    }
}
