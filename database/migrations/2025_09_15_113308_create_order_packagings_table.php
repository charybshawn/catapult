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
        Schema::create('order_packagings', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('order_id');
                    $table->bigInteger('packaging_type_id');
                    $table->integer('quantity');
                    $table->decimal('price_per_unit', 10, 2);
                    $table->decimal('total_price', 10, 2);
                    $table->text('notes')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_packagings');
    }
};
