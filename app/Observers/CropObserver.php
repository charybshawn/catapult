<?php

namespace App\Observers;

use App\Models\Crop;
use App\Services\CropStageCalculator;
use Illuminate\Support\Facades\Log;

/**
 * Observer for Crop model to handle automatic stage calculation.
 */
class CropObserver
{
    protected CropStageCalculator $stageCalculator;

    public function __construct(CropStageCalculator $stageCalculator)
    {
        $this->stageCalculator = $stageCalculator;
    }

    /**
     * Handle the Crop "creating" event.
     * 
     * @param Crop $crop
     * @return void
     */
    public function creating(Crop $crop): void
    {
        // Set the initial stage based on any timestamps that might be set
        $this->updateStageFromTimestamps($crop);
    }

    /**
     * Handle the Crop "updating" event.
     * 
     * @param Crop $crop
     * @return void
     */
    public function updating(Crop $crop): void
    {
        // Check if any stage timestamps have changed
        $timestampFields = ['soaking_at', 'germination_at', 'blackout_at', 'light_at', 'harvested_at'];
        
        if ($crop->isDirty($timestampFields)) {
            $this->updateStageFromTimestamps($crop);
        }
    }

    /**
     * Update the crop's current_stage_id based on timestamps.
     * 
     * @param Crop $crop
     * @return void
     */
    protected function updateStageFromTimestamps(Crop $crop): void
    {
        // Skip if we're in bulk operation mode to avoid recursive calls
        if (Crop::isInBulkOperation()) {
            return;
        }

        $oldStageId = $crop->current_stage_id;
        $stageUpdated = $this->stageCalculator->updateCropStage($crop);
        
        if ($stageUpdated) {
            Log::info('Crop stage automatically updated', [
                'crop_id' => $crop->id,
                'old_stage_id' => $oldStageId,
                'new_stage_id' => $crop->current_stage_id,
                'soaking_at' => $crop->soaking_at?->toDateTimeString(),
                'germination_at' => $crop->germination_at?->toDateTimeString(),
                'blackout_at' => $crop->blackout_at?->toDateTimeString(),
                'light_at' => $crop->light_at?->toDateTimeString(),
                'harvested_at' => $crop->harvested_at?->toDateTimeString(),
            ]);
            
            // Invalidate crop batch cache if this crop belongs to a batch
            if ($crop->crop_batch_id && $crop->relationLoaded('cropBatch')) {
                $crop->cropBatch->invalidateFirstCropCache();
            }
        }
    }

    /**
     * Handle the Crop "saved" event.
     * 
     * @param Crop $crop
     * @return void
     */
    public function saved(Crop $crop): void
    {
        // Validate timestamp sequence after save
        $validationErrors = $this->stageCalculator->validateTimestampSequence($crop);
        
        if (!empty($validationErrors)) {
            Log::warning('Crop timestamp sequence validation failed', [
                'crop_id' => $crop->id,
                'errors' => $validationErrors,
            ]);
        }
    }
}