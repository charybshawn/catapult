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
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('sku', 255)->nullable();
            $table->unsignedBigInteger('master_seed_catalog_id')->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->decimal('bulk_price', 10, 2)->nullable();
            $table->decimal('special_price', 10, 2)->nullable();
            $table->decimal('wholesale_discount_percentage', 5, 2)->default(15.00);
            $table->integer('is_visible_in_store')->default(1);
            $table->integer('active')->default(1);
            $table->string('image', 255)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_mix_id')->nullable();
            $table->decimal('total_stock', 10, 2)->default(0.00);
            $table->decimal('reserved_stock', 10, 2)->default(0.00);
            $table->decimal('reorder_threshold', 10, 2)->default(0.00);
            $table->integer('track_inventory')->default(1);
            $table->unsignedBigInteger('stock_status_id')->default(1);
            $table->decimal('available_stock', 10, 2)->default(0.00);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};