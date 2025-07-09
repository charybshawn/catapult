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
        // Check if constraints exist and drop them
        $constraints = DB::select('SHOW INDEX FROM product_mix_components WHERE Key_name = "product_mix_components_product_mix_id_seed_entry_id_unique"');
        if (count($constraints) > 0) {
            DB::statement('ALTER TABLE product_mix_components DROP INDEX product_mix_components_product_mix_id_seed_entry_id_unique');
        }
        
        // Check if foreign key exists and drop it
        $foreignKeys = DB::select('SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "product_mix_components" AND CONSTRAINT_NAME = "product_mix_components_seed_entry_id_foreign"');
        if (count($foreignKeys) > 0) {
            DB::statement('ALTER TABLE product_mix_components DROP FOREIGN KEY product_mix_components_seed_entry_id_foreign');
        }
        
        // Drop the seed_entry_id column if it exists
        if (Schema::hasColumn('product_mix_components', 'seed_entry_id')) {
            DB::statement('ALTER TABLE product_mix_components DROP COLUMN seed_entry_id');
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
