<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces last session's JSON-attribute variant snapshot (variant_key/
 * variant_attributes) with a real FK to product_variants, now that a real
 * variants table exists — no order has ever referenced the JSON columns
 * (no product ever had a populated products.variants JSON), so this is a
 * clean swap, not a data migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['variant_key', 'variant_attributes']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('variant_option_name')->nullable()->after('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn('variant_option_name');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('variant_key')->nullable()->after('product_id');
            $table->json('variant_attributes')->nullable()->after('variant_key');
        });
    }
};
