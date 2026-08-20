<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Farmtech sells technical equipment — a buyer is more likely to search "3000 kg"
 * or "cattle" than the exact product title. These tests prove search actually
 * matches on specification values and application language in the description,
 * not just the title.
 */
class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_by_product_title(): void
    {
        $product = Product::factory()->create(['title' => 'T7E Digital Weighing Scale Indicator']);

        $response = $this->get(route('search.index', ['q' => 'weighing scale']));

        $response->assertSee($product->title);
    }

    public function test_search_matches_by_specification_value(): void
    {
        $product = Product::factory()->create(['title' => 'Unrelated Title']);
        ProductSpec::create([
            'product_id' => $product->id,
            'spec_group' => 'Performance',
            'spec_key' => 'Capacity',
            'spec_value' => '3000 kg',
            'is_highlight' => true,
        ]);

        $response = $this->get(route('search.index', ['q' => '3000 kg']));

        $response->assertSee($product->title);
    }

    public function test_search_matches_by_application_language_in_the_description(): void
    {
        $product = Product::factory()->create([
            'title' => 'Unrelated Title',
            'description_html' => '<p>Built for weighing cattle at the loading ramp.</p>',
        ]);

        $response = $this->get(route('search.index', ['q' => 'cattle']));

        $response->assertSee($product->title);
    }

    public function test_suggest_matches_by_specification_value(): void
    {
        $product = Product::factory()->create(['title' => 'Unrelated Title']);
        ProductSpec::create([
            'product_id' => $product->id,
            'spec_group' => 'Performance',
            'spec_key' => 'Capacity',
            'spec_value' => '3000 kg',
            'is_highlight' => true,
        ]);

        $response = $this->getJson(route('search.suggest', ['q' => '3000 kg']));

        $response->assertJsonFragment(['title' => 'Unrelated Title']);
    }
}
