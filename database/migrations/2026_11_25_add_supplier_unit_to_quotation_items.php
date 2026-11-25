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
            // The unit the supplier filled in the quotation's "Supplier's
            // Unit" column, when they filled one. Stored so the purchase
            // order generated from the quotation uses the supplier's unit.
            $table->string('supplier_unit')->nullable()->after('supplier_box_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('supplier_unit');
        });
    }
};
