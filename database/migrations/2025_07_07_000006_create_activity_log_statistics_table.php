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
            $table->string('period_type', 20); // daily, weekly, monthly
            $table->string('log_name', 100)->nullable();
            $table->string('event_type', 100)->nullable();
            $table->string('model_type', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('total_activities')->default(0);
            $table->bigInteger('unique_users')->default(0);
            $table->bigInteger('unique_ips')->default(0);
            $table->json('activity_breakdown')->nullable(); // By event type
            $table->json('hourly_distribution')->nullable(); // 24-hour breakdown
            $table->json('top_users')->nullable(); // Top 10 most active users
            $table->json('top_actions')->nullable(); // Top 10 most common actions
            $table->json('top_models')->nullable(); // Top 10 most affected models
            $table->decimal('avg_execution_time_ms', 10, 2)->nullable();
            $table->decimal('max_execution_time_ms', 10, 2)->nullable();
            $table->decimal('total_execution_time_ms', 15, 2)->nullable();
            $table->decimal('avg_memory_usage_mb', 10, 2)->nullable();
            $table->decimal('max_memory_usage_mb', 10, 2)->nullable();
            $table->bigInteger('total_queries')->default(0);
            $table->bigInteger('error_count')->default(0);
            $table->bigInteger('warning_count')->default(0);
            $table->json('severity_breakdown')->nullable();
            $table->json('response_status_breakdown')->nullable();
            $table->json('browser_breakdown')->nullable();
            $table->json('os_breakdown')->nullable();
            $table->json('device_breakdown')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['date', 'period_type', 'log_name', 'event_type', 'model_type', 'user_id'], 'unique_stats');
            $table->index('date');
            $table->index('period_type');
            $table->index('log_name');
            $table->index('event_type');
            $table->index('model_type');
            $table->index('user_id');
            $table->index(['date', 'period_type']);
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