<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Farm / stud details printed on sale-catalogue headers
        // (e.g. "0696358  DIE BULT MEATMASTER STOET, POSBUS 42, KENHARDT, 8900").
        Schema::table('users', function (Blueprint $table) {
            $table->string('farm_name')->nullable()->after('name');
            $table->string('breeder_number', 32)->nullable()->after('farm_name');
            $table->string('stud_prefix', 16)->nullable()->after('breeder_number');
            $table->string('farm_address')->nullable()->after('stud_prefix');
            $table->string('breed', 64)->nullable()->after('farm_address');
            $table->string('phone', 32)->nullable()->after('breed');
        });

        // One code per physical device sold. Redeeming it unlocks the
        // matching software module for the customer's account.
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('module', 32)->default('rfid');
            $table->string('device_serial', 64)->nullable();
            $table->string('device_model', 64)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'module']);
        });

        Schema::create('readers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('serial', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('api_token', 64)->unique();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // in_herd=false rows are pedigree references (ancestors bred
            // elsewhere) so the tree can be walked uniformly.
            $table->boolean('in_herd')->default(true);
            $table->string('eid', 20)->nullable();
            $table->string('visual_id', 32);
            $table->string('name')->nullable();
            $table->char('sex', 1)->nullable(); // M / F
            $table->string('breed', 64)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_type', 2)->nullable(); // 01 single, 02 twin, 03 triplet
            $table->boolean('registered')->default(false);
            $table->boolean('is_commercial')->default(false);
            $table->string('tier', 4)->nullable(); // official tier if known: SP/C/B/CC
            $table->unsignedSmallInteger('gen_score')->nullable();
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('dam_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->string('status', 16)->default('active'); // active, sold, dead, culled
            $table->json('ebvs')->nullable();       // {trait: {v, acc}}
            $table->json('dam_record')->nullable(); // {first, sp, tl, lb, lw, mli, epi}
            $table->text('notes')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'visual_id']);
            $table->index(['user_id', 'eid']);
            $table->index(['user_id', 'in_herd', 'status']);
        });

        Schema::create('reader_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reader_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 16); // api, csv
            $table->string('filename')->nullable();
            $table->unsignedInteger('scan_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('new_count')->default(0);
            $table->timestamps();
        });

        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reader_sync_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('eid', 20)->nullable();
            $table->string('visual_id', 32)->nullable();
            $table->decimal('weight_kg', 6, 1)->nullable();
            $table->string('weigh_type', 16)->nullable(); // birth, wean, post_wean, routine
            $table->timestamp('scanned_at');
            $table->timestamps();
            $table->index(['animal_id', 'scanned_at']);
        });

        Schema::create('sale_catalogues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('breed', 64)->nullable();
            $table->date('sale_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('section', 32)->default('Ewes / Ooie');
            $table->string('breeder_line')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_catalogue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number', 12)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('comment')->nullable();
            $table->timestamps();
            $table->unique(['sale_catalogue_id', 'animal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lots');
        Schema::dropIfExists('sale_catalogues');
        Schema::dropIfExists('scans');
        Schema::dropIfExists('reader_syncs');
        Schema::dropIfExists('animals');
        Schema::dropIfExists('readers');
        Schema::dropIfExists('licenses');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['farm_name', 'breeder_number', 'stud_prefix', 'farm_address', 'breed', 'phone']);
        });
    }
};
