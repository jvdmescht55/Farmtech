<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\LandedCostCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCalculationTest extends TestCase
{
    use RefreshDatabase;

    /** Same worked example already proven in worker/test/landedCost.test.js — PHP and Node must agree. */
    public function test_landed_cost_calculator_applies_15_percent_vat_and_the_given_duty_rate(): void
    {
        $calculator = new LandedCostCalculator(
            freightUsdPerKg: 16,
            domesticDeliveryZar: 250,
            vatRate: 0.15,
        );

        $result = $calculator->calculate(
            supplierUsd: 68,
            weightKg: 0.35,
            usdZarRate: 18.5,
            dutyRate: 0.10,
            targetMarginPct: 35,
        );

        // Base ZAR = 68 * 18.5 = 1258; Intl Freight = (0.35*16)*18.5 = 103.6
        $this->assertEqualsWithDelta(1258.00, $result['base_zar'], 0.01);
        $this->assertEqualsWithDelta(103.60, $result['intl_freight_zar'], 0.01);
        // Customs+VAT = (1258+103.6) * (1.10*1.15 - 1) = 360.82
        $this->assertEqualsWithDelta(360.82, $result['customs_vat_zar'], 0.01);
        // Landed = 1258 + 103.6 + 360.824 + 250 = 1972.42
        $this->assertEqualsWithDelta(1972.42, $result['landed_cost_zar'], 0.01);
        // Retail = 1972.424 / (1 - 0.35) = 3034.50
        $this->assertEqualsWithDelta(3034.50, $result['retail_price_zar'], 0.01);
    }

    public function test_duty_rate_differs_by_category_and_changes_the_landed_cost(): void
    {
        $calculator = new LandedCostCalculator(freightUsdPerKg: 16, domesticDeliveryZar: 250, vatRate: 0.15);

        // Same inputs, only duty rate differs (e.g. ultrasound 0% vs scales 10%) — landed cost must differ.
        $zeroDuty = $calculator->calculate(100, 1, 18, 0.00, 35);
        $tenPercentDuty = $calculator->calculate(100, 1, 18, 0.10, 35);

        $this->assertGreaterThan($zeroDuty['landed_cost_zar'], $tenPercentDuty['landed_cost_zar']);
    }

    public function test_landed_cost_calculator_guards_against_margin_at_or_above_100_percent(): void
    {
        $calculator = new LandedCostCalculator(freightUsdPerKg: 16, domesticDeliveryZar: 100, vatRate: 0.15);

        $result = $calculator->calculate(50, 1, 18, 0.10, targetMarginPct: 100);

        // Would divide by zero at exactly 100% margin — must fall back to landed cost, not explode.
        $this->assertEquals($result['landed_cost_zar'], $result['retail_price_zar']);
        $this->assertTrue(is_finite($result['retail_price_zar']));
    }

    public function test_the_four_line_items_sum_to_the_landed_cost_without_double_counting(): void
    {
        $calculator = new LandedCostCalculator(freightUsdPerKg: 16, domesticDeliveryZar: 250, vatRate: 0.15);

        $result = $calculator->calculate(68, 0.35, 18.5, 0.10, 35);

        $sum = $result['base_zar'] + $result['intl_freight_zar'] + $result['customs_vat_zar'] + $result['domestic_delivery_zar'];
        $this->assertEqualsWithDelta($result['landed_cost_zar'], $sum, 0.01);
    }

    public function test_order_numbers_follow_ft_yyyymmdd_xxxx_and_increment_sequentially_within_a_day(): void
    {
        $first = $this->makeOrder();
        $second = $this->makeOrder();
        $third = $this->makeOrder();

        $today = now()->format('Ymd');
        $this->assertEquals("FT-{$today}-0001", $first->order_number);
        $this->assertEquals("FT-{$today}-0002", $second->order_number);
        $this->assertEquals("FT-{$today}-0003", $third->order_number);
    }

    public function test_order_status_enum_only_allows_notifying_the_customer_for_dispatched_and_in_customs(): void
    {
        $this->assertTrue(OrderStatus::Dispatched->customerNotifiable());
        $this->assertTrue(OrderStatus::InCustoms->customerNotifiable());

        $this->assertFalse(OrderStatus::PendingPayment->customerNotifiable());
        $this->assertFalse(OrderStatus::Paid->customerNotifiable());
        $this->assertFalse(OrderStatus::ProcessingImport->customerNotifiable());
        $this->assertFalse(OrderStatus::Completed->customerNotifiable());
        $this->assertFalse(OrderStatus::Cancelled->customerNotifiable());
    }

    public function test_a_new_order_defaults_to_pending_payment_status(): void
    {
        $order = $this->makeOrder();

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
    }

    private function makeOrder(): Order
    {
        return Order::create([
            'customer_name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'phone' => '0821234567',
            'address_line1' => '1 Farm Rd',
            'city' => 'Bloemfontein',
            'province' => 'Free State',
            'postal_code' => '9300',
            'subtotal_zar' => 1000,
            'shipping_zar' => 0,
            'total_zar' => 1000,
            'payment_status' => 'pending',
            'status' => 'pending_payment',
        ]);
    }
}
