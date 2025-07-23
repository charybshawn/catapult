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
        // Only create if it doesn't exist (for cases where table was manually created)
        if (!Schema::hasTable('crop_batches')) {
            Schema::create('crop_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('recipe_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('crop_plan_id')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('recipe_id')->references('id')->on('recipes');
                $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
                $table->foreign('crop_plan_id')->references('id')->on('crop_plans')->nullOnDelete();
                
                // Index for performance
                $table->index(['recipe_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_batches');
    }
};