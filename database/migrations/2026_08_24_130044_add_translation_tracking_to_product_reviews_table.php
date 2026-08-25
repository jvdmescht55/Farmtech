<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `original_review_text` snapshots the raw scraped text at import time,
 * before any AI translation/cleanup ever touches `review_text` — the real
 * source is never discarded, only presented more cleanly. `text_cleaned`
 * marks a review as already processed so a re-run of the translation
 * command doesn't re-bill Gemini for reviews it already handled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->text('original_review_text')->nullable()->after('review_text');
            $table->boolean('text_cleaned')->default(false)->after('original_review_text');
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn(['original_review_text', 'text_cleaned']);
        });
    }
};
