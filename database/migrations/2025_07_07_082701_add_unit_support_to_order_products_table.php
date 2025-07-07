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
        Schema::table('order_products', function (Blueprint $table) {
            // Change quantity from integer to decimal to support fractional quantities
            $table->decimal('quantity_decimal', 10, 3)->default(0)->after('price_variation_id');
            
            // Add unit tracking
            $table->string('quantity_unit', 20)->default('units')->after('quantity_decimal');
            
            // Add base quantity in grams for universal calculations
            $table->decimal('quantity_in_grams', 10, 3)->nullable()->after('quantity_unit');
        });
        
        // Migrate existing integer quantities to decimal
        DB::table('order_products')->update([
            'quantity_decimal' => DB::raw('quantity')
        ]);
        
        // After data migration, drop old column and rename new one
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
        
        Schema::table('order_products', function (Blueprint $table) {
            $table->renameColumn('quantity_decimal', 'quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First add back integer column
        Schema::table('order_products', function (Blueprint $table) {
            $table->integer('quantity_int')->default(0)->after('price_variation_id');
        });
        
        // Copy data back, rounding to nearest integer
        DB::table('order_products')->update([
            'quantity_int' => DB::raw('ROUND(quantity)')
        ]);
        
        // Drop new columns
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'quantity_unit', 'quantity_in_grams']);
        });
        
        // Rename integer column back
        Schema::table('order_products', function (Blueprint $table) {
            $table->renameColumn('quantity_int', 'quantity');
        });
    }
};