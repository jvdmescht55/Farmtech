<?php

namespace Tests\Feature;

use App\Enums\Industry;
use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndustryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_industry_page_shows_products_from_every_category_in_that_industry(): void
    {
        $scale = Product::factory()->create(['category' => ProductCategory::Scales]);
        $rfid = Product::factory()->create(['category' => ProductCategory::Rfid]);
        $laser = Product::factory()->create(['category' => ProductCategory::LaserLevels]);

        $response = $this->get(route('industry.show', Industry::Agriculture));

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $scale->id));
        $this->assertTrue($products->contains('id', $rfid->id));
        $this->assertFalse($products->contains('id', $laser->id));
    }

    public function test_industry_page_shows_a_pill_for_every_category_in_that_industry(): void
    {
        $response = $this->get(route('industry.show', Industry::Construction));

        foreach (Industry::Construction->categories() as $category) {
            $response->assertSee($category->shortLabel());
        }
    }

    public function test_industry_page_supports_sort_and_in_stock_filter(): void
    {
        $expensive = Product::factory()->create(['category' => ProductCategory::SolarPumps, 'retail_price_zar' => 5000, 'stock_status' => 'in_stock']);
        $cheap = Product::factory()->create(['category' => ProductCategory::MpptControllers, 'retail_price_zar' => 500, 'stock_status' => 'pre_order']);

        $response = $this->get(route('industry.show', Industry::SolarPower).'?sort=price_asc&in_stock=1');

        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $expensive->id));
        $this->assertFalse($products->contains('id', $cheap->id));
    }

    public function test_homepage_shows_exactly_four_industry_tiles(): void
    {
        $response = $this->get(route('home'));

        foreach (Industry::cases() as $industry) {
            $response->assertSee(route('industry.show', $industry), false);
        }
    }
}
