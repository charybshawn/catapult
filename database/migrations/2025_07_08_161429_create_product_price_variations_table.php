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
        Schema::create('product_price_variations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->integer('is_name_manual')->default(0);
            $table->string('unit', 255)->default('units');
            $table->string('pricing_unit', 255)->nullable();
            $table->string('sku', 255)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('fill_weight', 10, 2)->nullable();
            $table->unsignedBigInteger('packaging_type_id')->nullable();
            $table->string('pricing_type', 255)->default('retail');
            $table->decimal('fill_weight_grams', 8, 2)->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->integer('is_default')->default(0);
            $table->integer('is_global')->default(0);
            $table->integer('is_active')->default(1);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_variations');
    }
};