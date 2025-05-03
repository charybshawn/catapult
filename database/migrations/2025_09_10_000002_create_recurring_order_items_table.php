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
        Schema::create('recurring_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2)->nullable()->comment('Override price (null = use current item price)');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_order_items');
    }
}; 