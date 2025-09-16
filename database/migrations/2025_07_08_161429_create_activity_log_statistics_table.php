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
        Schema::create('activity_log_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('period_type', 20);
            $table->string('log_name', 100)->nullable();
            $table->string('event_type', 100)->nullable();
            $table->string('model_type', 255)->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->integer('total_activities')->default(0);
            $table->integer('unique_users')->default(0);
            $table->integer('unique_ips')->default(0);
            $table->json('activity_breakdown')->nullable();
            $table->json('hourly_distribution')->nullable();
            $table->json('top_users')->nullable();
            $table->json('top_actions')->nullable();
            $table->json('top_models')->nullable();
            $table->decimal('avg_execution_time_ms', 10, 2)->nullable();
            $table->decimal('max_execution_time_ms', 10, 2)->nullable();
            $table->decimal('total_execution_time_ms', 15, 2)->nullable();
            $table->decimal('avg_memory_usage_mb', 10, 2)->nullable();
            $table->decimal('max_memory_usage_mb', 10, 2)->nullable();
            $table->integer('total_queries')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->json('severity_breakdown')->nullable();
            $table->json('response_status_breakdown')->nullable();
            $table->json('browser_breakdown')->nullable();
            $table->json('os_breakdown')->nullable();
            $table->json('device_breakdown')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log_statistics');
    }
};