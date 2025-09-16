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
                    $table->id('id');
                    $table->string('name', 255);
                    $table->integer('is_name_manual');
                    $table->string('unit', 255)->nullable();
                    $table->string('pricing_unit', 255)->nullable();
                    $table->string('sku', 255)->nullable();
                    $table->decimal('weight', 10, 2)->nullable();
                    $table->decimal('price', 10, 2);
                    $table->bigInteger('packaging_type_id')->nullable();
                    $table->string('pricing_type', 255);
                    $table->decimal('fill_weight_grams', 8, 2)->nullable();
                    $table->bigInteger('template_id')->nullable();
                    $table->integer('is_default');
                    $table->integer('is_global');
                    $table->integer('is_active');
                    $table->bigInteger('product_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_variations');
    }
};
