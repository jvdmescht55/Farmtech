<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // storefrontVisible() (Product::scopeStorefrontVisible) filters on
        // exactly this pair on nearly every storefront query — the existing
        // (status, category) index doesn't help the is_active half of it.
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_active']);
        });

        // spec_key is filtered directly in category-filter whereHas() calls
        // and grouped by in the facet builder (ProductController) — had no
        // index of its own before, only the (product_id, spec_group) one.
        Schema::table('product_specs', function (Blueprint $table) {
            $table->index('spec_key');
        });

        // Admin order list/dashboard filter by both — no index existed on
        // either before this (only order_number, from the unique constraint).
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_active']);
        });

        Schema::table('product_specs', function (Blueprint $table) {
            $table->dropIndex(['spec_key']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
        });
    }
};
