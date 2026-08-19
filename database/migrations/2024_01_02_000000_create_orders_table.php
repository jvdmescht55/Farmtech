<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();

            $table->string('customer_name');
            $table->string('email');
            $table->string('phone', 20);

            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->enum('province', [
                'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
                'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape',
            ]);
            $table->string('postal_code', 4);

            $table->decimal('subtotal_zar', 12, 2);
            $table->decimal('shipping_zar', 12, 2)->default(0);
            $table->decimal('total_zar', 12, 2);

            $table->enum('payment_gateway', ['payfast', 'ozow', 'yoco'])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable();

            $table->enum('status', ['pending', 'processing', 'shipped', 'completed', 'cancelled'])->default('pending');

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('title_snapshot');
            $table->decimal('unit_price_zar', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total_zar', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
