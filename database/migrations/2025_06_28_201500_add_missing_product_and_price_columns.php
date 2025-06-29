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
        // Add missing column to products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'available_stock')) {
                $table->decimal('available_stock', 10, 2)->default(0)->after('track_inventory');
            }
        });
        
        // Add missing column to price_variations table
        Schema::table('price_variations', function (Blueprint $table) {
            if (!Schema::hasColumn('price_variations', 'fill_weight_grams')) {
                $table->decimal('fill_weight_grams', 8, 2)->nullable()->after('packaging_type_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['available_stock']);
        });
        
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropColumn(['fill_weight_grams']);
        });
    }
};