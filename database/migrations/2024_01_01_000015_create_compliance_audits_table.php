<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Supplier legitimacy
            $table->string('supplier_name');
            $table->unsignedSmallInteger('supplier_years')->nullable();
            $table->boolean('is_verified_supplier')->default(false);
            $table->boolean('has_trade_assurance')->default(false);

            // Technical / regulatory compliance
            $table->string('frequency_checked')->nullable(); // e.g. "134.2 kHz ISO 11784/5 compliant"
            $table->enum('icasa_status', ['pre_approved', 'exempt', 'requires_permit', 'flagged'])->nullable();
            $table->boolean('plug_type_checked')->default(false); // 220V/50Hz confirmed
            $table->string('battery_transport_cert')->nullable(); // e.g. "UN38.3"

            // Verdict
            $table->unsignedTinyInteger('risk_score')->default(0); // 0-100, higher = riskier
            $table->enum('audit_verdict', ['PASS', 'WARN', 'FAIL'])->nullable();
            $table->json('rejection_reasons')->nullable();
            $table->longText('raw_ai_analysis')->nullable();

            $table->timestamps();

            $table->index('audit_verdict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_audits');
    }
};
