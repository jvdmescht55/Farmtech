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
        Schema::table('products', function (Blueprint $table) {
            // Real "what's in the box" contents (probes, adapters, calibration
            // certs, etc.), admin-entered per listing — nullable since most
            // existing products have never had this recorded; the PDP shows an
            // honest "not confirmed" fallback rather than inventing contents.
            $table->json('included_items')->nullable()->after('description_html');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('included_items');
        });
    }
};
