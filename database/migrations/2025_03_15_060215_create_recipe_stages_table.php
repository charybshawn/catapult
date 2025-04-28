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
        Schema::create('recipe_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->integer('day')->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('stage', ['germination', 'blackout', 'light']);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->float('temperature_min_celsius')->nullable();
            $table->float('temperature_max_celsius')->nullable();
            $table->integer('humidity_min_percent')->nullable();
            $table->integer('humidity_max_percent')->nullable();
            $table->timestamps();
            
            // Ensure each recipe has only one entry per stage
            $table->unique(['recipe_id', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_stages');
    }
};
