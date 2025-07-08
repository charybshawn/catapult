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
        Schema::create('aggregated_crop_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variety_id')->constrained('master_seed_catalog')->onDelete('restrict');
            $table->date('harvest_date');
            $table->decimal('total_grams_needed', 10, 2);
            $table->integer('total_trays_needed');
            $table->decimal('grams_per_tray', 8, 2);
            $table->date('plant_date');
            $table->date('seed_soak_date')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'in_progress', 'completed'])->default('draft');
            $table->json('calculation_details')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('variety_id');
            $table->index('harvest_date');
            $table->index('plant_date');
            $table->index('status');
            $table->index(['variety_id', 'harvest_date']);
            $table->index(['status', 'harvest_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aggregated_crop_plans');
    }
};