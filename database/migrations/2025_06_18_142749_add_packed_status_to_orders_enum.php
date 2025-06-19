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
        Schema::table('orders', function (Blueprint $table) {
            // Drop the existing enum constraint
            $table->dropColumn('status');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            // Add the updated enum with packed status
            $table->enum('status', [
                'pending',
                'confirmed', 
                'processing',
                'planted',
                'harvested',
                'packed',
                'delivered',
                'cancelled',
                'completed',
                'template'
            ])->default('pending')->after('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the updated enum
            $table->dropColumn('status');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            // Restore the previous enum without packed
            $table->enum('status', [
                'pending',
                'confirmed', 
                'processing',
                'planted',
                'harvested',
                'delivered',
                'cancelled',
                'completed',
                'template'
            ])->default('pending')->after('delivery_date');
        });
    }
};
