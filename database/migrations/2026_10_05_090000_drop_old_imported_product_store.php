<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The old imported-products store is gone (owner's decision, 5 Oct 2026). Its
 * tables are dropped; a full backup was taken first
 * (/root/backups/farmtech-2026-10-05-0757-pre-old-store-removal.sql.gz).
 * Order lines keep their product_id / variant columns as plain history.
 */
return new class extends Migration
{
    private const TABLES = ['product_bundle_items', 'product_reviews', 'product_specs', 'product_images', 'compliance_audits', 'product_variants', 'products', 'blacklisted_suppliers', 'exchange_rates'];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') { // sqlite (tests) can't drop FKs by name
            Schema::table('order_items', function (Blueprint $t) {
                $t->dropForeign('order_items_product_id_foreign');
                $t->dropForeign('order_items_product_variant_id_foreign');
            });
        }
        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Restore from the backup named above if this ever needs undoing.
    }
};
