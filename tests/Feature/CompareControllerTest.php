<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The compare drawer needs real spec data aligned by spec_key across up to 3
 * products — these tests prove the endpoint actually does that alignment
 * (shared keys first, missing keys left absent rather than invented) and
 * respects the same storefront-visibility rule as everywhere else.
 */
class CompareControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_requested_products_with_their_specs(): void
    {
        $a = Product::factory()->create(['title' => 'Scale A']);
        $b = Product::factory()->create(['title' => 'Scale B']);
        ProductSpec::create(['product_id' => $a->id, 'spec_group' => 'Performance', 'spec_key' => 'Capacity', 'spec_value' => '3000 kg', 'is_highlight' => true]);
        ProductSpec::create(['product_id' => $b->id, 'spec_group' => 'Performance', 'spec_key' => 'Capacity', 'spec_value' => '1500 kg', 'is_highlight' => true]);

        $response = $this->getJson(route('compare.data', ['ids' => "{$a->id},{$b->id}"]));

        $response->assertOk();
        $response->assertJsonCount(2, 'products');
        $response->assertJsonFragment(['title' => 'Scale A']);
        $response->assertJsonFragment(['title' => 'Scale B']);
        $response->assertJsonPath('products.0.specs.Capacity', '3000 kg');
        $response->assertJsonPath('products.1.specs.Capacity', '1500 kg');
        $response->assertJsonPath('specKeys.0', 'Capacity');
    }

    public function test_a_spec_key_only_one_product_has_is_not_invented_for_the_other(): void
    {
        $a = Product::factory()->create();
        $b = Product::factory()->create();
        ProductSpec::create(['product_id' => $a->id, 'spec_group' => 'Connectivity', 'spec_key' => 'Interface', 'spec_value' => 'RS-485', 'is_highlight' => false]);

        $response = $this->getJson(route('compare.data', ['ids' => "{$a->id},{$b->id}"]));

        $response->assertOk();
        $products = collect($response->json('products'));
        $productB = $products->firstWhere('id', $b->id);
        $this->assertArrayNotHasKey('Interface', $productB['specs']);
    }

    public function test_caps_at_three_products_even_if_more_ids_are_requested(): void
    {
        $products = Product::factory()->count(4)->create();

        $response = $this->getJson(route('compare.data', ['ids' => $products->pluck('id')->implode(',')]));

        $response->assertOk();
        $response->assertJsonCount(3, 'products');
    }

    public function test_excludes_products_that_are_not_storefront_visible(): void
    {
        $visible = Product::factory()->create();
        $pending = Product::factory()->pendingReview()->create();

        $response = $this->getJson(route('compare.data', ['ids' => "{$visible->id},{$pending->id}"]));

        $response->assertOk();
        $response->assertJsonCount(1, 'products');
        $response->assertJsonFragment(['id' => $visible->id]);
    }
}
