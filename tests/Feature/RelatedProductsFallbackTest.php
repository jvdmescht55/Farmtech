<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Related Hardware & Add-Ons" falls back to real parent-industry products
 * when the same category alone has fewer than 4 — proven here rather than
 * just asserted, since a naive same-category-only query would show an
 * awkward half-empty grid on a young catalog.
 */
class RelatedProductsFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_industry_when_same_category_has_too_few(): void
    {
        // Scales is the only product in its category — Ultrasound (same
        // Agriculture industry) should fill the rest.
        $product = Product::factory()->create(['category' => ProductCategory::Scales]);
        $industryMate = Product::factory()->create(['category' => ProductCategory::Ultrasound]);
        // A different industry's product must never appear.
        Product::factory()->create(['category' => ProductCategory::LaserLevels]);

        $response = $this->get(route('products.show', $product));

        $response->assertSee($industryMate->title);
        $response->assertDontSee('Rotary Laser Levels');
    }

    public function test_does_not_fall_back_when_same_category_already_has_four(): void
    {
        $product = Product::factory()->create(['category' => ProductCategory::Scales]);
        Product::factory()->count(4)->create(['category' => ProductCategory::Scales]);
        $industryMate = Product::factory()->create(['category' => ProductCategory::Ultrasound, 'title' => 'Should Not Appear Ultrasound']);

        $response = $this->get(route('products.show', $product));

        $response->assertDontSee('Should Not Appear Ultrasound');
    }
}
