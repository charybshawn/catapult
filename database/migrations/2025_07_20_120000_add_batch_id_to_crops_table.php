<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add batch_id column to crops table for grouping soaking batches.
     * 
     * This migration adds support for batch processing of crops during the soaking stage.
     * Multiple crops can share the same batch_id when they are soaked together,
     * allowing for coordinated stage transitions and better resource management.
     */
    public function up(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // Add batch_id column after the id column
            // Using VARCHAR(36) to support UUIDs for batch identification
            // Nullable to maintain backward compatibility with existing crops
            $table->string('batch_id', 36)
                ->nullable()
                ->after('id')
                ->comment('UUID for grouping crops that are soaked together in the same batch');
            
            // Add index for performance when querying crops by batch
            $table->index('batch_id', 'idx_crops_batch_id');
        });
    }

    /**
     * Reverse the migration by removing the batch_id column and its index.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex('idx_crops_batch_id');
            
            // Then drop the column
            $table->dropColumn('batch_id');
        });
    }
};