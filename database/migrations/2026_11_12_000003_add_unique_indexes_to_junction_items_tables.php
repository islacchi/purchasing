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
        Schema::table('preplist_items', function (Blueprint $table) {
            $table->unique(['preplist_id', 'item_id'], 'preplist_items_preplist_item_id_unique');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->unique(['quotation_id', 'preplist_item_id'], 'quotation_items_quotation_preplist_item_unique');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unique(['purchase_order_id', 'quotation_item_id'], 'purchase_order_items_purchase_order_quote_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the original foreign-key index first — the unique indexes
        // are currently serving as FK-supporting indexes and cannot be
        // dropped until a plain index exists for each FK column.
        Schema::table('preplist_items', function (Blueprint $table) {
            $table->index('preplist_id', 'preplist_items_preplist_id_foreign');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->index('quotation_id', 'quotation_items_quotation_id_foreign');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->index('purchase_order_id', 'purchase_order_items_purchase_order_id_foreign');
        });

        Schema::table('preplist_items', function (Blueprint $table) {
            $table->dropUnique('preplist_items_preplist_item_id_unique');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropUnique('quotation_items_quotation_preplist_item_unique');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropUnique('purchase_order_items_purchase_order_quote_item_unique');
        });
    }
};
