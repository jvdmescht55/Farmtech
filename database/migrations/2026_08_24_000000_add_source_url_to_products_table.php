<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The real Alibaba listing URL (item.product.url in the scrape payload) —
 * distinct from supplier_url, which is the supplier's company profile page.
 * Previously discarded entirely.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('source_url')->nullable()->after('supplier_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('source_url');
        });
    }
};
