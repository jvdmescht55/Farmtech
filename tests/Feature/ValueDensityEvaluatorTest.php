<?php

namespace Tests\Feature;

use App\Services\ValueDensityEvaluator;
use Tests\TestCase;

/** Same worked examples already proven in worker/test/valueDensityFilter.test.js — PHP and Node must agree. */
class ValueDensityEvaluatorTest extends TestCase
{
    public function test_passes_a_light_high_value_item(): void
    {
        $result = (new ValueDensityEvaluator())->evaluate([
            'base_zar' => 555.5, 'intl_freight_zar' => 9.25, 'landed_cost_zar' => 700, 'retail_price_zar' => 1200,
        ]);

        $this->assertTrue($result['passes']);
    }

    public function test_rejects_a_heavy_low_value_item_on_freight_ratio(): void
    {
        $result = (new ValueDensityEvaluator())->evaluate([
            'base_zar' => 500, 'intl_freight_zar' => 300, 'landed_cost_zar' => 1200, 'retail_price_zar' => 1800,
        ]);

        $this->assertFalse($result['passes']);
        $this->assertSame('excessive_freight_ratio', $result['reason']);
    }

    public function test_high_freight_ratio_forgiven_above_the_profit_exception(): void
    {
        $result = (new ValueDensityEvaluator())->evaluate([
            'base_zar' => 500, 'intl_freight_zar' => 300, 'landed_cost_zar' => 1200, 'retail_price_zar' => 3000,
        ]);

        $this->assertTrue($result['passes']);
    }

    public function test_rejects_on_minimum_net_profit(): void
    {
        $result = (new ValueDensityEvaluator())->evaluate([
            'base_zar' => 1000, 'intl_freight_zar' => 50, 'landed_cost_zar' => 1200, 'retail_price_zar' => 1300,
        ]);

        $this->assertFalse($result['passes']);
        $this->assertSame('below_minimum_net_profit', $result['reason']);
    }
}
