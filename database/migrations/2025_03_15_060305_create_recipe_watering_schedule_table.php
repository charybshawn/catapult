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
        Schema::create('recipe_watering_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->integer('day_number');
            $table->integer('water_amount_ml');
            $table->boolean('needs_liquid_fertilizer')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Ensure each recipe has only one entry per day
            $table->unique(['recipe_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_watering_schedule');
    }
};
