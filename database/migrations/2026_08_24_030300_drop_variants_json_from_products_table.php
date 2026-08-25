<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Superseded by the real product_variants table — this JSON column was
 * added last session before any real per-listing SKU/price-tier data had
 * been confirmed to exist; it was never populated on any of the 322 live
 * products, so dropping it loses nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variants');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('variants')->nullable()->after('key_features');
        });
    }
};
