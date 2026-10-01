<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every device is a "reader" row; kind says which software it feeds.
        Schema::table('readers', function (Blueprint $table) {
            $table->string('kind', 16)->default('handheld')->after('license_id'); // handheld, watch, custom
            $table->string('location')->nullable()->after('name');                // e.g. "Trough — Bergkamp"
            $table->unsignedSmallInteger('alert_hours')->nullable()->after('location'); // watch: hours without a visit before we shout
            $table->json('metrics')->nullable()->after('alert_hours');             // custom: [{key,label,unit,min,max}]
        });

        // Readings from custom devices (tank level, temperature, rainfall…).
        Schema::create('device_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 40);
            $table->double('value');
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['reader_id', 'metric', 'recorded_at']);
        });

        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 16); // device, software, custom_build
            $table->string('title', 160);
            $table->text('details')->nullable();
            $table->string('name', 120)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('status', 16)->default('new'); // new, planned, building, done, declined
            $table->text('reply')->nullable();
            $table->timestamps();
        });

        // Farmtech's own products (KraalTrac Pro, Watch…) — published from admin.
        Schema::create('store_listings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('availability')->nullable();
            $table->string('module', 32)->nullable(); // software it unlocks
            $table->text('overview')->nullable();
            $table->json('features')->nullable();   // [{title, body}]
            $table->json('specs')->nullable();      // [{label, value}]
            $table->json('in_box')->nullable();     // [string]
            $table->text('why')->nullable();
            $table->json('images')->nullable();     // [path]
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('is_active');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('store_listing_id')->nullable()->after('interest')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $t) => $t->dropConstrainedForeignId('store_listing_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('terms_accepted_at'));
        Schema::dropIfExists('store_listings');
        Schema::dropIfExists('suggestions');
        Schema::dropIfExists('device_readings');
        Schema::table('readers', fn (Blueprint $t) => $t->dropColumn(['kind', 'location', 'alert_hours', 'metrics']));
    }
};
