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
        Schema::table('seed_variations', function (Blueprint $table) {
            // Rename and add columns to match import expectations
            $table->renameColumn('size', 'size_description');
            $table->renameColumn('unit', 'original_weight_unit');
            $table->renameColumn('is_available', 'is_in_stock');
            
            // Add missing columns
            $table->string('sku')->nullable()->after('seed_entry_id');
            $table->decimal('weight_kg', 8, 4)->nullable()->after('size_description');
            $table->decimal('original_weight_value', 8, 4)->nullable()->after('weight_kg');
            $table->string('currency', 3)->default('USD')->after('current_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_variations', function (Blueprint $table) {
            // Reverse the column renames
            $table->renameColumn('size_description', 'size');
            $table->renameColumn('original_weight_unit', 'unit');
            $table->renameColumn('is_in_stock', 'is_available');
            
            // Drop added columns
            $table->dropColumn([
                'sku',
                'weight_kg', 
                'original_weight_value',
                'currency'
            ]);
        });
    }
};
