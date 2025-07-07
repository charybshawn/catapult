<?php

namespace App\Services;

use App\Models\Crop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service class for Crop validation and business rules
 */
class CropValidationService
{
    /**
     * @var CropTaskManagementService
     */
    protected CropTaskManagementService $cropTaskService;
    
    /**
     * @var InventoryManagementService
     */
    protected InventoryManagementService $inventoryService;
    
    /**
     * Create a new service instance.
     */
    public function __construct(CropTaskManagementService $cropTaskService, InventoryManagementService $inventoryService)
    {
        $this->cropTaskService = $cropTaskService;
        $this->inventoryService = $inventoryService;
    }
    /**
     * Validate that timestamps are in chronological order
     * 
     * @param Crop $crop
     * @throws \Exception
     */
    public function validateTimestampSequence(Crop $crop): void
    {
        $timestamps = [];
        
        // Build array of non-null timestamps with their labels
        if ($crop->planting_at) {
            $timestamps['planting_at'] = $crop->planting_at;
        }
        if ($crop->germination_at) {
            $timestamps['germination_at'] = $crop->germination_at;
        }
        if ($crop->blackout_at) {
            $timestamps['blackout_at'] = $crop->blackout_at;
        }
        if ($crop->light_at) {
            $timestamps['light_at'] = $crop->light_at;
        }
        if ($crop->harvested_at) {
            $timestamps['harvested_at'] = $crop->harvested_at;
        }
        
        // Skip validation if we have fewer than 2 timestamps
        if (count($timestamps) < 2) {
            return;
        }
        
        // Convert to Carbon instances for comparison
        $carbonTimestamps = array_map(function($timestamp) {
            return $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);
        }, $timestamps);
        
        // Check if timestamps are in order (allow same timestamp for flexibility)
        $previousTimestamp = null;
        $previousLabel = null;
        
        foreach ($carbonTimestamps as $label => $timestamp) {
            if ($previousTimestamp && $timestamp->lt($previousTimestamp)) {
                $readableLabel = str_replace('_at', '', str_replace('_', ' ', $label));
                $readablePrevious = str_replace('_at', '', str_replace('_', ' ', $previousLabel));
                throw new \Exception("Growth stage timestamps must be in chronological order. {$readableLabel} cannot be before {$readablePrevious}.");
            }
            $previousTimestamp = $timestamp;
            $previousLabel = $label;
        }
    }

    /**
     * Initialize default values for a new crop
     * 
     * @param Crop $crop
     * @return void
     */
    public function initializeNewCrop(Crop $crop): void
    {
        // Set planting_at if not provided
        if (!$crop->planting_at) {
            $crop->planting_at = now();
        }
        
        // Set germination_at and current_stage to germination automatically
        if ($crop->planting_at && !$crop->germination_at) {
            $crop->germination_at = $crop->planting_at;
        }
        
        // Always start at germination stage if not set
        if (!$crop->current_stage_id) {
            $germinationStage = \App\Models\CropStage::findByCode('germination');
            if ($germinationStage) {
                $crop->current_stage_id = $germinationStage->id;
            }
        }
        
        // Initialize computed time fields with safe values
        if (!isset($crop->time_to_next_stage_minutes)) {
            $crop->time_to_next_stage_minutes = 0;
        }
        if (!isset($crop->time_to_next_stage_display)) {
            $crop->time_to_next_stage_display = 'Unknown';
        }
        if (!isset($crop->stage_age_minutes)) {
            $crop->stage_age_minutes = 0;
        }
        if (!isset($crop->stage_age_display)) {
            $crop->stage_age_display = '0m';
        }
        if (!isset($crop->total_age_minutes)) {
            $crop->total_age_minutes = 0;
        }
        if (!isset($crop->total_age_display)) {
            $crop->total_age_display = '0m';
        }
    }

    /**
     * Adjust stage timestamps when planting date changes
     * 
     * @param Crop $crop
     * @return void
     */
    public function adjustStageTimestamps(Crop $crop): void
    {
        if (!$crop->isDirty('planting_at') || !$crop->recipe) {
            return;
        }

        try {
            // Get original planting_at
            $originalPlantingAt = $crop->getOriginal('planting_at');
            
            // Get the new planting_at
            $newPlantingAt = $crop->planting_at;
            
            // Calculate time difference in minutes
            $originalDateTime = new Carbon($originalPlantingAt);
            $timeDiff = $originalDateTime->diffInMinutes($newPlantingAt, false);
            
            // Adjust all stage timestamps by the same amount
            foreach (['germination_at', 'blackout_at', 'light_at'] as $stageField) {
                if ($crop->$stageField) {
                    $stageTime = new Carbon($crop->$stageField);
                    $crop->$stageField = $stageTime->addMinutes($timeDiff);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error updating crop stage dates', [
                'crop_id' => $crop->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate crop data before saving
     * 
     * @param Crop $crop
     * @return array Array of validation errors, empty if valid
     */
    public function validateCrop(Crop $crop): array
    {
        $errors = [];

        // Validate tray count
        if ($crop->tray_count !== null && $crop->tray_count <= 0) {
            $errors[] = 'Tray count must be greater than zero';
        }

        // Validate harvest weight
        if ($crop->harvest_weight_grams !== null && $crop->harvest_weight_grams < 0) {
            $errors[] = 'Harvest weight cannot be negative';
        }

        // Validate recipe exists if recipe_id is set
        if ($crop->recipe_id && !$crop->recipe) {
            $errors[] = 'Invalid recipe selected';
        }

        // Validate stage progression
        if ($crop->current_stage_id) {
            $validStageIds = \App\Models\CropStage::pluck('id')->toArray();
            if (!in_array($crop->current_stage_id, $validStageIds)) {
                $errors[] = 'Invalid growth stage';
            }
        }

        return $errors;
    }

    /**
     * Check if crop should have its watering suspended based on recipe settings
     * 
     * @param Crop $crop
     * @return bool
     */
    public function shouldAutoSuspendWatering(Crop $crop): bool
    {
        if (!$crop->recipe || !$crop->recipe->suspend_water_hours) {
            return false;
        }

        return $this->cropTaskService->shouldSuspendWatering($crop);
    }

    /**
     * Handle post-creation tasks for a crop
     * 
     * @param Crop $crop
     * @return void
     */
    public function handleCropCreated(Crop $crop): void
    {
        // Deduct seed from inventory if not in bulk operation mode
        if (!Crop::isInBulkOperation()) {
            $this->inventoryService->deductSeedForCrop($crop);
        }
        
        // Schedule stage transition tasks (skip during testing)
        if (config('app.env') !== 'testing' && !Crop::isInBulkOperation()) {
            try {
                $this->cropTaskService->scheduleAllStageTasks($crop);
            } catch (\Exception $e) {
                Log::warning('Error scheduling crop tasks', [
                    'crop_id' => $crop->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle post-update tasks for a crop
     * 
     * @param Crop $crop
     * @return void
     */
    public function handleCropUpdated(Crop $crop): void
    {
        // If the stage has changed or planting_at has changed, recalculate tasks
        if (($crop->isDirty('current_stage_id') || $crop->isDirty('planting_at')) && 
            config('app.env') !== 'testing') {
            try {
                $this->cropTaskService->scheduleAllStageTasks($crop);
            } catch (\Exception $e) {
                Log::warning('Error rescheduling crop tasks', [
                    'crop_id' => $crop->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}