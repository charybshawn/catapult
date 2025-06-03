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
        // Add indexes for crops table - frequently queried fields
        Schema::table('crops', function (Blueprint $table) {
            $table->index('planted_at', 'crops_planted_at_index');
            $table->index(['current_stage', 'planted_at'], 'crops_stage_planted_index');
            $table->index('germination_at', 'crops_germination_at_index');
            $table->index('harvested_at', 'crops_harvested_at_index');
            $table->index(['recipe_id', 'planted_at', 'current_stage'], 'crops_batch_grouping_index');
        });

        // Add indexes for orders table - time-based queries
        Schema::table('orders', function (Blueprint $table) {
            $table->index('harvest_date', 'orders_harvest_date_index');
            $table->index('delivery_date', 'orders_delivery_date_index');
            $table->index(['status', 'harvest_date'], 'orders_status_harvest_index');
            $table->index(['user_id', 'created_at'], 'orders_user_created_index');
        });

        // Add indexes for seed-related tables
        Schema::table('seed_price_history', function (Blueprint $table) {
            $table->index(['seed_variation_id', 'scraped_at'], 'seed_price_variation_scraped_index');
            $table->index('scraped_at', 'seed_price_scraped_at_index');
        });

        Schema::table('seed_variations', function (Blueprint $table) {
            $table->index('last_checked_at', 'seed_variations_last_checked_index');
            $table->index(['is_in_stock', 'last_checked_at'], 'seed_variations_stock_status_index');
            $table->index(['seed_entry_id', 'size_description'], 'seed_variations_entry_size_index');
        });

        Schema::table('seed_entries', function (Blueprint $table) {
            $table->index(['seed_cultivar_id', 'supplier_id'], 'seed_entries_cultivar_supplier_index');
            $table->index('supplier_id', 'seed_entries_supplier_index');
        });

        // Add indexes for consumables - inventory queries
        Schema::table('consumables', function (Blueprint $table) {
            $table->index(['type', 'is_active'], 'consumables_type_active_index');
            $table->index(['supplier_id', 'type'], 'consumables_supplier_type_index');
            $table->index('packaging_type_id', 'consumables_packaging_type_index');
        });

        // Add indexes for order packagings - aggregation queries
        Schema::table('order_packagings', function (Blueprint $table) {
            $table->index(['order_id', 'packaging_type_id'], 'order_packagings_order_packaging_index');
        });

        // Add indexes for activities - audit trail queries
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id', 'created_at'], 'activity_log_subject_created_index');
            $table->index(['causer_type', 'causer_id'], 'activity_log_causer_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropIndex('crops_planted_at_index');
            $table->dropIndex('crops_stage_planted_index');
            $table->dropIndex('crops_germination_at_index');
            $table->dropIndex('crops_harvested_at_index');
            $table->dropIndex('crops_batch_grouping_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_harvest_date_index');
            $table->dropIndex('orders_delivery_date_index');
            $table->dropIndex('orders_status_harvest_index');
            $table->dropIndex('orders_user_created_index');
        });

        Schema::table('seed_price_history', function (Blueprint $table) {
            $table->dropIndex('seed_price_variation_scraped_index');
            $table->dropIndex('seed_price_scraped_at_index');
        });

        Schema::table('seed_variations', function (Blueprint $table) {
            $table->dropIndex('seed_variations_last_checked_index');
            $table->dropIndex('seed_variations_stock_status_index');
            $table->dropIndex('seed_variations_entry_size_index');
        });

        Schema::table('seed_entries', function (Blueprint $table) {
            $table->dropIndex('seed_entries_cultivar_supplier_index');
            $table->dropIndex('seed_entries_supplier_index');
        });

        Schema::table('consumables', function (Blueprint $table) {
            $table->dropIndex('consumables_type_active_index');
            $table->dropIndex('consumables_supplier_type_index');
            $table->dropIndex('consumables_packaging_type_index');
        });

        Schema::table('order_packagings', function (Blueprint $table) {
            $table->dropIndex('order_packagings_order_packaging_index');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_subject_created_index');
            $table->dropIndex('activity_log_causer_index');
        });
    }
};
