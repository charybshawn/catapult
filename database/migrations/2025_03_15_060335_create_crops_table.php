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
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            // order_id will be added in a later migration
            $table->string('tray_number');
            $table->timestamp('planted_at');
            $table->enum('current_stage', ['planting', 'germination', 'blackout', 'light', 'harvested'])->default('planting');
            $table->timestamp('stage_updated_at')->nullable();
            $table->decimal('harvest_weight_grams', 8, 2)->nullable();
            $table->timestamp('watering_suspended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Ensure tray numbers are unique for active crops (not harvested)
            $table->index(['tray_number', 'current_stage']);
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
