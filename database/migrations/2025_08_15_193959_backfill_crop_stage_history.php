<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill stage history for existing crops based on their stage timestamps
        $crops = \App\Models\Crop::with(['cropBatch', 'currentStage'])->get();
        
        foreach ($crops as $crop) {
            $stageEntries = [];
            
            // Collect all stage timestamps and create history entries
            $stages = [
                'soaking' => ['timestamp' => $crop->soaking_at, 'stage_code' => 'soaking'],
                'germination' => ['timestamp' => $crop->germination_at, 'stage_code' => 'germination'], 
                'blackout' => ['timestamp' => $crop->blackout_at, 'stage_code' => 'blackout'],
                'light' => ['timestamp' => $crop->light_at, 'stage_code' => 'light'],
                'harvested' => ['timestamp' => $crop->harvested_at, 'stage_code' => 'harvested'],
            ];
            
            // Sort stages by timestamp
            $chronologicalStages = collect($stages)
                ->filter(fn($stage) => !is_null($stage['timestamp']))
                ->sortBy('timestamp');
            
            $previousStage = null;
            
            foreach ($chronologicalStages as $stageName => $stageData) {
                // Find the stage ID by code
                $stage = \App\Models\CropStage::where('code', $stageData['stage_code'])->first();
                
                if (!$stage) {
                    continue;
                }
                
                $enteredAt = \Carbon\Carbon::parse($stageData['timestamp']);
                
                // Close the previous stage
                if ($previousStage) {
                    \App\Models\CropStageHistory::where('id', $previousStage['id'])
                        ->update(['exited_at' => $enteredAt]);
                }
                
                // Create the stage history entry
                $stageHistory = \App\Models\CropStageHistory::create([
                    'crop_id' => $crop->id,
                    'crop_batch_id' => $crop->crop_batch_id,
                    'stage_id' => $stage->id,
                    'entered_at' => $enteredAt,
                    'exited_at' => null, // Will be set when next stage starts or manually if current
                    'notes' => 'Backfilled from existing stage timestamps',
                    'created_by' => 1, // Assume system user ID 1
                ]);
                
                $previousStage = $stageHistory;
            }
            
            // If the crop is not harvested, keep the current stage open
            if ($crop->harvested_at === null && $previousStage) {
                // This stage should remain active (no exit time)
                // Already set to null above
            }
        }
        
        echo "Backfilled stage history for " . $crops->count() . " crops\n";
    }

    public function down(): void
    {
        // Remove backfilled stage history entries
        \App\Models\CropStageHistory::where('notes', 'Backfilled from existing stage timestamps')->delete();
    }
};
