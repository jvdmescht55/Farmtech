<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Manually-sourced supplier WhatsApp/mobile number — takes priority over
            // whatever SupplierContactExtractor can pull from scraped spec/description
            // text (see that class), which finds a usable number for only a small
            // minority of listings since Alibaba routes contact through its own
            // encrypted messaging by design.
            $table->string('supplier_phone')->nullable()->after('supplier_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('supplier_phone');
        });
    }
};
