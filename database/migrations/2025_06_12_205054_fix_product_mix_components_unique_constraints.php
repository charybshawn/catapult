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
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Drop the incorrect unique indexes that only have product_mix_id
            $table->dropIndex('product_mix_components_product_mix_id_seed_cultivar_id_unique');
            $table->dropIndex('product_mix_components_product_mix_id_seed_variety_id_unique');
            
            // Add a proper composite unique index
            $table->unique(['product_mix_id', 'master_cultivar_id'], 'mix_components_unique');
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
            
            // Re-add the old indexes (though they were incorrect)
            $table->unique(['product_mix_id'], 'product_mix_components_product_mix_id_seed_cultivar_id_unique');
            $table->unique(['product_mix_id'], 'product_mix_components_product_mix_id_seed_variety_id_unique');
        });
    }
};