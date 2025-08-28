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
        Schema::create('crop_harvest', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crop_id');
            $table->unsignedBigInteger('harvest_id');
            $table->decimal('harvested_weight_grams', 8, 2);
            $table->decimal('percentage_harvested', 5, 2)->default(100.00);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('crop_id')->references('id')->on('crops')->onDelete('cascade');
            $table->foreign('harvest_id')->references('id')->on('harvests')->onDelete('cascade');
            
            // Prevent duplicate entries
            $table->unique(['crop_id', 'harvest_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_harvest');
    }
};