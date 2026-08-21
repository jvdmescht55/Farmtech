<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * "Frequently Bought Together" is a real, admin-curated relation — never an
 * inferred suggestion. These tests prove the SKU-based sync actually
 * resolves to real products, silently drops unrecognized SKUs rather than
 * erroring, and never lets a product bundle itself.
 */
class ProductBundleUpdateTest extends TestCase
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

    public function test_valid_skus_are_synced_as_real_bundle_companions(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::factory()->create();
        $companionA = Product::factory()->create(['sku' => 'FT-JUNCTION-01']);
        $companionB = Product::factory()->create(['sku' => 'FT-LOADCELL-02']);

        $this->actingAs($admin)->patch(route('admin.products.update', $product), $this->validPayload($product, [
            'bundle_skus' => 'FT-JUNCTION-01, FT-LOADCELL-02',
        ]));

        $this->assertEqualsCanonicalizing(
            [$companionA->id, $companionB->id],
            $product->fresh()->bundleCompanions->pluck('id')->all()
        );
    }

    public function test_unrecognized_skus_are_silently_dropped_not_errored(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.products.update', $product), $this->validPayload($product, [
            'bundle_skus' => 'FT-DOES-NOT-EXIST',
        ]));

        $response->assertSessionDoesntHaveErrors();
        $this->assertCount(0, $product->fresh()->bundleCompanions);
    }

    public function test_a_product_cannot_bundle_itself(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::factory()->create(['sku' => 'FT-SELF-01']);

        $this->actingAs($admin)->patch(route('admin.products.update', $product), $this->validPayload($product, [
            'bundle_skus' => 'FT-SELF-01',
        ]));

        $this->assertCount(0, $product->fresh()->bundleCompanions);
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
