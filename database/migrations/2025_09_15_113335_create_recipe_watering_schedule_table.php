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
        Schema::create('recipe_watering_schedule', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('recipe_id');
                    $table->integer('day_number');
                    $table->decimal('water_amount_ml', 8, 2);
                    $table->string('watering_method', 255)->nullable();
                    $table->integer('needs_liquid_fertilizer');
                    $table->text('notes')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_watering_schedule');
    }
};
