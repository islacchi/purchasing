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
        Schema::create('preplist_item_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preplist_item_id')->constrained('preplist_items')->cascadeOnDelete();
            $table->foreignId('project_item_id')->constrained('project_items');
            $table->integer('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preplist_item_sources');
    }
};
