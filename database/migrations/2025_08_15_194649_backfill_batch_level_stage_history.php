<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, delete all existing individual crop stage history records
        \App\Models\CropStageHistory::truncate();
        echo "Cleared all existing stage history records\n";
        
        // Backfill batch-level stage history for existing crop batches
        $cropBatches = \App\Models\CropBatch::with(['crops', 'crops.currentStage'])->get();
        
        foreach ($cropBatches as $batch) {
            // Get the first crop to determine the batch's stage progression
            $firstCrop = $batch->crops->first();
            
            if (!$firstCrop) {
                continue;
            }
            
            // Collect all stage timestamps from the first crop (representative of the batch)
            $stages = [
                'soaking' => ['timestamp' => $firstCrop->soaking_at, 'stage_code' => 'soaking'],
                'germination' => ['timestamp' => $firstCrop->germination_at, 'stage_code' => 'germination'], 
                'blackout' => ['timestamp' => $firstCrop->blackout_at, 'stage_code' => 'blackout'],
                'light' => ['timestamp' => $firstCrop->light_at, 'stage_code' => 'light'],
                'harvested' => ['timestamp' => $firstCrop->harvested_at, 'stage_code' => 'harvested'],
            ];
            
            // Sort stages by timestamp
            $chronologicalStages = collect($stages)
                ->filter(fn($stage) => !is_null($stage['timestamp']))
                ->sortBy('timestamp');
            
            $previousStageHistory = null;
            
            foreach ($chronologicalStages as $stageName => $stageData) {
                // Find the stage ID by code
                $stage = \App\Models\CropStage::where('code', $stageData['stage_code'])->first();
                
                if (!$stage) {
                    continue;
                }
                
                $enteredAt = \Carbon\Carbon::parse($stageData['timestamp']);
                
                // Close the previous stage
                if ($previousStageHistory) {
                    \App\Models\CropStageHistory::where('id', $previousStageHistory->id)
                        ->update(['exited_at' => $enteredAt]);
                }
                
                // Create the batch-level stage history entry using first crop as representative
                $stageHistory = \App\Models\CropStageHistory::create([
                    'crop_id' => $firstCrop->id, // Use first crop as batch representative
                    'crop_batch_id' => $batch->id,
                    'stage_id' => $stage->id,
                    'entered_at' => $enteredAt,
                    'exited_at' => null, // Will be set when next stage starts or manually if current
                    'notes' => 'Batch-level stage history (representative crop: ' . $firstCrop->id . ')',
                    'created_by' => 1, // Assume system user ID 1
                ]);
                
                $previousStageHistory = $stageHistory;
            }
            
            // If the batch is not harvested, keep the current stage open
            if ($firstCrop->harvested_at === null && $previousStageHistory) {
                // This stage should remain active (no exit time) - already set to null above
            }
        }
        
        echo "Created batch-level stage history for " . $cropBatches->count() . " crop batches\n";
    }

    public function down(): void
    {
        // Remove batch-level stage history entries
        \App\Models\CropStageHistory::where('notes', 'like', 'Batch-level stage history (representative crop:%')->delete();
    }
};
