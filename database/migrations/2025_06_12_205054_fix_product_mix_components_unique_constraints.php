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
        // Check if columns already exist before adding them
        if (!Schema::hasColumn('product_mix_components', 'master_seed_catalog_id')) {
            Schema::table('product_mix_components', function (Blueprint $table) {
                // Add missing master_seed_catalog_id column
                $table->unsignedBigInteger('master_seed_catalog_id')->after('product_mix_id');
                $table->string('cultivar')->after('master_seed_catalog_id');
                
                // Add foreign key constraint
                $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog')->onDelete('cascade');
            });
        }
        
        // Check for and drop existing incorrect indexes using raw SQL
        $indexes = DB::select("SHOW INDEX FROM product_mix_components WHERE Key_name IN ('product_mix_components_product_mix_id_seed_cultivar_id_unique', 'product_mix_components_product_mix_id_seed_variety_id_unique')");
        
        foreach ($indexes as $index) {
            DB::statement("ALTER TABLE product_mix_components DROP INDEX `{$index->Key_name}`");
        }
        
        // Add the proper composite unique index if it doesn't exist
        $existingIndex = DB::select("SHOW INDEX FROM product_mix_components WHERE Key_name = 'mix_components_unique'");
        if (empty($existingIndex)) {
            Schema::table('product_mix_components', function (Blueprint $table) {
                $table->unique(['product_mix_id', 'master_seed_catalog_id', 'cultivar'], 'mix_components_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Drop the new unique index
            $table->dropIndex('mix_components_unique');
            
            // Drop foreign key
            $table->dropForeign(['master_seed_catalog_id']);
            
            // Drop the added columns
            $table->dropColumn(['master_seed_catalog_id', 'cultivar']);
            
            // Re-add the old indexes (though they were incorrect)
            $table->unique(['product_mix_id'], 'product_mix_components_product_mix_id_seed_cultivar_id_unique');
            $table->unique(['product_mix_id'], 'product_mix_components_product_mix_id_seed_variety_id_unique');
        });
    }
};