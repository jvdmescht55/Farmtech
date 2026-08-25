<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real per-SKU price tiers scraped from Alibaba's own detail.skus table
 * (see worker/lib_pricing.php::ft_extract_variants()) — a listing like
 * "Radar Water Level Sensor" sells the same base unit at genuinely
 * different USD prices per range/package option ("7m" vs "80m" vs
 * "Wireless module + server + software"), each run through
 * LandedCostCalculator independently rather than derived as a delta off
 * one base price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('option_name');
            $table->decimal('supplier_cost_usd', 10, 2);
            $table->decimal('landed_cost_zar', 10, 2)->nullable();
            $table->decimal('retail_price_zar', 10, 2);
            $table->boolean('is_default')->default(false);
            // Null = untracked (no per-SKU stock signal exists in the
            // scrape data — Alibaba B2B listings show MOQ, not a retail
            // stock count), same convention as products.stock_quantity.
            $table->integer('stock_quantity')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
