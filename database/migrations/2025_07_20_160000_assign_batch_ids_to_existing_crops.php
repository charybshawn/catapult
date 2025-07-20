<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Assign crop_batch_ids to existing crops based on their implicit batching
     * (recipe_id + planting_at + current_stage_id).
     */
    public function up(): void
    {
        // Find all unique batches based on implicit grouping
        $implicitBatches = DB::table('crops')
            ->whereNull('crop_batch_id')
            ->select('recipe_id', 'planting_at', 'current_stage_id', DB::raw('COUNT(*) as crop_count'))
            ->groupBy('recipe_id', 'planting_at', 'current_stage_id')
            ->orderBy('planting_at')
            ->get();

        Log::info('Found ' . $implicitBatches->count() . ' implicit batches to assign IDs to');

        $assignedCount = 0;
        $totalCrops = 0;

        foreach ($implicitBatches as $implicitBatch) {
            // Create a new batch record
            $batchId = DB::table('crop_batches')->insertGetId([
                'recipe_id' => $implicitBatch->recipe_id,
                'created_at' => $implicitBatch->planting_at,
                'updated_at' => $implicitBatch->planting_at,
            ]);

            // Update all crops in this implicit batch with the new batch ID
            $updated = DB::table('crops')
                ->where('recipe_id', $implicitBatch->recipe_id)
                ->where('planting_at', $implicitBatch->planting_at)
                ->where('current_stage_id', $implicitBatch->current_stage_id)
                ->whereNull('crop_batch_id')
                ->update(['crop_batch_id' => $batchId]);

            if ($updated > 0) {
                $assignedCount++;
                $totalCrops += $updated;
                
                Log::info("Assigned batch ID {$batchId} to {$updated} crops", [
                    'recipe_id' => $implicitBatch->recipe_id,
                    'planting_at' => $implicitBatch->planting_at,
                    'current_stage_id' => $implicitBatch->current_stage_id,
                ]);
            }
        }

        Log::info("Batch ID assignment complete", [
            'batches_created' => $assignedCount,
            'crops_updated' => $totalCrops,
        ]);
    }

    /**
     * Remove the assigned batch IDs (but keep the batch records).
     */
    public function down(): void
    {
        // Only clear the crop_batch_id from crops, don't delete the batch records
        // This allows for easier re-running if needed
        DB::table('crops')
            ->whereNotNull('crop_batch_id')
            ->update(['crop_batch_id' => null]);
            
        Log::info('Cleared crop_batch_id from all crops');
    }
};