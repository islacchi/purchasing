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
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('offered_shelf_life_months');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('offered_shelf_life_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->smallInteger('offered_shelf_life_months')->nullable()->after('available_qty');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->smallInteger('offered_shelf_life_months')->nullable()->after('min_remaining_months');
        });
    }
};
