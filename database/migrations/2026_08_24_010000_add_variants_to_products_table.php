<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Selectable SKU variations (color, wattage, probe type, size, ...) as a
 * single JSON array of {attributes, sku_suffix, price_delta_zar} rows —
 * see App\Models\Product::hasVariants()/variantOptionGroups()/findVariant().
 * A JSON column rather than a product_variants table: this app's stock
 * tracking (Product::decrementStock()) is per-product, not per-SKU, so a
 * fully relational variants table would need a separate, larger stock-model
 * change beyond what's asked for here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('variants')->nullable()->after('key_features');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variants');
        });
    }
};
