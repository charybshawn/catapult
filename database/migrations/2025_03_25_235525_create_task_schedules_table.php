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
        Schema::create('task_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type'); // The type of resource (inventory, crop, order, etc.)
            $table->string('task_name'); // Name of the task
            $table->string('frequency'); // daily, weekly, monthly, hourly, etc.
            $table->time('time_of_day')->nullable(); // Time to run (for daily, weekly, monthly)
            $table->integer('day_of_week')->nullable(); // Day to run (for weekly)
            $table->integer('day_of_month')->nullable(); // Day to run (for monthly)
            $table->json('conditions'); // JSON containing threshold conditions
            $table->boolean('is_active')->default(true); // Whether this task is active
            $table->timestamp('last_run_at')->nullable(); // When the task was last executed
            $table->timestamp('next_run_at')->nullable(); // When the task is scheduled to run next
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_schedules');
    }
};
