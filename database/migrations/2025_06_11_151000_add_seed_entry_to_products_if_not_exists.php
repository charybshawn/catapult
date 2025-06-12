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
        // Only add the column if it doesn't already exist
        if (!Schema::hasColumn('products', 'seed_entry_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Add seed_entry_id for single-variety products
                $table->foreignId('seed_entry_id')
                    ->nullable()
                    ->after('product_mix_id')
                    ->constrained('seed_entries')
                    ->nullOnDelete()
                    ->comment('For single-variety products');
                
                // Add index for better query performance
                $table->index(['seed_entry_id', 'active']);
            });
        }
        
        // Note: MySQL doesn't support check constraints on columns with foreign keys
        // We'll enforce the mutual exclusivity in the application layer instead
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'seed_entry_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['seed_entry_id']);
                $table->dropIndex(['seed_entry_id', 'active']);
                $table->dropColumn('seed_entry_id');
            });
        }
    }
};