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
        Schema::table('crops', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['recipe_id']);
            
            // Add the new foreign key with cascadeOnDelete
            $table->foreign('recipe_id')
                ->references('id')
                ->on('recipes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // Drop the cascade foreign key
            $table->dropForeign(['recipe_id']);
            
            // Restore the original foreign key with restrictOnDelete
            $table->foreign('recipe_id')
                ->references('id')
                ->on('recipes')
                ->restrictOnDelete();
        });
    }
};
