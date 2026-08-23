<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplier-trust and admin-verification fields for the sourcing → publish
 * workflow: real per-product supplier identity/cost (replacing the flat
 * defaults every row previously shared), plus the ICASA/radio/datasheet
 * checks the admin "Publishing Readiness" panel gates on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('compatibility_notes')->nullable()->after('included_items');
            $table->text('requirements_notes')->nullable()->after('compatibility_notes');
            $table->string('warranty_terms')->nullable()->default('12-month limited warranty against manufacturing defects.')->after('requirements_notes');
            $table->longText('technical_specs_json')->nullable()->after('warranty_terms');

            $table->decimal('customs_clearance_zar', 10, 2)->nullable()->default(150.00)->after('domestic_delivery_zar');

            $table->decimal('supplier_cost_usd', 10, 2)->nullable()->default(0.00)->after('retail_price_zar');
            $table->decimal('exchange_rate', 10, 2)->nullable()->default(18.50)->after('supplier_cost_usd');
            $table->string('supplier_name')->nullable()->default('Alibaba Supplier (Vetting Pending)')->after('exchange_rate');
            $table->text('supplier_url')->nullable()->after('supplier_name');
            $table->timestamp('supplier_last_checked_at')->nullable()->useCurrent()->after('supplier_url');

            $table->decimal('import_contingency_pct', 5, 2)->nullable()->default(5.00)->after('supplier_last_checked_at');
            $table->decimal('insurance_cost_zar', 10, 2)->nullable()->default(45.00)->after('import_contingency_pct');
            $table->decimal('payment_fees_zar', 10, 2)->nullable()->default(65.00)->after('insurance_cost_zar');

            $table->string('stock_availability_type', 50)->nullable()->default('available_to_order')->after('stock_status');

            $table->string('verification_tier', 50)->nullable()->default('supplier_supplied')->after('status');
            $table->string('icasa_status', 50)->nullable()->default('verification_required')->after('verification_tier');
            $table->boolean('radio_frequency_confirmed')->nullable()->default(false)->after('icasa_status');
            $table->boolean('datasheet_uploaded')->nullable()->default(false)->after('radio_frequency_confirmed');

            $table->timestamp('published_at')->nullable()->after('is_active');
            $table->timestamp('approved_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'compatibility_notes', 'requirements_notes', 'warranty_terms', 'technical_specs_json',
                'customs_clearance_zar',
                'supplier_cost_usd', 'exchange_rate', 'supplier_name', 'supplier_url', 'supplier_last_checked_at',
                'import_contingency_pct', 'insurance_cost_zar', 'payment_fees_zar',
                'stock_availability_type',
                'verification_tier', 'icasa_status', 'radio_frequency_confirmed', 'datasheet_uploaded',
                'published_at', 'approved_at',
            ]);
        });
    }
};
