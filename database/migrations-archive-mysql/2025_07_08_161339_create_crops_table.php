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
            $table->bigInteger('recipe_id');
            $table->bigInteger('order_id')->nullable();
            $table->bigInteger('crop_plan_id')->nullable();
            $table->string('tray_number', 255);
            $table->bigInteger('current_stage_id');
            $table->timestamp('stage_updated_at')->nullable();
            $table->timestamp('planting_at')->nullable();
            $table->timestamp('germination_at')->nullable();
            $table->timestamp('blackout_at')->nullable();
            $table->timestamp('light_at')->nullable();
            $table->timestamp('watering_suspended_at')->nullable();
            $table->text('notes')->nullable();
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
