<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the single "clearing fee" folded into landed_cost_zar into three
 * separately-stored line items — intl_freight_zar, customs_vat_zar,
 * domestic_delivery_zar — so the admin "Profit Breakdown" card can show
 * exactly where the money went without recomputing from settings that may
 * have changed since the item was sourced. Also widens `status` to add
 * rejected_uncompetitive — the strict-arbitrage-filter outcome, distinct
 * from a compliance `rejected` (see worker/src/pipeline.js).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('intl_freight_zar', 12, 2)->nullable()->after('original_price_usd');
            $table->decimal('customs_vat_zar', 12, 2)->nullable()->after('intl_freight_zar');
            $table->decimal('domestic_delivery_zar', 12, 2)->nullable()->after('customs_vat_zar');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'rejected_uncompetitive', 'archived'])
                ->default('draft')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['intl_freight_zar', 'customs_vat_zar', 'domestic_delivery_zar']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])
                ->default('draft')
                ->change();
        });
    }
};
