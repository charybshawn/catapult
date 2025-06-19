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
        // Add cascade delete to product_inventories
        Schema::table('product_inventories', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            $table->dropForeign(['product_id']);
            
            // Add new foreign key with cascade delete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        // Add cascade delete to price_variations
        Schema::table('price_variations', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            try {
                $table->dropForeign(['product_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            // Add new foreign key with cascade delete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        // Add cascade delete to inventory_transactions
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            try {
                $table->dropForeign(['product_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            // Add new foreign key with cascade delete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        // Add cascade delete to inventory_reservations
        Schema::table('inventory_reservations', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            try {
                $table->dropForeign(['product_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            // Add new foreign key with cascade delete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        // Add cascade delete to product_photos
        Schema::table('product_photos', function (Blueprint $table) {
            // Drop existing foreign key if it exists
            try {
                $table->dropForeign(['product_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            // Add new foreign key with cascade delete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore foreign keys without cascade delete
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::table('price_variations', function (Blueprint $table) {
            try {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')->on('products');
            } catch (\Exception $e) {
                // Handle if foreign key doesn't exist
            }
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            try {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')->on('products');
            } catch (\Exception $e) {
                // Handle if foreign key doesn't exist
            }
        });

        Schema::table('inventory_reservations', function (Blueprint $table) {
            try {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')->on('products');
            } catch (\Exception $e) {
                // Handle if foreign key doesn't exist
            }
        });

        Schema::table('product_photos', function (Blueprint $table) {
            try {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')->on('products');
            } catch (\Exception $e) {
                // Handle if foreign key doesn't exist
            }
        });
    }
};