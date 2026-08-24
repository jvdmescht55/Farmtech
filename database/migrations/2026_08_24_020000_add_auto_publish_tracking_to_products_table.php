<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracking for the AI auto-publish pass (app/Console/Commands/AutoPublishProducts.php):
 * auto_publish_checked_at records when Gemini last evaluated a pending_review
 * product's readiness, so a growing backlog isn't re-vetted (and re-billed)
 * every run. published_via distinguishes a human-approved product from one
 * the auto-publish command promoted on its own — shown as a badge on the
 * new admin "Live Products" tab.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('auto_publish_checked_at')->nullable()->after('approved_at');
            $table->string('published_via', 20)->nullable()->after('auto_publish_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['auto_publish_checked_at', 'published_via']);
        });
    }
};
