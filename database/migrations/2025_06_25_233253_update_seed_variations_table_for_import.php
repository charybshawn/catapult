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
            // Keep original column names from main branch
            // Only add new columns needed for import functionality
            
            // Add missing columns
            $table->string('sku')->nullable()->after('seed_entry_id');
            $table->decimal('weight_kg', 8, 4)->nullable()->after('size');
            $table->decimal('original_weight_value', 8, 4)->nullable()->after('weight_kg');
            $table->string('original_weight_unit')->nullable()->after('original_weight_value');
            $table->string('currency', 3)->default('USD')->after('current_price');
            
            // Add alias columns for backward compatibility if needed
            $table->string('size_description')->virtualAs('size')->nullable();
            $table->boolean('is_in_stock')->virtualAs('is_available')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_variations', function (Blueprint $table) {
            // Drop virtual columns
            $table->dropColumn(['size_description', 'is_in_stock']);
            
            // Drop added columns
            $table->dropColumn([
                'sku',
                'weight_kg', 
                'original_weight_value',
                'original_weight_unit',
                'currency'
            ]);
        });
    }
};
