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
        Schema::create('order_products', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('order_id');
                    $table->bigInteger('product_id');
                    $table->bigInteger('price_variation_id')->nullable();
                    $table->decimal('quantity', 10, 3);
                    $table->string('quantity_unit', 20);
                    $table->decimal('quantity_in_grams', 10, 3)->nullable();
                    $table->decimal('price', 10, 2);
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_products');
    }
};
