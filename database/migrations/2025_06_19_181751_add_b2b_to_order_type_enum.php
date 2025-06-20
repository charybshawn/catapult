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
            // Add 'b2b' to the order_type enum to match business logic expectations
            $table->enum('order_type', ['farmers_market', 'b2b', 'b2b_recurring', 'website_immediate'])
                  ->default('website_immediate')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('order_type', ['farmers_market', 'b2b_recurring', 'website_immediate'])
                  ->default('website_immediate')
                  ->change();
        });
    }
};