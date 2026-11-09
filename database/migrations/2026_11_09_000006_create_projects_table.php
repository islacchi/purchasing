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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procuring_entity_id')->constrained('procuring_entities');
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->string('reference_no')->index();
            $table->string('mode_of_procurement');
            $table->decimal('amount_awarded', 15, 2)->nullable();
            $table->date('date_awarded')->nullable();
            $table->smallInteger('delivery_days')->nullable();
            $table->date('delivery_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
