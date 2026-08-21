<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A real bug this session: the PDP's gallery/pricing grid had no explicit
 * `grid-cols-1` at the base breakpoint (only `lg:grid-cols-2`), so on
 * mobile it fell back to an unconstrained implicit column with no
 * `minmax(0,1fr)` safety net — combined with the gallery box having no
 * explicit width (just `aspect-square`), this let the box's ambiguous
 * intrinsic sizing blow the whole page out to ~666px on a 375px viewport.
 * This suite can't render real CSS (no headless-browser test infra in this
 * project), so it can only guard the structural fix at the markup level —
 * verified visually via computed layout dimensions in the browser instead
 * (documented in PROGRESS.md).
 */
class ProductDetailLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_grid_has_an_explicit_single_column_base_state(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('grid grid-cols-1 lg:grid-cols-2 gap-12', false);
    }

    public function test_gallery_box_has_an_explicit_width_not_just_aspect_ratio(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertSee('relative w-full aspect-square', false);
    }
}
