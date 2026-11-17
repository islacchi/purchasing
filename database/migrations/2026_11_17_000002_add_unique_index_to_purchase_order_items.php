<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One awarded quotation line ever feeds one PO line. Without this
        // global uniqueness, two concurrent checkouts can both win the same
        // "already on a purchase order?" check and create two POs from one
        // quotation line.
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unique('quotation_item_id', 'purchase_order_items_quotation_item_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropUnique('purchase_order_items_quotation_item_id_unique');
        });
    }
};
