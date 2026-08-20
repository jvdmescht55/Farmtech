<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\ProductSpec;
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

    public function test_spec_filter_narrows_results_to_matching_value(): void
    {
        $big = Product::factory()->create(['category' => ProductCategory::Scales]);
        ProductSpec::create(['product_id' => $big->id, 'spec_group' => 'Performance', 'spec_key' => 'Capacity', 'spec_value' => '3000 kg', 'is_highlight' => true]);

        $small = Product::factory()->create(['category' => ProductCategory::Scales]);
        ProductSpec::create(['product_id' => $small->id, 'spec_group' => 'Performance', 'spec_key' => 'Capacity', 'spec_value' => '500 kg', 'is_highlight' => true]);

        $response = $this->get(route('category.show', ProductCategory::Scales).'?'.http_build_query(['spec' => ['Capacity' => ['3000 kg']]]));

        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $big->id));
        $this->assertFalse($products->contains('id', $small->id));
    }

    public function test_facets_are_built_from_real_spec_data_in_this_category_only(): void
    {
        $scale = Product::factory()->create(['category' => ProductCategory::Scales]);
        ProductSpec::create(['product_id' => $scale->id, 'spec_group' => 'Performance', 'spec_key' => 'Capacity', 'spec_value' => '3000 kg', 'is_highlight' => true]);

        $rfid = Product::factory()->create(['category' => ProductCategory::Rfid]);
        ProductSpec::create(['product_id' => $rfid->id, 'spec_group' => 'Compliance', 'spec_key' => 'Frequency', 'spec_value' => '134.2 kHz', 'is_highlight' => true]);

        $response = $this->get(route('category.show', ProductCategory::Scales));

        $facets = $response->viewData('facets');
        $this->assertTrue($facets->has('Capacity'));
        $this->assertTrue($facets->get('Capacity')->contains('3000 kg'));
        $this->assertFalse($facets->has('Frequency'));
    }

    public function test_selecting_a_spec_filter_does_not_remove_other_facet_options(): void
    {
        $a = Product::factory()->create(['category' => ProductCategory::Scales]);
        ProductSpec::create(['product_id' => $a->id, 'spec_group' => 'Performance', 'spec_key' => 'Capacity', 'spec_value' => '3000 kg', 'is_highlight' => true]);
        ProductSpec::create(['product_id' => $a->id, 'spec_group' => 'Connectivity', 'spec_key' => 'Connectivity', 'spec_value' => 'Bluetooth', 'is_highlight' => true]);

        $b = Product::factory()->create(['category' => ProductCategory::Scales]);
        ProductSpec::create(['product_id' => $b->id, 'spec_group' => 'Performance', 'spec_key' => 'Capacity', 'spec_value' => '500 kg', 'is_highlight' => true]);
        ProductSpec::create(['product_id' => $b->id, 'spec_group' => 'Connectivity', 'spec_key' => 'Connectivity', 'spec_value' => 'Wi-Fi', 'is_highlight' => true]);

        $response = $this->get(route('category.show', ProductCategory::Scales).'?'.http_build_query(['spec' => ['Capacity' => ['3000 kg']]]));

        $facets = $response->viewData('facets');
        // Even though the result set is narrowed to product A, the Connectivity
        // facet still shows both real options — filters don't erase each other.
        $this->assertTrue($facets->get('Connectivity')->contains('Bluetooth'));
        $this->assertTrue($facets->get('Connectivity')->contains('Wi-Fi'));
    }
}
