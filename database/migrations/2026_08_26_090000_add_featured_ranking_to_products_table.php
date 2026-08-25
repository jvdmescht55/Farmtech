<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the storefront curation algorithm (App\Console\Commands\
 * RecalculateFeaturedScores): `click_count` is a real, incrementing signal
 * (bumped on every storefront product-page view — see
 * Storefront\ProductController::show()), never seeded/fabricated.
 * `featured_score` is a stored, recomputable composite of real signals
 * (margin, weight/compactness, media richness, reviews, verified-supplier
 * trust) — stored rather than computed on every query since it only needs
 * to change when the underlying data does, not on every page load.
 * `is_featured` is the top-N cut of that score, set by the same command.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->decimal('featured_score', 8, 2)->default(0)->after('is_featured');
            $table->unsignedInteger('click_count')->default(0)->after('featured_score');

            $table->index(['is_featured', 'featured_score']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_featured', 'featured_score']);
            $table->dropColumn(['is_featured', 'featured_score', 'click_count']);
        });
    }
};
