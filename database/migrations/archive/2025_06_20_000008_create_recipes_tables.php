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
            $table->foreignId('supplier_soil_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->unsignedBigInteger('soil_consumable_id')->nullable()->index();
            $table->decimal('seed_soak_hours', 5, 2)->default(0);
            $table->integer('germination_days');
            $table->integer('blackout_days');
            $table->integer('light_days');
            $table->integer('harvest_days');
            $table->integer('days_to_maturity')->nullable();
            $table->decimal('expected_yield_grams', 8, 2)->nullable();
            $table->decimal('seed_density_grams_per_tray', 8, 2)->nullable();
            $table->decimal('buffer_percentage', 5, 2)->default(10.00);
            $table->integer('suspend_watering_hours')->default(0);
            $table->text('germination_notes')->nullable();
            $table->text('blackout_notes')->nullable();
            $table->text('light_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });

        Schema::create('recipe_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('day');
            $table->integer('duration_days');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('stage', ['germination', 'blackout', 'light']);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('temperature_min_celsius', 5, 2)->nullable();
            $table->decimal('temperature_max_celsius', 5, 2)->nullable();
            $table->decimal('humidity_min_percent', 5, 2)->nullable();
            $table->decimal('humidity_max_percent', 5, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['recipe_id', 'stage']);
            $table->index('recipe_id');
        });

        Schema::create('recipe_watering_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->integer('day_number');
            $table->decimal('water_amount_ml', 8, 2);
            $table->string('watering_method')->nullable();
            $table->boolean('needs_liquid_fertilizer')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['recipe_id', 'day_number']);
            $table->index('recipe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_watering_schedule');
        Schema::dropIfExists('recipe_stages');
        Schema::dropIfExists('recipes');
    }
};