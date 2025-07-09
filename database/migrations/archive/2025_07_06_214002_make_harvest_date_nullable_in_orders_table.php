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
            // Make harvest_date nullable since recurring orders don't have specific harvest dates
            $table->date('harvest_date')->nullable()->change();
            
            // Also make delivery_date nullable for consistency
            $table->date('delivery_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert to non-nullable (but this could cause issues if there are null values)
            $table->date('harvest_date')->nullable(false)->change();
            $table->date('delivery_date')->nullable(false)->change();
        });
    }
};
