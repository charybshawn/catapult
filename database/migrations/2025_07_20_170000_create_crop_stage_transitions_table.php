<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create table to track all crop stage transitions for audit trail
     */
    public function up(): void
    {
        Schema::create('crop_stage_transitions', function (Blueprint $table) {
            $table->id();
            
            // Transition type
            $table->enum('type', ['advance', 'revert', 'bulk_advance', 'bulk_revert']);
            
            // Batch information
            $table->unsignedBigInteger('crop_batch_id')->nullable();
            $table->integer('crop_count')->default(1);
            
            // Stage information
            $table->unsignedBigInteger('from_stage_id');
            $table->unsignedBigInteger('to_stage_id');
            
            // Timing
            $table->timestamp('transition_at')->comment('When the actual transition occurred');
            $table->timestamp('recorded_at')->comment('When this record was created');
            
            // User tracking
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            
            // Additional context
            $table->string('reason')->nullable()->comment('Reason for reversion if applicable');
            $table->json('metadata')->nullable()->comment('Additional data like tray number assignments');
            $table->json('validation_warnings')->nullable();
            
            // Results
            $table->integer('succeeded_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('failed_crops')->nullable()->comment('IDs and reasons for failed crops');
            
            $table->timestamps();
            
            // Indexes
            $table->index('crop_batch_id');
            $table->index('from_stage_id');
            $table->index('to_stage_id');
            $table->index('user_id');
            $table->index('transition_at');
            $table->index('type');
            
            // Foreign keys
            $table->foreign('crop_batch_id')->references('id')->on('crop_batches')->onDelete('cascade');
            $table->foreign('from_stage_id')->references('id')->on('crop_stages');
            $table->foreign('to_stage_id')->references('id')->on('crop_stages');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_stage_transitions');
    }
};