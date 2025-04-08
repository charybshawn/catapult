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
        Schema::table('items', function (Blueprint $table) {
            // Update recipes references to be nullable
            $table->foreignId('recipe_id')->nullable()->change();
            
            // Update the foreign key constraint to use nullOnDelete
            $table->dropForeign(['recipe_id']);
            $table->foreign('recipe_id')
                ->references('id')
                ->on('recipes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Revert recipes references to be required
            $table->dropForeign(['recipe_id']);
            $table->foreign('recipe_id')
                ->references('id')
                ->on('recipes')
                ->restrictOnDelete();
            
            $table->foreignId('recipe_id')->nullable(false)->change();
        });
    }
};
