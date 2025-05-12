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
        Schema::create('crop_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained()->cascadeOnDelete(); // Link to the representative crop of the batch
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete(); // Link to the recipe for context
            $table->string('task_type'); // e.g., end_germination, end_blackout, suspend_watering, expected_harvest
            $table->json('details')->nullable(); // Extra info, e.g., target stage
            $table->timestamp('scheduled_at'); // When the task should trigger
            $table->timestamp('triggered_at')->nullable(); // When the task was processed
            $table->string('status')->default('pending'); // pending, triggered, error, dismissed
            $table->timestamps(); // created_at, updated_at
            
            $table->index('scheduled_at');
            $table->index('status');
            $table->index(['crop_id', 'task_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_tasks');
    }
};
