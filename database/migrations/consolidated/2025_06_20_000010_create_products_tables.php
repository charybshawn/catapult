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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_mixes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            $table->foreignId('seed_entry_id')->nullable()->constrained('seed_entries')->onDelete('set null');
            $table->foreignId('master_seed_catalog_id')->nullable()->constrained('master_seed_catalog')->onDelete('set null');
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->decimal('bulk_price', 10, 2)->nullable();
            $table->decimal('special_price', 10, 2)->nullable();
            $table->decimal('wholesale_discount_percentage', 5, 2)->default(15.00);
            $table->boolean('is_visible_in_store')->default(true);
            $table->boolean('active')->default(true);
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('product_mix_id')->nullable()->constrained('product_mixes')->onDelete('set null');
            $table->decimal('total_stock', 10, 2)->default(0);
            $table->decimal('reserved_stock', 10, 2)->default(0);
            $table->decimal('reorder_threshold', 10, 2)->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->enum('stock_status', ['in_stock', 'low_stock', 'out_of_stock', 'discontinued'])->default('in_stock');
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('active');
            $table->index('category_id');
            $table->index('product_mix_id');
        });

        Schema::create('product_mix_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_mix_id')->constrained('product_mixes')->onDelete('cascade');
            $table->foreignId('seed_entry_id')->constrained('seed_entries')->onDelete('cascade');
            $table->decimal('percentage', 8, 5);
            $table->timestamps();
            
            $table->unique(['product_mix_id', 'seed_entry_id']);
            $table->index('product_mix_id');
        });

        Schema::create('price_variations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit');
            $table->string('pricing_unit')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('fill_weight', 10, 2)->nullable();
            $table->foreignId('packaging_type_id')->nullable()->constrained('packaging_types')->onDelete('set null');
            $table->foreignId('template_id')->nullable()->constrained('price_variations')->onDelete('set null');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('is_active');
        });

        Schema::create('product_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index('product_id');
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('price_variation_id')->nullable()->constrained('price_variations')->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('product_photos');
        Schema::dropIfExists('price_variations');
        Schema::dropIfExists('product_mix_components');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_mixes');
        Schema::dropIfExists('categories');
    }
};