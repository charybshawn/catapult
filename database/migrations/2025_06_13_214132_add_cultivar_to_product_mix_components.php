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
        Schema::table('product_mix_components', function (Blueprint $table) {
            $table->string('cultivar')->nullable()->after('master_seed_catalog_id');
            
            // Drop the old unique constraint
            $indexExists = DB::select("SHOW INDEX FROM product_mix_components WHERE Key_name = 'mix_catalog_unique'");
            if ($indexExists) {
                $table->dropIndex('mix_catalog_unique');
            }
            
            // Add new unique constraint that includes cultivar
            $table->unique(['product_mix_id', 'master_seed_catalog_id', 'cultivar'], 'mix_catalog_cultivar_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropIndex('mix_catalog_cultivar_unique');
            
            // Restore the old unique constraint
            $table->unique(['product_mix_id', 'master_seed_catalog_id'], 'mix_catalog_unique');
            
            // Drop the cultivar column
            $table->dropColumn('cultivar');
        });
    }
};