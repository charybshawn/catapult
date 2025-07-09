<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes unused legacy database elements that were replaced
     * by newer implementations but were never cleaned up:
     * 
     * 1. seed_cultivars table - Replaced by the master catalog system (seed_entries)
     * 2. recipe_stages table - Model exists but is completely unused in the codebase
     * 3. planted_at column - Already migrated to planting_at in previous migration
     *    but this ensures it's removed if it still exists somehow
     */
    public function up(): void
    {
        // First, drop the foreign key constraint from seed_entries to seed_cultivars
        if (Schema::hasTable('seed_entries')) {
            Schema::table('seed_entries', function (Blueprint $table) {
                // Get the foreign key name
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'seed_entries' 
                    AND COLUMN_NAME = 'seed_cultivar_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (!empty($foreignKeys)) {
                    $table->dropForeign($foreignKeys[0]->CONSTRAINT_NAME);
                }
                
                if (Schema::hasColumn('seed_entries', 'seed_cultivar_id')) {
                    $table->dropColumn('seed_cultivar_id');
                }
            });
        }
        
        // Drop the seed_cultivars table if it exists
        // This table was replaced by the seed_entries master catalog system
        Schema::dropIfExists('seed_cultivars');
        
        // Drop the recipe_stages table if it exists
        // This table and its model exist but are completely unused in the codebase
        // The Recipe model has germination_days, blackout_days, and light_days columns
        // that handle the stage durations directly
        Schema::dropIfExists('recipe_stages');
        
        // Ensure planted_at column is removed from crops table
        // This was already handled in migration 2025_06_29_003245_migrate_planted_at_to_planting_at
        // but we'll double-check here for safety
        Schema::table('crops', function (Blueprint $table) {
            if (Schema::hasColumn('crops', 'planted_at')) {
                $table->dropColumn('planted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: We're not recreating these legacy structures in the down() method
     * because they've been replaced by better implementations. If a rollback
     * is needed, it should be done by restoring from a database backup rather
     * than recreating obsolete structures.
     */
    public function down(): void
    {
        // Recreate seed_cultivars table structure (for emergency rollback only)
        Schema::create('seed_cultivars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('common_name')->nullable();
            $table->string('cultivar_name')->nullable();
            $table->decimal('seed_density_grams_per_tray', 8, 2)->nullable();
            $table->integer('germination_days')->nullable();
            $table->integer('blackout_days')->nullable();
            $table->integer('light_days')->nullable();
            $table->decimal('expected_yield_grams', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Recreate recipe_stages table structure (for emergency rollback only)
        Schema::create('recipe_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->string('stage'); // germination, blackout, light
            $table->text('notes')->nullable();
            $table->decimal('temperature_min_celsius', 5, 2)->nullable();
            $table->decimal('temperature_max_celsius', 5, 2)->nullable();
            $table->integer('humidity_min_percent')->nullable();
            $table->integer('humidity_max_percent')->nullable();
            $table->timestamps();
            
            $table->index(['recipe_id', 'stage']);
        });
        
        // Recreate planted_at column (for emergency rollback only)
        Schema::table('crops', function (Blueprint $table) {
            if (!Schema::hasColumn('crops', 'planted_at')) {
                $table->timestamp('planted_at')->nullable()->after('stage_updated_at');
                $table->index('planted_at');
            }
        });
        
        // Restore the foreign key constraint from seed_entries to seed_cultivars
        Schema::table('seed_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('seed_entries', 'seed_cultivar_id')) {
                $table->foreignId('seed_cultivar_id')->nullable()->after('id')
                      ->constrained('seed_cultivars')->onDelete('cascade');
            }
        });
    }
};
