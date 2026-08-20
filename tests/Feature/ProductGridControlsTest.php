<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductGridControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_sort_is_newest_first(): void
    {
        $older = Product::factory()->create(['category' => ProductCategory::Scales, 'created_at' => now()->subDays(2)]);
        $newer = Product::factory()->create(['category' => ProductCategory::Scales, 'created_at' => now()->subDay()]);

        $response = $this->get(route('category.show', ProductCategory::Scales));

        $products = $response->viewData('products');
        $this->assertSame($newer->id, $products->first()->id);
        $this->assertSame($older->id, $products->last()->id);
    }

    public function test_sort_by_price_low_to_high(): void
    {
        $expensive = Product::factory()->create(['category' => ProductCategory::Scales, 'retail_price_zar' => 5000]);
        $cheap = Product::factory()->create(['category' => ProductCategory::Scales, 'retail_price_zar' => 500]);

        $response = $this->get(route('category.show', ProductCategory::Scales).'?sort=price_asc');

        $products = $response->viewData('products');
        $this->assertSame($cheap->id, $products->first()->id);
        $this->assertSame($expensive->id, $products->last()->id);
    }

    public function test_sort_by_price_high_to_low(): void
    {
        $expensive = Product::factory()->create(['category' => ProductCategory::Scales, 'retail_price_zar' => 5000]);
        $cheap = Product::factory()->create(['category' => ProductCategory::Scales, 'retail_price_zar' => 500]);

        $response = $this->get(route('category.show', ProductCategory::Scales).'?sort=price_desc');

        $products = $response->viewData('products');
        $this->assertSame($expensive->id, $products->first()->id);
        $this->assertSame($cheap->id, $products->last()->id);
    }

    public function test_in_stock_filter_excludes_out_of_stock_products(): void
    {
        $inStock = Product::factory()->create(['category' => ProductCategory::Scales, 'stock_status' => 'in_stock']);
        $preOrder = Product::factory()->create(['category' => ProductCategory::Scales, 'stock_status' => 'pre_order']);

        $response = $this->get(route('category.show', ProductCategory::Scales).'?in_stock=1');

        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $inStock->id));
        $this->assertFalse($products->contains('id', $preOrder->id));
    }

    public function test_showing_count_control_bar_renders(): void
    {
        Product::factory()->count(3)->create(['category' => ProductCategory::Scales]);

        $this->get(route('category.show', ProductCategory::Scales))
            ->assertSee('Showing 1&ndash;3 of 3', false);
    }

    public function test_invalid_sort_value_falls_back_to_newest_instead_of_erroring(): void
    {
        Product::factory()->create(['category' => ProductCategory::Scales]);

        $this->get(route('category.show', ProductCategory::Scales).'?sort=not-a-real-sort')->assertOk();
    }
}
