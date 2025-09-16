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
            $table->string('name', 255);
            $table->bigInteger('master_seed_catalog_id')->nullable();
            $table->bigInteger('master_cultivar_id')->nullable();
            $table->bigInteger('soil_consumable_id')->nullable();
            $table->bigInteger('seed_consumable_id')->nullable();
            $table->string('lot_number', 255)->nullable();
            $table->timestamp('lot_depleted_at')->nullable();
            $table->decimal('seed_soak_hours', 5, 2)->default(0.00);
            $table->integer('germination_days');
            $table->integer('blackout_days');
            $table->integer('light_days');
            $table->integer('days_to_maturity')->nullable();
            $table->decimal('expected_yield_grams', 8, 2)->nullable();
            $table->decimal('seed_density_grams_per_tray', 8, 2)->nullable();
            $table->decimal('buffer_percentage', 5, 2)->default(10.00);
            $table->integer('suspend_watering_hours')->default(0);
            $table->integer('is_active')->default(1);
            $table->text('notes')->nullable();
            $table->integer('suspend_water_hours')->default(24);
            $table->string('common_name', 255)->nullable();
            $table->string('cultivar_name', 255)->nullable();
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