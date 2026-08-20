<?php

namespace Tests\Feature;

use App\Enums\Industry;
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

    public function test_privacy_policy_page_renders(): void
    {
        $this->get(route('policies.privacy'))->assertOk()->assertSee('personal information');
    }

    public function test_footer_links_to_all_columns(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee(route('track.index'), false);
        $response->assertSee(route('policies.returns'), false);
        $response->assertSee(route('policies.terms'), false);
        $response->assertSee(route('policies.icasa'), false);
        $response->assertSee(route('policies.privacy'), false);
        $response->assertSee(route('how-it-works'), false);
        $response->assertSee(route('industry.show', Industry::Agriculture), false);
    }

    public function test_footer_whatsapp_badge_only_shows_when_configured(): void
    {
        $this->get(route('home'))->assertDontSee('WhatsApp Farmtech');

        Setting::set('support_whatsapp', '+27821234567', 'string');

        $this->get(route('home'))->assertSee('WhatsApp Farmtech');
    }
}
