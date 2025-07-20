<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Change crop_batch_id from VARCHAR to BIGINT UNSIGNED and add foreign key.
     */
    public function up(): void
    {
        // First, create crop_batch records for any existing crop_batch_ids
        $existingBatches = DB::table('crops')
            ->whereNotNull('crop_batch_id')
            ->select('crop_batch_id', 'recipe_id', DB::raw('MIN(created_at) as created_at'))
            ->groupBy('crop_batch_id', 'recipe_id')
            ->get();

        // Store mapping of old crop_batch_id to new numeric id
        $batchMapping = [];
        
        foreach ($existingBatches as $batch) {
            $newBatchId = DB::table('crop_batches')->insertGetId([
                'recipe_id' => $batch->recipe_id,
                'created_at' => $batch->created_at,
                'updated_at' => $batch->created_at,
            ]);
            
            $batchMapping[$batch->crop_batch_id] = $newBatchId;
        }

        // Create a temporary column for the new crop_batch_id
        Schema::table('crops', function (Blueprint $table) {
            $table->unsignedBigInteger('crop_batch_id_new')->nullable()->after('crop_batch_id');
        });

        // Update the temporary column with new numeric crop_batch_ids
        foreach ($batchMapping as $oldBatchId => $newBatchId) {
            DB::table('crops')
                ->where('crop_batch_id', $oldBatchId)
                ->update(['crop_batch_id_new' => $newBatchId]);
        }

        // Drop the old column and rename the new one
        Schema::table('crops', function (Blueprint $table) {
            // Only drop index if it exists
            $indexExists = DB::select("SHOW INDEX FROM crops WHERE Key_name = 'idx_crops_crop_batch_id'");
            if (!empty($indexExists)) {
                $table->dropIndex('idx_crops_crop_batch_id');
            }
            $table->dropColumn('crop_batch_id');
        });

        Schema::table('crops', function (Blueprint $table) {
            $table->renameColumn('crop_batch_id_new', 'crop_batch_id');
        });

        // Add the foreign key constraint and index
        Schema::table('crops', function (Blueprint $table) {
            $table->foreign('crop_batch_id')->references('id')->on('crop_batches')->onDelete('set null');
            $table->index('crop_batch_id', 'idx_crops_crop_batch_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Remove foreign key and index
        Schema::table('crops', function (Blueprint $table) {
            $table->dropForeign(['crop_batch_id']);
            $table->dropIndex('idx_crops_crop_batch_id');
        });

        // Change column back to VARCHAR
        Schema::table('crops', function (Blueprint $table) {
            $table->string('crop_batch_id', 36)->nullable()->change();
        });

        // Re-add the index
        Schema::table('crops', function (Blueprint $table) {
            $table->index('crop_batch_id', 'idx_crops_crop_batch_id');
        });

        // Note: We don't restore the old string batch_ids as that would be data loss
    }
};