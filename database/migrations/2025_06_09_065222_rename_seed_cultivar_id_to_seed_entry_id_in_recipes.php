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
        Schema::table('recipes', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['seed_cultivar_id']);
            
            // Rename the column
            $table->renameColumn('seed_cultivar_id', 'seed_entry_id');
            
            // Re-add the foreign key constraint with new column name
            $table->foreign('seed_entry_id')->references('id')->on('seed_entries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['seed_entry_id']);
            
            // Rename the column back
            $table->renameColumn('seed_entry_id', 'seed_cultivar_id');
            
            // Re-add the old foreign key constraint
            $table->foreign('seed_cultivar_id')->references('id')->on('seed_entries')->onDelete('set null');
        });
    }
};
