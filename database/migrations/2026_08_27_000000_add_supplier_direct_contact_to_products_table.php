<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direct-contact detail for a product's supplier, alongside the existing
     * `supplier_phone` (2026_08_25_151839). Every column is nullable and
     * admin-editable on the product page: an admin reads these off Alibaba's
     * own TradeManager / company-profile UI — where the supplier chose to
     * publish them — and types them in. `suppliers:extract-contacts` may also
     * pre-fill the whatsapp/wechat/email columns when a value is already
     * sitting in the product's own scraped spec/description text, but it
     * never overwrites a human-entered value and never guesses one.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('supplier_contact_name')->nullable()->after('supplier_phone');
            $table->string('supplier_whatsapp')->nullable()->after('supplier_contact_name');
            $table->string('supplier_wechat_id')->nullable()->after('supplier_whatsapp');
            $table->string('supplier_email')->nullable()->after('supplier_wechat_id');
            $table->string('supplier_media_url')->nullable()->after('supplier_email');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_contact_name',
                'supplier_whatsapp',
                'supplier_wechat_id',
                'supplier_email',
                'supplier_media_url',
            ]);
        });
    }
};
