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
            // First, drop the existing enum constraint
            $table->dropColumn('status');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            // Add the updated enum with all status values actually used in the application
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
            // Restore the original enum
            $table->enum('status', ['pending', 'confirmed', 'harvested', 'delivered', 'cancelled'])
                ->default('pending')
                ->after('delivery_date');
        });
    }
};
