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
        Schema::create('products', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('name', 255);
                    $table->text('description')->nullable();
                    $table->string('sku', 255)->nullable();
                    $table->bigInteger('master_seed_catalog_id')->nullable();
                    $table->decimal('base_price', 10, 2)->nullable();
                    $table->decimal('wholesale_price', 10, 2)->nullable();
                    $table->decimal('bulk_price', 10, 2)->nullable();
                    $table->decimal('special_price', 10, 2)->nullable();
                    $table->decimal('wholesale_discount_percentage', 5, 2);
                    $table->integer('is_visible_in_store');
                    $table->integer('active');
                    $table->string('image', 255)->nullable();
                    $table->bigInteger('category_id')->nullable();
                    $table->bigInteger('product_mix_id')->nullable();
                    $table->bigInteger('recipe_id')->nullable();
                    $table->decimal('total_stock', 10, 2);
                    $table->decimal('reserved_stock', 10, 2);
                    $table->decimal('reorder_threshold', 10, 2);
                    $table->integer('track_inventory');
                    $table->bigInteger('stock_status_id');
                    $table->timestamp('deleted_at')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
