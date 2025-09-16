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
        // First, migrate any fill_weight values to fill_weight_grams if fill_weight_grams is null
        DB::statement('
            UPDATE product_price_variations 
            SET fill_weight_grams = fill_weight 
            WHERE fill_weight IS NOT NULL 
            AND fill_weight_grams IS NULL
        ');
        
        // Then drop the redundant fill_weight column
        Schema::table('product_price_variations', function (Blueprint $table) {
            $table->dropColumn('fill_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_price_variations', function (Blueprint $table) {
            // Re-add fill_weight column
            $table->decimal('fill_weight', 10, 2)->nullable()->after('sku');
            
            // Copy data back from fill_weight_grams
            DB::statement('
                UPDATE product_price_variations 
                SET fill_weight = fill_weight_grams 
                WHERE fill_weight_grams IS NOT NULL
            ');
        });
    }
};
