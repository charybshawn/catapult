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
        // Update existing products that have null wholesale_discount_percentage to 25.00
        DB::table('products')
            ->whereNull('wholesale_discount_percentage')
            ->update(['wholesale_discount_percentage' => 25.00]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to null for rollback
        DB::table('products')
            ->where('wholesale_discount_percentage', 25.00)
            ->update(['wholesale_discount_percentage' => null]);
    }
};
