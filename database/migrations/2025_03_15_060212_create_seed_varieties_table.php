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
        Schema::create('seed_varieties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('crop_type');
            $table->string('brand')->nullable();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->decimal('cost_per_unit', 8, 2)->nullable();
            $table->string('unit_type')->nullable();
            $table->decimal('germination_rate', 5, 2)->nullable(); // Percentage
            $table->decimal('average_yield_grams', 8, 2)->nullable();
            $table->integer('days_to_maturity')->nullable();
            $table->decimal('price_per_kg', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_varieties');
    }
};
