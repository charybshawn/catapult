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
        Schema::create('seed_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_entry_id')->constrained()->onDelete('cascade');
            $table->string('size_description');
            $table->string('sku')->nullable();
            $table->decimal('weight_kg', 10, 4)->nullable();
            $table->string('original_weight_value')->nullable();
            $table->string('original_weight_unit')->nullable();
            $table->decimal('current_price', 10, 2);
            $table->string('currency')->default('USD');
            $table->boolean('is_in_stock')->default(true);
            $table->timestamp('last_checked_at');
            $table->timestamps();
            $table->unique(['seed_entry_id', 'size_description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_variations');
    }
};
