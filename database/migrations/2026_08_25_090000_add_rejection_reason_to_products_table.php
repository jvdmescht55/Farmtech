<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records WHY a product was auto-rejected by the Logistics & Dimensional
 * Gatekeeper (see App\Services\ValueDensityEvaluator::evaluateLogistics()
 * and the catalog:purge-oversized command) — e.g. "Auto-rejected: Exceeds
 * air freight weight limit (>25kg)". Nullable/free-text since an admin can
 * also reject a listing manually with their own reasoning; not constrained
 * to the gatekeeper's own fixed reason strings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('rejection_reason', 500)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
