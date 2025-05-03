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
        Schema::create('planting_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('planting_date')->comment('Date when trays should be planted');
            $table->date('target_harvest_date')->comment('Target date for harvest');
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            $table->integer('trays_required')->default(1)->comment('Number of trays needed');
            $table->integer('trays_planted')->default(0)->comment('Number of trays already planted');
            $table->enum('status', [
                'pending', 'partially_planted', 'fully_planted', 'completed', 'cancelled'
            ])->default('pending');
            $table->json('related_orders')->nullable()->comment('Order IDs that this planting is for');
            $table->json('related_recurring_orders')->nullable()->comment('Recurring Order IDs this planting is for');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planting_schedules');
    }
}; 