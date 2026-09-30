<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single embedded listing video for a product. `video_url` is a direct
     * .mp4/.webm (or Alibaba/Taobao video-CDN) URL that `catalog:extract-videos`
     * found already sitting in the product's own stored description HTML / spec
     * text / image payload — never fetched, never guessed — or one an admin
     * pasted in on the product page. `has_video` is a denormalised, indexed
     * mirror of `video_url IS NOT NULL`, kept in sync by a Product saving hook,
     * so the outreach dashboard can filter on it cheaply.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('video_url')->nullable()->after('description_html');
            $table->boolean('has_video')->default(false)->after('video_url')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'has_video']);
        });
    }
};
