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
        // Rename the main items table to products
        Schema::rename('items', 'products');

        // Rename order_items to order_products
        Schema::rename('order_items', 'order_products');

        // Update foreign key references in price_variations table
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // Update foreign key references in order_products table
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update foreign key references in order_products table
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });

        // Update foreign key references in price_variations table
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });

        // Rename order_products back to order_items
        Schema::rename('order_products', 'order_items');

        // Rename products back to items
        Schema::rename('products', 'items');
    }
};
