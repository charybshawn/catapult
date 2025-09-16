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
                    $table->id('id');
                    $table->bigInteger('product_id');
                    $table->bigInteger('price_variation_id');
                    $table->string('batch_number', 255)->nullable();
                    $table->string('lot_number', 255)->nullable();
                    $table->decimal('quantity', 10, 2);
                    $table->decimal('reserved_quantity', 10, 2);
                    $table->decimal('available_quantity', 10, 2)->nullable();
                    $table->decimal('cost_per_unit', 10, 2)->nullable();
                    $table->string('expiration_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('production_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('location', 255)->nullable();
                    $table->text('notes')->nullable();
                    $table->bigInteger('product_inventory_status_id');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventories');
    }
};
