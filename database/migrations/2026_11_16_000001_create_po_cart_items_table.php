<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user shopping cart of awarded quotation lines to be ordered.
     *
     * Rows reference the quotation item (not a stored snapshot), so the
     * cart always reflects the current awarded quotation content; the
     * checkout re-validates prices and quantities before creating draft
     * purchase orders.
     */
    public function up(): void
    {
        Schema::create('po_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->constrained('quotation_items')->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'quotation_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_cart_items');
    }
};
