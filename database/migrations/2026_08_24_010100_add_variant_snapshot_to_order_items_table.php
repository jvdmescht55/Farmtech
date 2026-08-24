<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A real snapshot of the variant selected at checkout time, same principle
 * as the existing title_snapshot/unit_price_zar columns — an order must
 * keep showing what was actually bought even if the product's variants
 * change or are removed later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('variant_key')->nullable()->after('product_id');
            $table->json('variant_attributes')->nullable()->after('variant_key');
            $table->string('variant_sku')->nullable()->after('variant_attributes');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['variant_key', 'variant_attributes', 'variant_sku']);
        });
    }
};
