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
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('price_variation_id');
            $table->string('batch_number', 255)->nullable();
            $table->string('lot_number', 255)->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('reserved_quantity', 10, 2)->default(0.00);
            $table->decimal('available_quantity', 10, 2)->nullable();
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('production_date')->nullable();
            $table->string('location', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('product_inventory_status_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventories');
    }
};