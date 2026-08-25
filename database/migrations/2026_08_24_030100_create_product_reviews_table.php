<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real Alibaba buyer reviews (worker/lib_pricing.php::ft_extract_reviews()),
 * from the scrape's top-level `reviews[]` array — genuine masked buyer
 * names ("V************o", Alibaba's own display format), real ratings/
 * dates/text. source_review_id lets a re-scrape updateOrCreate without
 * ever duplicating a review already imported.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('source_review_id')->nullable();
            $table->string('author_name');
            $table->unsignedTinyInteger('rating');
            $table->text('review_text')->nullable();
            $table->boolean('verified_purchase')->default(true);
            $table->date('review_date')->nullable();
            $table->string('source', 30)->default('alibaba');
            $table->timestamps();

            $table->unique(['product_id', 'source_review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
