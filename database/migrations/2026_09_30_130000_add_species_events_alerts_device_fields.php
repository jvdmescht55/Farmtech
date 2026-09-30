<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('species', 16)->default('sheep')->after('in_herd');
            $table->date('status_date')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('species', 16)->default('sheep')->after('breed');
        });

        // Health/breeding logbook: treatments, vaccinations, mating, births, sales, deaths…
        Schema::create('animal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->date('date');
            $table->string('product')->nullable();
            $table->string('dose', 64)->nullable();
            $table->date('withdrawal_until')->nullable();
            $table->foreignId('mate_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->unsignedTinyInteger('count')->nullable();
            $table->string('result', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type', 'date']);
            $table->index(['animal_id', 'date']);
        });

        // "Seen it, stop telling me" for a specific alert on a specific animal.
        Schema::create('alert_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('alert_key', 120);
            $table->timestamp('until')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'alert_key']);
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->string('client_ref', 64)->nullable()->after('reader_sync_id');
            $table->index(['user_id', 'client_ref']);
        });

        Schema::table('readers', function (Blueprint $table) {
            $table->string('firmware', 32)->nullable()->after('model');
            $table->unsignedTinyInteger('battery_pct')->nullable()->after('firmware');
            $table->string('last_ip', 45)->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('readers', fn (Blueprint $t) => $t->dropColumn(['firmware', 'battery_pct', 'last_ip']));
        Schema::table('scans', function (Blueprint $t) {
            $t->dropIndex(['user_id', 'client_ref']);
            $t->dropColumn('client_ref');
        });
        Schema::dropIfExists('alert_dismissals');
        Schema::dropIfExists('animal_events');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('species'));
        Schema::table('animals', fn (Blueprint $t) => $t->dropColumn(['species', 'status_date']));
    }
};
