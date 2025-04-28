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
        Schema::table('consumables', function (Blueprint $table) {
            // Add reference to seed_varieties table
            $table->foreignId('seed_variety_id')
                ->nullable()
                ->after('packaging_type_id')
                ->constrained('seed_varieties')
                ->nullOnDelete();
            
            // Add index for faster lookups on type='seed'
            $table->index(['type', 'seed_variety_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            // Remove the foreign key constraint
            $table->dropForeign(['seed_variety_id']);
            
            // Remove the index
            $table->dropIndex(['type', 'seed_variety_id']);
            
            // Drop the column
            $table->dropColumn('seed_variety_id');
        });
    }
};
