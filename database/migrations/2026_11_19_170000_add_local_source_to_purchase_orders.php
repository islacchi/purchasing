<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local (no-quotation) purchases hang off the same tables as awarded-quotation
 * purchases: a demand line can now be satisfied straight from a shelf instead
 * of from a supplier award.
 *
 * - purchase_orders.quotation_id is NULLABLE so a direct (local) order can exist.
 * - purchase_orders.source distinguishes them ('quotation' | 'local').
 * - purchase_order_items.quotation_item_id is NULLABLE for the same reason.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
            $table->unsignedBigInteger('quotation_id')->nullable()->change();
            $table->foreign('quotation_id')->references('id')->on('quotations');
            $table->string('source')->default('quotation')->after('quotation_id');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['quotation_item_id']);
            $table->unsignedBigInteger('quotation_item_id')->nullable()->change();
            $table->foreign('quotation_item_id')->references('id')->on('quotation_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['quotation_item_id']);
            $table->unsignedBigInteger('quotation_item_id')->nullable(false)->change();
            $table->foreign('quotation_item_id')->references('id')->on('quotation_items');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->dropForeign(['quotation_id']);
            $table->unsignedBigInteger('quotation_id')->nullable(false)->change();
            $table->foreign('quotation_id')->references('id')->on('quotations');
        });
    }
};
