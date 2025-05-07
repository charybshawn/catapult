<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes item_id nullable to support global price variations
     */
    public function up(): void
    {
        // First drop the foreign key constraint
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });
        
        // Then modify the column to be nullable
        Schema::table('price_variations', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->change();
        });
        
        // Finally re-add the foreign key constraint with nullability
        Schema::table('price_variations', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First drop the foreign key constraint
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });
        
        // Then modify the column to be non-nullable again
        Schema::table('price_variations', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable(false)->change();
        });
        
        // Finally re-add the foreign key constraint without nullability
        Schema::table('price_variations', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }
};
