<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Farmtech shop: stock & batches on store listings, and orders that can
 * hold store listings (not only the old imported products), reservations for
 * the next batch, and collect-from-us orders without a street address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_listings', function (Blueprint $t) {
            $t->string('stock_status', 20)->default('in_stock')->after('availability'); // in_stock | low_stock | sold_out | coming_soon
            $t->unsignedInteger('stock_qty')->nullable()->after('stock_status');        // null = built to order / not counted
            $t->string('next_batch')->nullable()->after('stock_qty');                   // "Next batch ships mid-November"
            $t->string('unit_label')->nullable()->after('price_cents');                 // "Pack of 50"
            $t->unsignedInteger('compare_at_cents')->nullable()->after('unit_label');   // was-price
            $t->string('badge', 40)->nullable()->after('compare_at_cents');             // "New", "Best value"
            $t->string('photo_key', 60)->nullable()->after('images');                   // SiteImages key used until real photos exist
            $t->unsignedSmallInteger('max_per_order')->nullable()->after('stock_qty');
        });

        Schema::table('orders', function (Blueprint $t) {
            $t->string('kind', 20)->default('order')->after('order_number');            // order | reservation | mixed
            $t->string('farm_name')->nullable()->after('customer_name');
            $t->string('delivery_method', 20)->nullable()->after('postal_code');       // courier | collect
            $t->string('payment_method', 20)->nullable()->after('payment_gateway');    // eft | card | none (reservation)
            $t->text('customer_notes')->nullable()->after('tracking_number');
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->string('address_line1')->nullable()->change();
            $t->string('city')->nullable()->change();
            $t->string('province', 60)->nullable()->change();
            $t->string('postal_code', 4)->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $t) {
            $t->unsignedBigInteger('product_id')->nullable()->change();
            $t->foreignId('store_listing_id')->nullable()->after('product_id')->constrained('store_listings')->nullOnDelete();
            $t->boolean('is_reservation')->default(false)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropConstrainedForeignId('store_listing_id');
            $t->dropColumn('is_reservation');
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn(['kind', 'farm_name', 'delivery_method', 'payment_method', 'customer_notes']);
        });
        Schema::table('store_listings', function (Blueprint $t) {
            $t->dropColumn(['stock_status', 'stock_qty', 'next_batch', 'unit_label', 'compare_at_cents', 'badge', 'photo_key', 'max_per_order']);
        });
    }
};
