<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear any existing records first
        DB::table('product_mix_components')->delete();
        
        // Drop the problematic unique constraint
        try {
            DB::statement('ALTER TABLE product_mix_components DROP INDEX product_mix_components_product_mix_id_seed_entry_id_unique');
        } catch (Exception $e) {
            // May not exist
        }
        
        // Drop the foreign key constraint
        try {
            DB::statement('ALTER TABLE product_mix_components DROP FOREIGN KEY product_mix_components_seed_entry_id_foreign');
        } catch (Exception $e) {
            // May not exist
        }
        
        // Drop the seed_entry_id column
        try {
            DB::statement('ALTER TABLE product_mix_components DROP COLUMN seed_entry_id');
        } catch (Exception $e) {
            // May not exist
        }
        
        // Add master_seed_catalog_id column if it doesn't exist
        if (!Schema::hasColumn('product_mix_components', 'master_seed_catalog_id')) {
            DB::statement('ALTER TABLE product_mix_components ADD COLUMN master_seed_catalog_id BIGINT UNSIGNED NULL AFTER product_mix_id');
            DB::statement('ALTER TABLE product_mix_components ADD CONSTRAINT product_mix_components_master_seed_catalog_id_foreign FOREIGN KEY (master_seed_catalog_id) REFERENCES master_seed_catalog (id)');
        }
        
        // Add cultivar column if it doesn't exist
        if (!Schema::hasColumn('product_mix_components', 'cultivar')) {
            DB::statement('ALTER TABLE product_mix_components ADD COLUMN cultivar VARCHAR(255) NULL AFTER master_seed_catalog_id');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This rollback is not safe as we cleared data
        // Add seed_entry_id column back
        DB::statement('ALTER TABLE product_mix_components ADD COLUMN seed_entry_id BIGINT UNSIGNED NULL AFTER product_mix_id');
        
        // Add foreign key constraint back
        DB::statement('ALTER TABLE product_mix_components ADD CONSTRAINT product_mix_components_seed_entry_id_foreign FOREIGN KEY (seed_entry_id) REFERENCES seed_entries (id)');
        
        // Add unique constraint back
        DB::statement('ALTER TABLE product_mix_components ADD UNIQUE KEY product_mix_components_product_mix_id_seed_entry_id_unique (product_mix_id, seed_entry_id)');
        
        // Remove new columns
        DB::statement('ALTER TABLE product_mix_components DROP FOREIGN KEY product_mix_components_master_seed_catalog_id_foreign');
        DB::statement('ALTER TABLE product_mix_components DROP COLUMN master_seed_catalog_id');
        DB::statement('ALTER TABLE product_mix_components DROP COLUMN cultivar');
    }
};
