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
        Schema::table('consumables', function (Blueprint $table) {
            // Change initial_stock from integer to decimal with 3 decimal places
            $table->decimal('initial_stock', 12, 3)->change();
            
            // Change restock_threshold from integer to decimal with 3 decimal places
            $table->decimal('restock_threshold', 12, 3)->change();
            
            // Change restock_quantity from integer to decimal with 3 decimal places
            $table->decimal('restock_quantity', 12, 3)->change();
            
            // Update consumed_quantity precision to 3 decimal places
            $table->decimal('consumed_quantity', 12, 3)->change();
            
            // Update quantity_per_unit precision to 3 decimal places
            $table->decimal('quantity_per_unit', 12, 3)->nullable()->change();
            
            // Update total_quantity precision to 3 decimal places
            $table->decimal('total_quantity', 12, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            // Change back to original types
            $table->integer('initial_stock')->change();
            $table->integer('restock_threshold')->change();
            $table->integer('restock_quantity')->change();
            $table->decimal('consumed_quantity', 12, 2)->change();
            $table->decimal('quantity_per_unit', 10, 2)->nullable()->change();
            $table->decimal('total_quantity', 12, 2)->change();
        });
    }
};
