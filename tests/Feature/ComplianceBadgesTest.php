<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Models\ComplianceAudit;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The badge strip is data-driven per product, never a fixed set — see
 * resources/views/components/compliance-badges.blade.php. These tests prove
 * badges appear only when the underlying pipeline data actually supports
 * them, and don't appear as decoration when it doesn't.
 */
class ComplianceBadgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfid_product_with_correct_frequency_shows_iso_badge(): void
    {
        $product = Product::factory()->create(['category' => ProductCategory::Rfid]);
        ComplianceAudit::create([
            'product_id' => $product->id,
            'supplier_name' => 'Verified Supplier',
            'frequency_checked' => '134.2 kHz ISO 11784/11785',
            'icasa_status' => 'exempt',
            'plug_type_checked' => true,
            'risk_score' => 10,
            'audit_verdict' => 'PASS',
            'rejection_reasons' => [],
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertSee('ISO 11784/11785 Compliant');
        $response->assertSee('ICASA Exempt');
        $response->assertSee('Power Spec Verified');
    }

    public function test_scales_product_never_shows_the_rfid_specific_iso_badge(): void
    {
        $product = Product::factory()->create(['category' => ProductCategory::Scales]);
        ComplianceAudit::create([
            'product_id' => $product->id,
            'supplier_name' => 'Verified Supplier',
            'icasa_status' => 'pre_approved',
            'plug_type_checked' => true,
            'risk_score' => 10,
            'audit_verdict' => 'PASS',
            'rejection_reasons' => [],
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertDontSee('ISO 11784/11785 Compliant');
        $response->assertSee('ICASA Type Approved');
    }

    public function test_ip_rating_badge_only_appears_when_a_real_spec_row_records_one(): void
    {
        $withIp = Product::factory()->create(['category' => ProductCategory::LaserLevels]);
        ProductSpec::create([
            'product_id' => $withIp->id,
            'spec_group' => 'Durability',
            'spec_key' => 'IP Rating',
            'spec_value' => 'IP54',
            'is_highlight' => true,
        ]);

        $withoutIp = Product::factory()->create(['category' => ProductCategory::LaserLevels]);

        $this->get(route('products.show', $withIp))->assertSee('IP54 Ingress Protection');
        $this->get(route('products.show', $withoutIp))->assertDontSee('Ingress Protection');
    }

    public function test_no_compliance_audit_renders_no_badges_at_all(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertDontSee('ICASA Type Approved');
        $response->assertDontSee('ICASA Exempt');
        $response->assertDontSee('ICASA Permit Required');
        $response->assertDontSee('Power Spec Verified');
    }

    public function test_whatsapp_cta_only_renders_when_a_number_is_configured(): void
    {
        $product = Product::factory()->create();

        $this->get(route('products.show', $product))->assertDontSee('Chat with an equipment specialist');

        Setting::set('support_whatsapp', '+27821234567', 'string');

        $this->get(route('products.show', $product))
            ->assertSee('Chat with an equipment specialist')
            ->assertSee('https://wa.me/27821234567', false);
    }
}
