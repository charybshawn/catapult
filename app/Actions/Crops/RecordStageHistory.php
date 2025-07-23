<?php

namespace App\Actions\Crops;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\CropStageHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecordStageHistory
{
    /**
     * Record a stage transition in the history table
     * 
     * @param Crop $crop The crop transitioning stages
     * @param CropStage $newStage The stage being entered
     * @param Carbon $timestamp When the transition occurred
     * @param string|null $notes Optional notes about the transition
     * @return CropStageHistory The created history record
     */
    public function execute(Crop $crop, CropStage $newStage, Carbon $timestamp, ?string $notes = null): CropStageHistory
    {
        return DB::transaction(function () use ($crop, $newStage, $timestamp, $notes) {
            // Close any existing active stage for this crop
            CropStageHistory::where('crop_id', $crop->id)
                ->whereNull('exited_at')
                ->update(['exited_at' => $timestamp]);
            
            // Create new stage history entry
            return CropStageHistory::create([
                'crop_id' => $crop->id,
                'crop_batch_id' => $crop->crop_batch_id,
                'stage_id' => $newStage->id,
                'entered_at' => $timestamp,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Close the current stage without entering a new one
     * Used when harvesting or removing a crop
     * 
     * @param Crop $crop The crop to close history for
     * @param Carbon $timestamp When to close the stage
     * @return int Number of records updated
     */
    public function closeCurrentStage(Crop $crop, Carbon $timestamp): int
    {
        return CropStageHistory::where('crop_id', $crop->id)
            ->whereNull('exited_at')
            ->update(['exited_at' => $timestamp]);
    }

    /**
     * Record history for multiple crops in a batch
     * 
     * @param array $cropIds Array of crop IDs
     * @param int $batchId The batch ID
     * @param CropStage $newStage The stage being entered
     * @param Carbon $timestamp When the transition occurred
     * @param string|null $notes Optional notes about the transition
     * @return int Number of history records created
     */
    public function executeBatch(array $cropIds, int $batchId, CropStage $newStage, Carbon $timestamp, ?string $notes = null): int
    {
        return DB::transaction(function () use ($cropIds, $batchId, $newStage, $timestamp, $notes) {
            // Close existing active stages for all crops
            CropStageHistory::whereIn('crop_id', $cropIds)
                ->whereNull('exited_at')
                ->update(['exited_at' => $timestamp]);
            
            // Prepare data for bulk insert
            $historyData = array_map(function ($cropId) use ($batchId, $newStage, $timestamp, $notes) {
                return [
                    'crop_id' => $cropId,
                    'crop_batch_id' => $batchId,
                    'stage_id' => $newStage->id,
                    'entered_at' => $timestamp,
                    'notes' => $notes,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $cropIds);
            
            // Bulk insert new history records
            CropStageHistory::insert($historyData);
            
            return count($historyData);
        });
    }

    /**
     * Remove stage history when reverting a stage
     * 
     * @param Crop $crop The crop reverting stages
     * @param CropStage $currentStage The stage being reverted from
     * @return bool Whether the history was successfully removed
     */
    public function removeStageEntry(Crop $crop, CropStage $currentStage): bool
    {
        // Find and delete the current stage entry
        $deleted = CropStageHistory::where('crop_id', $crop->id)
            ->where('stage_id', $currentStage->id)
            ->whereNull('exited_at')
            ->delete();
        
        // Reopen the previous stage (remove its exit timestamp)
        if ($deleted > 0) {
            CropStageHistory::where('crop_id', $crop->id)
                ->where('exited_at', '!=', null)
                ->orderBy('entered_at', 'desc')
                ->first()
                ?->update(['exited_at' => null]);
        }
        
        return $deleted > 0;
    }
}