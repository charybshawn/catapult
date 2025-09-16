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
        Schema::create('inventory_reservations', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('product_inventory_id');
                    $table->bigInteger('product_id');
                    $table->bigInteger('order_id');
                    $table->bigInteger('order_item_id');
                    $table->decimal('quantity', 10, 2);
                    $table->bigInteger('status_id');
                    $table->timestamp('expires_at')->nullable();
                    $table->timestamp('fulfilled_at')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
