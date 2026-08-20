<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Nullable = "not tracked" (unlimited, today's default behaviour
            // for every existing product) — only products an admin has
            // explicitly given a count to are ever stock-checked at checkout.
            // Signed, not unsigned: a backorderable product can legitimately
            // go negative (that negative number IS the backorder depth) —
            // an unsigned column would throw a DB error the moment that
            // happened under strict SQL mode.
            $table->integer('stock_quantity')->nullable()->after('stock_status');
            $table->boolean('allow_backorder')->default(false)->after('stock_quantity');
            $table->unsignedInteger('low_stock_threshold')->default(2)->after('allow_backorder');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'allow_backorder', 'low_stock_threshold']);
        });
    }
};
