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
        // First, clean up orphaned inventory entries (no price variation)
        DB::table('product_inventories')
            ->whereNull('price_variation_id')
            ->delete();
            
        // Clean up duplicate entries (same product + price variation combo)
        // Keep the one with highest quantity
        DB::statement('
            DELETE pi1 FROM product_inventories pi1
            INNER JOIN product_inventories pi2 
            WHERE pi1.product_id = pi2.product_id 
            AND pi1.price_variation_id = pi2.price_variation_id
            AND pi1.id < pi2.id
        ');
        
        // Now make price_variation_id required
        Schema::table('product_inventories', function (Blueprint $table) {
            // First drop the nullable foreign key
            $table->dropForeign(['price_variation_id']);
            
            // Make the column not nullable
            $table->foreignId('price_variation_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('price_variation_id')->references('id')->on('price_variations')->onDelete('cascade');
            
            // Add unique constraint to prevent duplicates
            $table->unique(['product_id', 'price_variation_id'], 'product_price_variation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('product_price_variation_unique');
            
            // Drop the foreign key
            $table->dropForeign(['price_variation_id']);
            
            // Make the column nullable again
            $table->foreignId('price_variation_id')->nullable()->change();
            
            // Re-add the foreign key with set null on delete
            $table->foreign('price_variation_id')->references('id')->on('price_variations')->onDelete('set null');
        });
    }
};
