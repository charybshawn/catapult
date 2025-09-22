<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the crop_batches table to properly track crop batches.
     */
    public function up(): void
    {
        // Create the crop_batches table
        Schema::create('crop_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_id');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('recipe_id')->references('id')->on('recipes');
            
            // Index for performance
            $table->index('recipe_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_batches');
    }
};