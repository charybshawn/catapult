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
        // Add foreign key constraints that couldn't be added during table creation due to order dependencies
        
        // Add foreign key from seed_variations to consumables
        Schema::table('seed_variations', function (Blueprint $table) {
            $table->foreign('consumable_id')->references('id')->on('consumables')->onDelete('set null');
        });
        
        // Add foreign keys from consumables to seed catalog tables
        Schema::table('consumables', function (Blueprint $table) {
            $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog')->onDelete('set null');
            $table->foreign('master_cultivar_id')->references('id')->on('master_cultivars')->onDelete('set null');
        });
        
        // Add foreign keys from recipes
        Schema::table('recipes', function (Blueprint $table) {
            $table->foreign('soil_consumable_id')->references('id')->on('consumables')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['soil_consumable_id']);
        });
        
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropForeign(['master_seed_catalog_id']);
            $table->dropForeign(['master_cultivar_id']);
        });
        
        Schema::table('seed_variations', function (Blueprint $table) {
            $table->dropForeign(['consumable_id']);
        });
    }
};