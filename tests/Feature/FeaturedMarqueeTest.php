<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The homepage marquee renders real products (the same trending ranking used elsewhere), never placeholder content. */
class FeaturedMarqueeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_the_marquee_with_a_real_product(): void
    {
        $product = Product::factory()->create(['title' => 'Marquee Test Scale']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Featured Equipment');
        $response->assertSee('Marquee Test Scale');
    }

    public function test_marquee_is_hidden_when_there_are_no_approved_products(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Featured Equipment');
    }
}
