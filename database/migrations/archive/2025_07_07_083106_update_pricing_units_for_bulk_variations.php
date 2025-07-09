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
        // Update existing bulk variations to have proper pricing_unit
        DB::table('product_price_variations')
            ->where('pricing_type', 'bulk')
            ->where(function($query) {
                $query->whereNull('pricing_unit')
                      ->orWhere('pricing_unit', '');
            })
            ->update(['pricing_unit' => 'per_lb']);
            
        // Update variations that have "Bulk" in their name but no pricing_unit
        DB::table('product_price_variations')
            ->where('name', 'like', '%Bulk%')
            ->where(function($query) {
                $query->whereNull('pricing_unit')
                      ->orWhere('pricing_unit', '');
            })
            ->update(['pricing_unit' => 'per_lb']);
            
        // Set default pricing_unit for non-bulk items
        DB::table('product_price_variations')
            ->where(function($query) {
                $query->whereNull('pricing_unit')
                      ->orWhere('pricing_unit', '');
            })
            ->update(['pricing_unit' => 'per_item']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We can't really reverse this migration as we don't know
        // which pricing_units were originally null
    }
};