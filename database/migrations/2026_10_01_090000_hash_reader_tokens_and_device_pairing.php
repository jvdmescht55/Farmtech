<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Device sync keys are now stored as SHA-256 hashes (like passwords).
        // Existing devices keep working: the same key hashes to the stored value.
        foreach (DB::table('readers')->get(['id', 'api_token']) as $r) {
            if (strlen($r->api_token) !== 64 || ! ctype_xdigit($r->api_token)) {
                DB::table('readers')->where('id', $r->id)->update(['api_token' => hash('sha256', $r->api_token)]);
            }
        }

        // Pairing: the device shows a 6-digit code, the farmer types it into
        // Kuddebestuur, the device collects its key — no key typed into firmware.
        Schema::create('device_pairings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->index();
            $table->string('secret_hash', 64)->unique();
            $table->string('serial', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('firmware', 32)->nullable();
            $table->foreignId('reader_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('token_encrypted')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_pairings');
        // Hashes cannot be reversed; devices would need new keys after a rollback.
    }
};
