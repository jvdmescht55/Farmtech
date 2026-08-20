<?php

namespace Database\Factories;

use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $sku = 'FT-TEST-'.$this->faker->unique()->numerify('#####');

        return [
            'sku' => $sku,
            'title' => $this->faker->words(4, true),
            'category' => ProductCategory::Scales,
            'short_description' => $this->faker->sentence(),
            'description_html' => '<p>'.$this->faker->paragraph().'</p>',
            'original_price_usd' => $this->faker->randomFloat(2, 20, 500),
            'est_weight_kg' => $this->faker->randomFloat(3, 0.1, 10),
            'hs_code' => '8423.82',
            'customs_duty_rate' => 0.10,
            'vat_rate' => 0.15,
            'landed_cost_zar' => 1000,
            'retail_price_zar' => 1538.46,
            'profit_margin_pct' => 35,
            'stock_status' => 'in_stock',
            'lead_time_days' => '7-12 business days',
            'status' => 'approved',
            'is_active' => true,
            'stock_quantity' => null,
            'allow_backorder' => false,
            'low_stock_threshold' => 2,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(['status' => 'pending_review', 'is_active' => false]);
    }

    public function tracked(int $quantity, bool $allowBackorder = false): static
    {
        return $this->state([
            'stock_quantity' => $quantity,
            'allow_backorder' => $allowBackorder,
        ]);
    }
}
