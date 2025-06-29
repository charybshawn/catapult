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
        Schema::table('seed_variations', function (Blueprint $table) {
            // Rename columns to match main branch
            if (Schema::hasColumn('seed_variations', 'size_description') && !Schema::hasColumn('seed_variations', 'size')) {
                $table->renameColumn('size_description', 'size');
            }
            if (Schema::hasColumn('seed_variations', 'is_in_stock') && !Schema::hasColumn('seed_variations', 'is_available')) {
                $table->renameColumn('is_in_stock', 'is_available');
            }
        });
        
        Schema::table('seed_price_history', function (Blueprint $table) {
            // Rename columns to match main branch  
            if (Schema::hasColumn('seed_price_history', 'scraped_at') && !Schema::hasColumn('seed_price_history', 'checked_at')) {
                $table->renameColumn('scraped_at', 'checked_at');
            }
        });
        
        Schema::table('seed_scrape_uploads', function (Blueprint $table) {
            // Rename columns to match main branch
            if (Schema::hasColumn('seed_scrape_uploads', 'original_filename') && !Schema::hasColumn('seed_scrape_uploads', 'filename')) {
                $table->renameColumn('original_filename', 'filename');
            }
            if (Schema::hasColumn('seed_scrape_uploads', 'successful_entries') && !Schema::hasColumn('seed_scrape_uploads', 'new_entries')) {
                $table->renameColumn('successful_entries', 'new_entries');
            }
        });
        
        Schema::table('seed_entries', function (Blueprint $table) {
            // Rename columns to match main branch
            if (Schema::hasColumn('seed_entries', 'supplier_product_title') && !Schema::hasColumn('seed_entries', 'supplier_sku')) {
                $table->renameColumn('supplier_product_title', 'supplier_sku');
            }
            if (Schema::hasColumn('seed_entries', 'supplier_product_url') && !Schema::hasColumn('seed_entries', 'url')) {
                $table->renameColumn('supplier_product_url', 'url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_variations', function (Blueprint $table) {
            if (Schema::hasColumn('seed_variations', 'size')) {
                $table->renameColumn('size', 'size_description');
            }
            if (Schema::hasColumn('seed_variations', 'is_available')) {
                $table->renameColumn('is_available', 'is_in_stock');
            }
        });
        
        Schema::table('seed_price_history', function (Blueprint $table) {
            if (Schema::hasColumn('seed_price_history', 'checked_at')) {
                $table->renameColumn('checked_at', 'scraped_at');
            }
        });
        
        Schema::table('seed_scrape_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('seed_scrape_uploads', 'filename')) {
                $table->renameColumn('filename', 'original_filename');
            }
            if (Schema::hasColumn('seed_scrape_uploads', 'new_entries')) {
                $table->renameColumn('new_entries', 'successful_entries');
            }
        });
        
        Schema::table('seed_entries', function (Blueprint $table) {
            if (Schema::hasColumn('seed_entries', 'supplier_sku')) {
                $table->renameColumn('supplier_sku', 'supplier_product_title');
            }
            if (Schema::hasColumn('seed_entries', 'url')) {
                $table->renameColumn('url', 'supplier_product_url');
            }
        });
    }
};