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
        Schema::create('activity_log_bulk_operations', function (Blueprint $table) {
            $table->id();
            $table->string('batch_uuid');
            $table->string('operation_type', 100);
            $table->string('model_type', 255);
            $table->integer('total_records');
            $table->integer('processed_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->enum('status', ['pending','processing','completed','failed','cancelled'])->default('pending');
            $table->json('parameters')->nullable();
            $table->json('results')->nullable();
            $table->text('error_message')->nullable();
            $table->bigInteger('initiated_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('execution_time_seconds', 10, 2)->nullable();
            $table->decimal('memory_peak_mb', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log_bulk_operations');
    }
};