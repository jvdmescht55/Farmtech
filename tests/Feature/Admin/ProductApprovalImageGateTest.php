<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A real publish-time gate (spec item 19): a product cannot be approved
 * without at least one image on record, regardless of what the sourcing
 * pipeline's own vetting verdict said — this is enforced at the one place
 * a product actually goes live (ProductController::approve), not just an
 * admin-UI convention someone could bypass.
 */
class ProductApprovalImageGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_with_no_images_cannot_be_approved(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::factory()->pendingReview()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.approve', $product));

        $response->assertSessionHasErrors('images');
        $this->assertSame('pending_review', $product->fresh()->status);
        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_a_product_with_at_least_one_image_can_be_approved(): void
    {
        $admin = $this->makeAdmin();
        $product = Product::factory()->pendingReview()->create();
        ProductImage::create([
            'product_id' => $product->id,
            'original_url' => 'https://example.com/photo.jpg',
            'local_path' => 'test-sku-1.webp',
            'is_thumbnail' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.approve', $product));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('approved', $product->fresh()->status);
        $this->assertTrue((bool) $product->fresh()->is_active);
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
