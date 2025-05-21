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
        // Create categories table first since products references it
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create product_mixes table first since products references it
        Schema::create('product_mixes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create products table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->decimal('bulk_price', 10, 2)->nullable();
            $table->decimal('special_price', 10, 2)->nullable();
            $table->boolean('is_visible_in_store')->default(true);
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_mix_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create order_products table
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // Create price_variations table
        Schema::create('price_variations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->default('item')->comment('per item, lbs, gram, etc.');
            $table->string('sku')->nullable();
            $table->decimal('weight', 10, 3)->default(0)->comment('Weight in the specified unit');
            $table->decimal('price', 8, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_variations');
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_mixes');
        Schema::dropIfExists('categories');
    }
}; 