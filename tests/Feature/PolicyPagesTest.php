<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_policy_page_renders(): void
    {
        $this->get(route('policies.returns'))->assertOk()->assertSee('14 (fourteen) days');
    }

    public function test_terms_page_renders(): void
    {
        $this->get(route('policies.terms'))->assertOk()->assertSee('all-inclusive');
    }

    public function test_icasa_compliance_page_renders(): void
    {
        $this->get(route('policies.icasa'))->assertOk()->assertSee('134.2 kHz');
    }

    public function test_footer_links_to_all_four_columns(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee(route('track.index'), false);
        $response->assertSee(route('policies.returns'), false);
        $response->assertSee(route('policies.terms'), false);
        $response->assertSee(route('policies.icasa'), false);
        $response->assertSee(route('category.show', ProductCategory::Scales), false);
    }

    public function test_footer_whatsapp_badge_only_shows_when_configured(): void
    {
        $this->get(route('home'))->assertDontSee('Chat on WhatsApp');

        Setting::set('support_whatsapp', '+27821234567', 'string');

        $this->get(route('home'))->assertSee('Chat on WhatsApp');
    }
}
