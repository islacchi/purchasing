<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local purchases have no quotation item between the demand (preplist line)
 * and the purchase order line, so the link is now stored directly. Enables
 * allocation rebuilds and on-hand stock bumps for local purchase orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('preplist_item_id')
                ->nullable()
                ->after('quotation_item_id')
                ->constrained('preplist_items');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['preplist_item_id']);
            $table->dropColumn('preplist_item_id');
        });
    }
};
