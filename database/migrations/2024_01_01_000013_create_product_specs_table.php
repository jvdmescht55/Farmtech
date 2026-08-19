<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('spec_group'); // e.g. "Frequency & Compliance", "Power", "Physical"
            $table->string('spec_key');
            $table->string('spec_value');
            $table->boolean('is_highlight')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'spec_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specs');
    }
};
