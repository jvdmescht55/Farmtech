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

    // Logistics & Dimensional Gatekeeper

    public function test_parse_dimensions_cm_extracts_three_numbers(): void
    {
        $evaluator = new ValueDensityEvaluator();

        $this->assertSame([28.0, 14.7, 6.0], $evaluator->parseDimensionsCm('28cm*14.7cm*6cm'));
        $this->assertSame([50.0, 40.0, 30.0], $evaluator->parseDimensionsCm('50 x 40 x 30 cm'));
        $this->assertNull($evaluator->parseDimensionsCm('single value'));
        $this->assertNull($evaluator->parseDimensionsCm(null));
    }

    public function test_logistics_rejects_over_25kg_weight_limit(): void
    {
        $result = (new ValueDensityEvaluator())->evaluateLogistics([
            'gross_weight_kg' => 180, 'package_dimensions' => null, 'hazard_text' => 'Diesel generator',
            'intl_freight_zar' => 1000, 'retail_price_zar' => 20000,
        ]);

        $this->assertFalse($result['passes']);
        $this->assertSame('exceeds_max_weight', $result['reason']);
    }

    public function test_logistics_rejects_over_120cm_longest_side(): void
    {
        $result = (new ValueDensityEvaluator())->evaluateLogistics([
            'gross_weight_kg' => 20, 'package_dimensions' => '150x40x40', 'hazard_text' => '',
            'intl_freight_zar' => 500, 'retail_price_zar' => 5000,
        ]);

        $this->assertFalse($result['passes']);
        $this->assertSame('exceeds_max_dimension', $result['reason']);
    }

    public function test_logistics_rejects_excessive_volumetric_weight(): void
    {
        $result = (new ValueDensityEvaluator())->evaluateLogistics([
            'gross_weight_kg' => 5, 'package_dimensions' => '100x100x100', 'hazard_text' => '',
            'intl_freight_zar' => 500, 'retail_price_zar' => 5000,
        ]);

        $this->assertFalse($result['passes']);
        $this->assertSame('excessive_volumetric_weight', $result['reason']);
    }

    public function test_logistics_rejects_hazardous_goods(): void
    {
        $result = (new ValueDensityEvaluator())->evaluateLogistics([
            'gross_weight_kg' => 2, 'package_dimensions' => null, 'hazard_text' => 'Bulk Diesel Fuel Container 20L',
            'intl_freight_zar' => 50, 'retail_price_zar' => 2000,
        ]);

        $this->assertFalse($result['passes']);
        $this->assertSame('prohibited_hazardous_goods', $result['reason']);
    }

    public function test_logistics_rejects_shipping_over_65_percent_of_retail(): void
    {
        $result = (new ValueDensityEvaluator())->evaluateLogistics([
            'gross_weight_kg' => 22, 'package_dimensions' => null, 'hazard_text' => '',
            'intl_freight_zar' => 1400, 'retail_price_zar' => 2000,
        ]);

        $this->assertFalse($result['passes']);
        $this->assertSame('low_value_density', $result['reason']);
    }

    public function test_logistics_passes_a_normal_item(): void
    {
        $result = (new ValueDensityEvaluator())->evaluateLogistics([
            'gross_weight_kg' => 1.5, 'package_dimensions' => '30x20x15', 'hazard_text' => 'RFID ear tag reader',
            'intl_freight_zar' => 100, 'retail_price_zar' => 1500,
        ]);

        $this->assertTrue($result['passes']);
        $this->assertNull($result['reason']);
    }
}
