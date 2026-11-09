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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('preplist_item_id')->constrained('preplist_items');
            $table->smallInteger('min_remaining_months')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->smallInteger('offered_shelf_life_months')->nullable();
            $table->integer('available_qty')->nullable();
            $table->timestamp('quoted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
