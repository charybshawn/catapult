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
            $table->uuid('batch_uuid')->unique();
            $table->string('operation_type', 100);
            $table->string('model_type', 255);
            $table->integer('total_records');
            $table->integer('processed_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                ->default('pending');
            $table->json('parameters')->nullable();
            $table->json('results')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('execution_time_seconds', 10, 2)->nullable();
            $table->decimal('memory_peak_mb', 10, 2)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('batch_uuid');
            $table->index('operation_type');
            $table->index('model_type');
            $table->index('status');
            $table->index('initiated_by');
            $table->index('created_at');
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