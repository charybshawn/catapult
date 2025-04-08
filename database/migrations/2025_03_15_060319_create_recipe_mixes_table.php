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
        Schema::create('recipe_mixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2); // Allows for precise percentages (e.g., 33.33%)
            $table->timestamps();
            
            // Ensure each recipe has only one entry per component recipe
            $table->unique(['recipe_id', 'component_recipe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_mixes');
    }
};
