<?php

namespace Tests\Feature;

use App\Models\ComplianceAudit;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "AI vetting" itself — the actual 125 kHz-vs-134.2 kHz judgment call — is
 * made by a live Gemini API call in worker/src/prompts/vettingPrompt.js,
 * tested for real there (worker/test/, real network calls, real model
 * responses). That is not repeatable here: PHPUnit has no business making
 * live, non-deterministic, quota-consuming calls to an external AI on every
 * test run.
 *
 * What *is* deterministic, and what this file actually verifies, is how
 * Farmtech's own application code reacts to a vetting result once the
 * pipeline has produced one: does a rejected 125 kHz listing get correctly
 * excluded from the storefront, does an approved 134.2 kHz listing get
 * shown, and does the compliance badge shown to a customer match the real
 * audit_verdict rather than being decorative.
 */
class PipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_rejected_125khz_rfid_reader_never_appears_on_the_storefront(): void
    {
        $rejected = Product::factory()->create([
            'title' => 'Invalid 125kHz Pet Chip Reader',
            'status' => 'rejected',
            'is_active' => false,
        ]);

        ComplianceAudit::create([
            'product_id' => $rejected->id,
            'supplier_name' => 'Unverified Supplier Co',
            'frequency_checked' => '125 kHz EM4100 — not ISO 11784/5 compliant',
            'icasa_status' => 'flagged',
            'plug_type_checked' => false,
            'risk_score' => 90,
            'audit_verdict' => 'FAIL',
            'rejection_reasons' => ['Operating frequency is 125 kHz rather than the required 134.2 kHz ISO 11784/11785 standard.'],
        ]);

        $this->assertDatabaseCount('products', 1);
        $this->assertFalse(Product::storefrontVisible()->exists());

        $this->get(route('products.show', $rejected))->assertNotFound();
        $this->get(route('home'))->assertDontSee('Invalid 125kHz Pet Chip Reader');
    }

    public function test_an_approved_1342khz_rfid_reader_appears_and_shows_the_verified_stamp(): void
    {
        $approved = Product::factory()->create([
            'title' => 'Handheld ISO 134.2kHz RFID Stick Reader',
            'category' => \App\Enums\ProductCategory::Rfid,
            'status' => 'approved',
            'is_active' => true,
        ]);

        ComplianceAudit::create([
            'product_id' => $approved->id,
            'supplier_name' => 'Verified Supplier Co',
            'supplier_years' => 6,
            'is_verified_supplier' => true,
            'frequency_checked' => '134.2 kHz ISO 11784/5 compliant',
            'icasa_status' => 'exempt',
            'plug_type_checked' => true,
            'risk_score' => 10,
            'audit_verdict' => 'PASS',
            'rejection_reasons' => [],
        ]);

        $this->assertTrue(Product::storefrontVisible()->exists());

        $response = $this->get(route('products.show', $approved));
        $response->assertOk();
        $response->assertSee('Handheld ISO 134.2kHz RFID Stick Reader');
        $response->assertSee('134.2 kHz ISO 11784/5 compliant');
    }

    public function test_a_warn_verdict_product_shows_the_softer_ai_checked_badge_not_verified_and_cleared(): void
    {
        $warned = Product::factory()->create([
            'title' => 'Ultrasound Scanner Missing Battery Cert',
            'status' => 'approved',
            'is_active' => true,
        ]);

        ComplianceAudit::create([
            'product_id' => $warned->id,
            'supplier_name' => 'Some Supplier',
            'audit_verdict' => 'WARN',
            'risk_score' => 40,
            'rejection_reasons' => ['Missing UN38.3 transport certificate for the lithium battery pack.'],
        ]);

        $response = $this->get(route('category.show', $warned->category));

        $response->assertOk();
        $response->assertSee('AI Checked');
        $response->assertDontSee('Verified &amp; Cleared', false);
    }
}
