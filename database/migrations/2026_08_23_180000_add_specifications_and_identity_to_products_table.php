<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renames the unused technical_specs_json (always empty, never written to by
 * any code path) to specifications — the raw deduped key→value attribute map
 * extracted from the Alibaba scrape's "Key attributes" table. Adds the
 * remaining product-identity/trust columns the scraper and AI copy step need.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('technical_specs_json', 'specifications');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('key_features')->nullable()->after('specifications');
            $table->string('brand_name')->nullable()->after('key_features');
            $table->string('model_number')->nullable()->after('brand_name');
            $table->string('warranty_period')->nullable()->after('model_number');
            $table->decimal('gross_weight_kg', 8, 3)->nullable()->after('est_weight_kg');
            $table->string('package_dimensions')->nullable()->after('gross_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['key_features', 'brand_name', 'model_number', 'warranty_period', 'gross_weight_kg', 'package_dimensions']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('specifications', 'technical_specs_json');
        });
    }
};
