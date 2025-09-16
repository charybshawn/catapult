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
        Schema::create('crop_plans_aggregate', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('variety_id');
            $table->date('harvest_date');
            $table->decimal('total_grams_needed', 10, 2);
            $table->integer('total_trays_needed');
            $table->decimal('grams_per_tray', 8, 2);
            $table->date('plant_date');
            $table->date('seed_soak_date')->nullable();
            $table->enum('status', ['draft','confirmed','in_progress','completed'])->default('draft');
            $table->json('calculation_details')->nullable();
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans_aggregate');
    }
};