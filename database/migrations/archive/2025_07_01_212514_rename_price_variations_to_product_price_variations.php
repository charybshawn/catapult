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
        // Only rename if the source table exists and target doesn't
        if (Schema::hasTable('price_variations') && !Schema::hasTable('product_price_variations')) {
            Schema::rename('price_variations', 'product_price_variations');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('product_price_variations', 'price_variations');
    }
};
