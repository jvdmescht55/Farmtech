<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same reasoning as products.category: a plain string (validated at
        // the app layer via App\Enums\OrderStatus) instead of a DB enum, so
        // the lifecycle can grow without another migration.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 30)->default('pending_payment')->change();
        });

        // Existing demo rows used the old pending/processing/shipped values.
        DB::table('orders')->where('status', 'pending')->update(['status' => 'pending_payment']);
        DB::table('orders')->where('status', 'processing')->update(['status' => 'paid']);
        DB::table('orders')->where('status', 'shipped')->update(['status' => 'dispatched']);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('payment_reference');
            $table->string('courier_name')->nullable()->after('payment_reference');

            // Checkout only ever collected one address (used as both
            // shipping and billing, the overwhelming common case for this
            // kind of store) — these stay nullable until/unless a real
            // separate-billing-address step gets added to checkout.
            $table->boolean('billing_same_as_shipping')->default(true)->after('postal_code');
            $table->string('billing_address_line1')->nullable()->after('billing_same_as_shipping');
            $table->string('billing_address_line2')->nullable()->after('billing_address_line1');
            $table->string('billing_city')->nullable()->after('billing_address_line2');
            $table->string('billing_province')->nullable()->after('billing_city');
            $table->string('billing_postal_code', 4)->nullable()->after('billing_province');
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->boolean('customer_notified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_number', 'courier_name', 'billing_same_as_shipping',
                'billing_address_line1', 'billing_address_line2', 'billing_city',
                'billing_province', 'billing_postal_code',
            ]);
        });

        DB::table('orders')->where('status', 'pending_payment')->update(['status' => 'pending']);
        DB::table('orders')->whereIn('status', ['paid', 'processing_import', 'in_customs'])->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'dispatched')->update(['status' => 'shipped']);

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'shipped', 'completed', 'cancelled'])->default('pending')->change();
        });
    }
};
