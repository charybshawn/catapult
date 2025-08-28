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
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 255)->nullable();
            $table->text('description');
            $table->string('subject_type', 255)->nullable();
            $table->string('subject_id', 255)->nullable();
            $table->string('event', 255)->nullable();
            $table->string('causer_type', 255)->nullable();
            $table->string('causer_id', 255)->nullable();
            $table->json('properties')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->text('request_url')->nullable();
            $table->integer('response_status')->nullable();
            $table->decimal('execution_time_ms', 10, 2)->nullable();
            $table->decimal('memory_usage_mb', 10, 2)->nullable();
            $table->integer('query_count')->nullable();
            $table->json('context')->nullable();
            $table->json('tags')->nullable();
            $table->enum('severity_level', ['debug','info','notice','warning','error','critical','alert','emergency'])->default('info');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};