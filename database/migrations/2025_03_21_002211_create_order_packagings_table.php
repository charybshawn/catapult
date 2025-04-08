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
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('packaging_type_id')->constrained()->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Ensure each packaging type can only be added once per order
            $table->unique(['order_id', 'packaging_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_packagings');
    }
};
