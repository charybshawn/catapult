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
            // Check if the column exists before trying to drop it
            if (Schema::hasColumn('items', 'recipe_id')) {
                // Try to drop the foreign key if it exists
                $table->dropForeign(['recipe_id']);
                $table->dropColumn('recipe_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'recipe_id')) {
                $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }
}; 