<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('plan', 10)->default('monthly');          // monthly | yearly
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamp('paid_until')->nullable();
            $t->string('reminded_for', 40)->nullable();          // last reminder sent, so each goes once
            $t->text('notes')->nullable();                       // admin notes (free months given, etc.)
            $t->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('reference', 20)->unique();                // SUB-000123: EFT reference and invoice number
            $t->string('plan', 10);
            $t->unsignedInteger('amount_cents');
            $t->string('method', 10);                            // eft | card | admin
            $t->string('status', 10)->default('pending');       // pending | paid | cancelled
            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->string('gateway_reference')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
    }
};
