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
        // Add indexes to products table
        Schema::table('products', function (Blueprint $table) {
            $table->index('active');
            $table->index('is_visible_in_store');
            $table->index('category_id');
            $table->index('product_mix_id');
        });

        // Add indexes to price_variations table
        Schema::table('price_variations', function (Blueprint $table) {
            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'is_default']);
            $table->index(['is_global', 'is_active']);
        });

        // Add indexes to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('order_date');
            $table->index('user_id');
        });

        // Add indexes to crops table
        Schema::table('crops', function (Blueprint $table) {
            $table->index('active');
            $table->index('recipe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropIndex(['is_visible_in_store']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['product_mix_id']);
        });

        // Remove indexes from price_variations table
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'is_active']);
            $table->dropIndex(['product_id', 'is_default']);
            $table->dropIndex(['is_global', 'is_active']);
        });

        // Remove indexes from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['order_date']);
            $table->dropIndex(['user_id']);
        });

        // Remove indexes from crops table
        Schema::table('crops', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropIndex(['recipe_id']);
        });
    }
};
