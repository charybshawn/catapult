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
            // Drop foreign key first
            $table->dropForeign(['recipe_id']);
            // Then drop the column
            $table->dropColumn('recipe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Add back the recipe_id column
            $table->foreignId('recipe_id')->nullable()->after('id');
            // Add back the foreign key constraint
            $table->foreign('recipe_id')
                ->references('id')
                ->on('recipes')
                ->nullOnDelete();
        });
    }
};
