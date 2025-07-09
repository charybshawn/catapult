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
            $table->string('resource_type', 255)->nullable();
            $table->string('task_name', 255)->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('frequency', ['once','daily','weekly','monthly']);
            $table->json('schedule_config');
            $table->string('time_of_day', 255)->nullable();
            $table->integer('day_of_week')->nullable();
            $table->integer('day_of_month')->nullable();
            $table->json('conditions')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
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