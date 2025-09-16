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
            $table->bigInteger('seed_entry_id');
            $table->string('sku', 255)->nullable();
            $table->bigInteger('consumable_id')->nullable();
            $table->string('size', 255);
            $table->decimal('weight_kg', 8, 4)->nullable();
            $table->decimal('original_weight_value', 8, 4)->nullable();
            $table->string('original_weight_unit', 255)->nullable();
            $table->string('unit', 255);
            $table->decimal('current_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('is_available')->default(1);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('size_description', 255)->nullable();
            $table->integer('is_in_stock')->nullable();
            $table->timestamps();
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