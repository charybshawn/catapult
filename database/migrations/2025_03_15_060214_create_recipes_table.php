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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('supplier_soil_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('seed_variety_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('seed_density', 8, 2)->nullable();
            $table->integer('seed_soak_days')->default(0);
            $table->integer('germination_days')->default(3);
            $table->integer('blackout_days')->default(0);
            $table->integer('harvest_days')->default(7);
            $table->decimal('expected_yield_grams', 8, 2)->nullable();
            $table->decimal('seed_density_grams_per_tray', 8, 2)->nullable();
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
        Schema::dropIfExists('recipes');
    }
};
