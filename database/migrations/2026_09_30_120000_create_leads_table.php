<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16); // interest, contact
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 40)->nullable();
            $table->string('farm_name', 160)->nullable();
            $table->string('herd_size', 40)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('interest', 80)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'handled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
