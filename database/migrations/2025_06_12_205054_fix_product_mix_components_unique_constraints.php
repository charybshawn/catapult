<?php

use Exception;
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
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Add missing master_seed_catalog_id column
            $table->unsignedBigInteger('master_seed_catalog_id')->after('product_mix_id');
            $table->string('cultivar')->after('master_seed_catalog_id');
            
            // Add foreign key constraint
            $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog')->onDelete('cascade');
            
            // Drop the incorrect unique indexes that only have product_mix_id (if they exist)
            try {
                $table->dropIndex('product_mix_components_product_mix_id_seed_cultivar_id_unique');
            } catch (Exception $e) {
                // Index might not exist
            }
            try {
                $table->dropIndex('product_mix_components_product_mix_id_seed_variety_id_unique');
            } catch (Exception $e) {
                // Index might not exist
            }
            
            // Add a proper composite unique index
            $table->unique(['product_mix_id', 'master_seed_catalog_id', 'cultivar'], 'mix_components_unique');
        });
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