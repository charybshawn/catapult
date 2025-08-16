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
        // Auto-set requires_soaking based on recipe
        if ($crop->recipe_id && !isset($crop->requires_soaking)) {
            $recipe = \App\Models\Recipe::find($crop->recipe_id);
            if ($recipe) {
                $crop->requires_soaking = $recipe->requiresSoaking();
            }
        }
        
        
        // Set initial timestamps based on recipe requirements
        // Note: The CropStageCalculator will automatically set the current_stage_id
        // based on which timestamps are present
        if ($crop->requires_soaking && $crop->recipe_id) {
            // Set soaking_at if not provided (stage will be calculated automatically)
            if (!$crop->soaking_at) {
                $crop->soaking_at = now();
            }
        } else {
            // Set germination_at automatically to now for non-soaking crops
            if (!$crop->germination_at) {
                $crop->germination_at = now();
            }
        }
        
        // Note: current_stage_id will be set automatically by the CropStageCalculator
        // based on which timestamps are present
        
        // Note: Computed time fields have been moved to crop_batches_list_view
        // They are no longer stored on individual crop records
    }

    /**
     * Adjust stage timestamps when planting date changes
     * 
     * @param Crop $crop
     * @return void
     */
    public function adjustStageTimestamps(Crop $crop): void
    {
        // Method no longer needed since planting_at was removed
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
        // If the stage has changed, recalculate tasks
        if ($crop->isDirty('current_stage_id') && config('app.env') !== 'testing') {
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