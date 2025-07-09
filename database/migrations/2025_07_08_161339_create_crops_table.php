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
        Schema::create('crops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('crop_plan_id')->nullable();
            $table->string('tray_number', 255);
            $table->unsignedBigInteger('current_stage_id');
            $table->timestamp('stage_updated_at')->nullable();
            $table->timestamp('planting_at')->nullable();
            $table->timestamp('germination_at')->nullable();
            $table->timestamp('blackout_at')->nullable();
            $table->timestamp('light_at')->nullable();
            $table->timestamp('harvested_at')->nullable();
            $table->decimal('harvest_weight_grams', 8, 2)->nullable();
            $table->timestamp('watering_suspended_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('time_to_next_stage_minutes')->nullable();
            $table->string('time_to_next_stage_display', 20)->nullable();
            $table->integer('stage_age_minutes')->nullable();
            $table->string('stage_age_display', 20)->nullable();
            $table->integer('total_age_minutes')->nullable();
            $table->string('total_age_display', 20)->nullable();
            $table->timestamp('expected_harvest_at')->nullable();
            $table->integer('tray_count')->default(1);
            $table->string('tray_numbers', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};