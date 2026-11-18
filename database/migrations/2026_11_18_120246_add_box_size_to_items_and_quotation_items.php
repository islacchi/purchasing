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
        Schema::table('items', function (Blueprint $table) {
            // Units per box for the item master. NULL means the item is not
            // packaged to a box quantity (no pack-size maths applied).
            $table->unsignedInteger('box_size')->nullable()->after('default_min_shelf_life_months');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            // Units per box the supplier chose to quote, when it differs from
            // the required packaging. Filled at upload or by staff on the
            // quotation show page.
            $table->unsignedInteger('supplier_box_size')->nullable()->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('box_size');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('supplier_box_size');
        });
    }
};
