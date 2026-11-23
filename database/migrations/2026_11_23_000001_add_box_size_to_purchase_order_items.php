<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Exact piece accounting for boxed local purchases.
 *
 * A requirement of 100 boxes of 20 is really 2,000 base pieces. Purchases can
 * arrive in any pack (10 boxes of 40 = 400 pieces), so local purchase order
 * lines snapshot the requirement box size and receipts store base pieces.
 *
 * - purchase_order_items.box_size = requirement pack at ordering time. NULL
 *   keeps the line on the legacy plain-unit behaviour (quoted lines, boxless
 *   items, and local lines that already carry receipts).
 * - project_items.in_stock is converted from boxes to base pieces for items
 *   with a box requirement, so stock from any pack adds up exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedInteger('box_size')->nullable()->after('qty');
        });

        // On-hand stock was counted in the item's unit (boxes for boxed
        // items). Switch boxed rows to base pieces.
        DB::table('project_items')
            ->join('items', 'items.id', '=', 'project_items.item_id')
            ->whereNotNull('items.box_size')
            ->where('items.box_size', '>', 0)
            ->where('project_items.in_stock', '>', 0)
            ->select('project_items.id', 'project_items.in_stock', 'items.box_size')
            ->orderBy('project_items.id')
            ->get()
            ->each(function ($row) {
                DB::table('project_items')
                    ->where('id', $row->id)
                    ->update(['in_stock' => (int) $row->in_stock * (int) $row->box_size]);
            });

        // Existing local lines that have not been bought yet can adopt the
        // piece model safely. Lines with receipts keep their historical
        // plain-unit figures (box_size stays NULL).
        DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('items', 'items.id', '=', 'purchase_order_items.item_id')
            ->where('purchase_orders.source', 'local')
            ->whereNotNull('items.box_size')
            ->where('items.box_size', '>', 0)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('purchase_order_receipts')
                    ->whereColumn('purchase_order_receipts.purchase_order_item_id', 'purchase_order_items.id');
            })
            ->select('purchase_order_items.id', 'items.box_size')
            ->orderBy('purchase_order_items.id')
            ->get()
            ->each(function ($row) {
                DB::table('purchase_order_items')
                    ->where('id', $row->id)
                    ->update(['box_size' => (int) $row->box_size]);
            });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('box_size');
        });
    }
};
