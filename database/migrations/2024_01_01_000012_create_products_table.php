<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('category', ['scales', 'ultrasound', 'rfid', 'accessories']);
            $table->string('short_description', 500)->nullable();
            $table->longText('description_html')->nullable();

            // Costing inputs
            $table->decimal('original_price_usd', 12, 2);
            $table->decimal('est_weight_kg', 8, 3);
            $table->string('hs_code', 12)->nullable();
            $table->decimal('customs_duty_rate', 6, 4)->default(0); // e.g. 0.1000 = 10%
            $table->decimal('vat_rate', 6, 4)->default(0.15);

            // Costing outputs (computed by the sourcing pipeline)
            $table->decimal('landed_cost_zar', 12, 2)->nullable();
            $table->decimal('retail_price_zar', 12, 2)->nullable();
            $table->decimal('profit_margin_pct', 6, 2)->nullable();

            $table->enum('stock_status', ['in_stock', 'pre_order'])->default('pre_order');
            $table->string('lead_time_days')->default('7-12 business days');

            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])->default('draft');
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
