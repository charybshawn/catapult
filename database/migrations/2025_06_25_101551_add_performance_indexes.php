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
            $table->index(['user_id', 'delivery_date'], 'idx_orders_user_delivery');
            $table->index(['status', 'created_at'], 'idx_orders_status_created');
            $table->index(['billing_frequency', 'billing_period_end'], 'idx_orders_billing');
        });

        Schema::table('price_variations', function (Blueprint $table) {
            $table->index(['product_id', 'is_active', 'is_default'], 'idx_price_variations_product');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->index(['order_id', 'product_id'], 'idx_order_products_composite');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['active', 'category_id'], 'idx_products_active_category');
            $table->index(['master_seed_catalog_id'], 'idx_products_master_seed');
            $table->index(['is_visible_in_store', 'active'], 'idx_products_store_visible');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->index(['seed_entry_id'], 'idx_recipes_seed_entry');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['order_id', 'sent_at'], 'idx_invoices_order_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_delivery');
            $table->dropIndex('idx_orders_status_created');
            $table->dropIndex('idx_orders_billing');
        });

        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropIndex('idx_price_variations_product');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropIndex('idx_order_products_composite');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active_category');
            $table->dropIndex('idx_products_master_seed');
            $table->dropIndex('idx_products_store_visible');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex('idx_recipes_seed_entry');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_order_sent');
        });
    }
};