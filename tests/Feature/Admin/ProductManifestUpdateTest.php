<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The Equipment Manifest ("what's in the box") is a real, admin-entered
 * field — never fabricated per product. These tests prove the quick-edit
 * form's newline-per-item textarea round-trips to a real JSON array, and
 * that leaving it blank stores null rather than an empty-string placeholder.
 */
class ProductManifestUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'title' => $product->title,
            'short_description' => $product->short_description,
            'retail_price_zar' => $product->retail_price_zar,
            'profit_margin_pct' => $product->profit_margin_pct,
            'stock_status' => $product->stock_status,
            'lead_time_days' => $product->lead_time_days,
            'low_stock_threshold' => $product->low_stock_threshold,
        ], $overrides);
    }

    public function test_manifest_textarea_is_stored_as_a_real_array(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.products.update', $product), $this->validPayload($product, [
            'included_items' => "4-wire load cell probe\n12V power adapter\n\nCalibration certificate",
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(
            ['4-wire load cell probe', '12V power adapter', 'Calibration certificate'],
            $product->fresh()->included_items
        );
    }

    public function test_blank_manifest_stores_null_not_an_empty_placeholder(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.products.update', $product), $this->validPayload($product, [
            'included_items' => '',
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertNull($product->fresh()->included_items);
    }

    public function test_pdp_shows_the_real_manifest_when_present(): void
    {
        $product = Product::factory()->create(['included_items' => ['4-wire load cell probe', 'Calibration certificate']]);

        $response = $this->get(route('products.show', $product));

        $response->assertSee('4-wire load cell probe');
        $response->assertSee('Calibration certificate');
    }

    public function test_pdp_shows_an_honest_fallback_when_manifest_is_not_recorded(): void
    {
        $product = Product::factory()->create(['included_items' => null]);

        $response = $this->get(route('products.show', $product));

        $response->assertSeeText("haven't been confirmed for this specific listing", false);
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
