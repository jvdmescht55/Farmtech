<?php

namespace Tests\Feature;

use App\Enums\Industry;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The new short "domain hub" URLs (/livestock, /construction, /solar,
 * /logistics) are aliases for the real, already-tested /industry/{industry}
 * page — same controller, same data, just a friendlier path with the new
 * benefit-driven domain naming.
 */
class DomainNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_domain_hub_url_renders_the_real_industry_page(): void
    {
        $product = Product::factory()->create(['category' => \App\Enums\ProductCategory::Scales]);

        $response = $this->get('/livestock');

        $response->assertOk();
        $response->assertSee('Livestock Management');
        $response->assertSee($product->title);
    }

    public function test_domain_slugs_map_to_the_correct_industry(): void
    {
        $this->assertSame('livestock', Industry::Agriculture->domainSlug());
        $this->assertSame('construction', Industry::Construction->domainSlug());
        $this->assertSame('logistics', Industry::IndustrialLogistics->domainSlug());
        $this->assertSame('solar', Industry::SolarPower->domainSlug());
    }

    public function test_header_and_footer_link_to_the_new_domain_urls(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('domain.livestock'), false);
        $response->assertSee(route('domain.construction'), false);
    }
}
