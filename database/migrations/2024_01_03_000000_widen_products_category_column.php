<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Category used to be a fixed DB enum (scales/ultrasound/rfid/accessories),
 * which meant adding a category required a migration. Widened to a plain
 * string so the list of real categories — see App\Enums\ProductCategory —
 * can keep growing without touching the schema again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('category', ['scales', 'ultrasound', 'rfid', 'accessories'])->change();
        });
    }
};
