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
            $table->index('is_visible_in_store');
            $table->index('category_id');
            $table->index('product_mix_id');
        });

        // Add indexes to price_variations table - first check if the table exists
        if (Schema::hasTable('price_variations')) {
            Schema::table('price_variations', function (Blueprint $table) {
                // Check if columns exist before adding indexes
                if (Schema::hasColumn('price_variations', 'product_id') && 
                    Schema::hasColumn('price_variations', 'is_active')) {
                    $table->index(['product_id', 'is_active']);
                }
                
                if (Schema::hasColumn('price_variations', 'product_id') && 
                    Schema::hasColumn('price_variations', 'is_default')) {
                    $table->index(['product_id', 'is_default']);
                }
                
                if (Schema::hasColumn('price_variations', 'is_global') && 
                    Schema::hasColumn('price_variations', 'is_active')) {
                    $table->index(['is_global', 'is_active']);
                }
            });
        }

        // Add indexes to orders table - first check if the table exists
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Check if columns exist before adding indexes
                if (Schema::hasColumn('orders', 'status')) {
                    $table->index('status');
                }
                
                if (Schema::hasColumn('orders', 'order_date')) {
                    $table->index('order_date');
                }
                
                if (Schema::hasColumn('orders', 'user_id')) {
                    $table->index('user_id');
                }
            });
        }

        // Add indexes to crops table - first check if the table exists
        if (Schema::hasTable('crops')) {
            Schema::table('crops', function (Blueprint $table) {
                // Check if columns exist before adding indexes
                if (Schema::hasColumn('crops', 'active')) {
                    $table->index('active');
                }
                
                if (Schema::hasColumn('crops', 'recipe_id')) {
                    $table->index('recipe_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_visible_in_store']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['product_mix_id']);
        });

        // Remove indexes from price_variations table if it exists
        if (Schema::hasTable('price_variations')) {
            Schema::table('price_variations', function (Blueprint $table) {
                // Check if indexes exist before trying to drop them
                if (Schema::hasColumn('price_variations', 'product_id') && 
                    Schema::hasColumn('price_variations', 'is_active')) {
                    $table->dropIndex(['product_id', 'is_active']);
                }
                
                if (Schema::hasColumn('price_variations', 'product_id') && 
                    Schema::hasColumn('price_variations', 'is_default')) {
                    $table->dropIndex(['product_id', 'is_default']);
                }
                
                if (Schema::hasColumn('price_variations', 'is_global') && 
                    Schema::hasColumn('price_variations', 'is_active')) {
                    $table->dropIndex(['is_global', 'is_active']);
                }
            });
        }

        // Remove indexes from orders table if it exists
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Check if columns exist before dropping indexes
                if (Schema::hasColumn('orders', 'status')) {
                    $table->dropIndex(['status']);
                }
                
                if (Schema::hasColumn('orders', 'order_date')) {
                    $table->dropIndex(['order_date']);
                }
                
                if (Schema::hasColumn('orders', 'user_id')) {
                    $table->dropIndex(['user_id']);
                }
            });
        }

        // Remove indexes from crops table if it exists
        if (Schema::hasTable('crops')) {
            Schema::table('crops', function (Blueprint $table) {
                // Check if columns exist before dropping indexes
                if (Schema::hasColumn('crops', 'active')) {
                    $table->dropIndex(['active']);
                }
                
                if (Schema::hasColumn('crops', 'recipe_id')) {
                    $table->dropIndex(['recipe_id']);
                }
            });
        }
    }
};
