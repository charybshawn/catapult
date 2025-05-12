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
        Schema::dropIfExists('recipe_mixes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('recipe_mixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
            
            $table->unique(['recipe_id', 'component_recipe_id']);
        });
    }
}; 