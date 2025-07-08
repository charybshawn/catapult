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
        Schema::create('activity_log_background_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_log_id')->nullable();
            $table->string('job_id', 100)->nullable();
            $table->string('job_class', 500);
            $table->string('queue_name', 100)->nullable();
            $table->enum('status', ['pending','processing','completed','failed','retrying','cancelled'])->default('pending');
            $table->json('payload')->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->decimal('execution_time_seconds', 10, 2)->nullable();
            $table->decimal('memory_peak_mb', 10, 2)->nullable();
            $table->text('exception_message')->nullable();
            $table->text('exception_trace')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('tags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log_background_jobs');
    }
};