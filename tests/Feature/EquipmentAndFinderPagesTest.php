<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The two new browse-by-industry/goal pages actually render all 15 real categories under all 4 real industries. */
class EquipmentAndFinderPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_by_equipment_lists_every_industry_and_category(): void
    {
        $response = $this->get(route('equipment.index'));

        $response->assertOk();
        $response->assertSee('Livestock Management');
        $response->assertSee('Site &amp; Construction', false);
        $response->assertSee('Fleet &amp; Asset Logistics', false);
        $response->assertSee('Solar &amp; Water Infrastructure', false);
        $response->assertSee('Scale Indicators &amp; Platform Kits', false);
        $response->assertSee('Solar Electric Fencing');
    }

    public function test_finder_page_renders_with_real_industry_goal_data(): void
    {
        $response = $this->get(route('finder.index'));

        $response->assertOk();
        $response->assertSee('What are you trying to achieve?');
        $response->assertViewHas('industries', function ($industries) {
            return $industries->count() === 4
                && collect($industries)->firstWhere('value', 'agriculture')['goals'][0]['url'] !== null;
        });
    }
}
