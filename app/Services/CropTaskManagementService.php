<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\NotificationSetting;
use App\Models\TaskSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ResourceActionRequired;

/**
 * Unified service for managing crop tasks and lifecycle operations
 * Consolidates functionality from CropTaskService, CropLifecycleService, and TaskFactoryService
 */
class CropTaskManagementService
{
    /**
     * The valid crop stages in order
     */
    private const STAGES = [
        'soaking',
        'germination',
        'blackout',
        'light',
        'harvested'
    ];

    /**
     * Schedule all stage transition tasks for a crop
     */
    public function scheduleAllStageTasks(Crop $crop): void
    {
        Log::info('Starting task scheduling for crop', [
            'crop_id' => $crop->id,
            'tray_number' => $crop->tray_number,
            'has_recipe' => !!$crop->recipe,
            'recipe_id' => $crop->recipe_id,
            'current_stage_id' => $crop->current_stage_id,
            'requires_soaking' => $crop->requires_soaking
        ]);
        
        // Prevent memory issues during bulk operations
        $memoryLimitMb = config('tasks.memory_limit_mb', 100);
        if (memory_get_usage(true) > $memoryLimitMb * 1024 * 1024) {
            Log::warning('Memory limit approaching, skipping task scheduling', [
                'crop_id' => $crop->id,
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => $memoryLimitMb * 1024 * 1024
            ]);
            return;
        }
        
        $this->deleteTasksForCrop($crop);
        
        // Only schedule tasks if the crop has a recipe
        if (!$crop->recipe) {
            Log::warning('Crop has no recipe, skipping task scheduling', [
                'crop_id' => $crop->id
            ]);
            return;
        }
        
        $recipe = $crop->recipe;
        $plantedAt = $crop->planting_at;
        
        // Debug current stage loading and fallback to direct lookup if relationship fails
        $currentStageObject = $crop->currentStage;
        $currentStage = $currentStageObject?->code ?? null;
        
        if (!$currentStage && $crop->current_stage_id) {
            Log::warning('Stage relationship failed to load, attempting direct lookup', [
                'crop_id' => $crop->id,
                'current_stage_id' => $crop->current_stage_id,
                'currentStage_relationship_loaded' => $crop->relationLoaded('currentStage'),
                'currentStage_object' => $currentStageObject ? 'object found' : 'null'
            ]);
            
            // Fallback: Direct lookup of the stage
            $stageFromDirect = CropStage::find($crop->current_stage_id);
            if ($stageFromDirect) {
                $currentStage = $stageFromDirect->code;
                Log::info('Successfully found stage via direct lookup', [
                    'crop_id' => $crop->id,
                    'stage_code' => $currentStage,
                    'stage_name' => $stageFromDirect->name
                ]);
            } else {
                Log::error('Stage ID not found in database', [
                    'crop_id' => $crop->id,
                    'current_stage_id' => $crop->current_stage_id
                ]);
            }
        }
        
        Log::info('Current stage and recipe details', [
            'crop_id' => $crop->id,
            'current_stage' => $currentStage,
            'current_stage_id' => $crop->current_stage_id,
            'recipe_name' => $recipe->name ?? 'unknown',
            'seed_soak_hours' => $recipe->seed_soak_hours ?? 0,
            'planting_at' => $plantedAt ? $plantedAt->format('Y-m-d H:i:s') : 'null',
            'soaking_at' => $crop->soaking_at ? $crop->soaking_at->format('Y-m-d H:i:s') : 'null'
        ]);
        
        // Skip if no current stage is set
        if (!$currentStage) {
            Log::warning('No current stage set, skipping task scheduling', [
                'crop_id' => $crop->id
            ]);
            return;
        }
        
        // Get durations from recipe
        $soakHours = $recipe->seed_soak_hours ?? 0;
        $germDays = $recipe->germination_days;
        $blackoutDays = $recipe->blackout_days;
        $lightDays = $recipe->light_days;
        
        // Calculate stage transition times based on whether crop is soaking
        if ($crop->requires_soaking && $crop->soaking_at) {
            // For soaking crops, base calculations on soaking_at
            $soakingStart = $crop->soaking_at;
            $germinationTime = $soakingStart->copy()->addHours($soakHours);
            $blackoutTime = $germinationTime->copy()->addDays($germDays);
            $lightTime = $blackoutTime->copy()->addDays($blackoutDays);
            $harvestTime = $lightTime->copy()->addDays($lightDays);
        } else {
            // For non-soaking crops, use planting_at as base
            $germinationTime = $plantedAt->copy()->addHours($soakHours);
            $blackoutTime = $germinationTime->copy()->addDays($germDays);
            $lightTime = $blackoutTime->copy()->addDays($blackoutDays);
            $harvestTime = $lightTime->copy()->addDays($lightDays);
        }
        
        $now = Carbon::now();
        
        // Schedule soaking → germination transition if crop requires soaking
        if ($currentStage === 'soaking' && $crop->requires_soaking && $soakHours > 0 && $germinationTime->gt($now)) {
            Log::info('Creating soaking tasks', [
                'crop_id' => $crop->id,
                'germination_time' => $germinationTime->format('Y-m-d H:i:s'),
                'is_today' => $germinationTime->isToday()
            ]);
            
            $this->createBatchStageTransitionTask($crop, 'germination', $germinationTime);
            
            // Schedule soaking completion notice for the day it completes
            if ($germinationTime->isToday()) {
                // Create alert for early morning of completion day
                $warningTime = $germinationTime->copy()->startOfDay()->addHours(6); // 6 AM on completion day
                if ($warningTime->isPast()) {
                    $warningTime = $now; // If it's already past 6 AM, schedule immediately
                }
                $this->createSoakingWarningTask($crop, $warningTime);
            }
        } else {
            Log::info('Skipping soaking task creation', [
                'crop_id' => $crop->id,
                'current_stage' => $currentStage,
                'requires_soaking' => $crop->requires_soaking,
                'soak_hours' => $soakHours,
                'germination_time' => $germinationTime ? $germinationTime->format('Y-m-d H:i:s') : 'null',
                'is_future' => $germinationTime ? $germinationTime->gt($now) : false,
                'condition_results' => [
                    'is_soaking_stage' => $currentStage === 'soaking',
                    'requires_soaking' => $crop->requires_soaking,
                    'has_soak_hours' => $soakHours > 0,
                    'germination_in_future' => $germinationTime ? $germinationTime->gt($now) : false
                ]
            ]);
        }
        
        // Only schedule tasks for future stages
        if ($currentStage === 'germination' && $blackoutTime->gt($now)) {
            // Skip blackout stage if blackoutDays is 0
            if ($blackoutDays > 0) {
                $this->createBatchStageTransitionTask($crop, 'blackout', $blackoutTime);
            } else {
                // If skipping blackout, go straight to light
                $this->createBatchStageTransitionTask($crop, 'light', $blackoutTime);
            }
        }
        
        // Only create light transition if we're going through blackout stage
        if ($currentStage === 'blackout' && $lightTime->gt($now)) {
            $this->createBatchStageTransitionTask($crop, 'light', $lightTime);
        }
        
        if (in_array($currentStage, ['soaking', 'germination', 'blackout', 'light']) && $harvestTime->gt($now)) {
            $this->createBatchStageTransitionTask($crop, 'harvested', $harvestTime);
        }

        // Schedule Suspend Watering Task (if applicable)
        if ($recipe->suspend_water_hours > 0) {
            $suspendTime = $harvestTime->copy()->subHours($recipe->suspend_water_hours);
            if ($suspendTime->isAfter($plantedAt) && $suspendTime->gt($now)) {
                $this->createWateringSuspensionTask($crop, $suspendTime);
            } else {
                Log::warning("Suspend watering time ({$suspendTime->toDateTimeString()}) is before planting time ({$plantedAt->toDateTimeString()}) or in the past for Crop ID {$crop->id}. Task not generated.");
            }
        }
        
        Log::info('Completed task scheduling for crop', [
            'crop_id' => $crop->id
        ]);
    }

    /**
     * Advance a crop to the next stage in its lifecycle
     * IMPORTANT: This advances ALL crops in the batch to maintain batch integrity
     */
    public function advanceStage(Crop $crop, ?Carbon $timestamp = null): void
    {
        // Ensure currentStage and recipe are loaded
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        // Get the next viable stage based on recipe (skipping stages with 0 days)
        $currentStageObject = $crop->getRelationValue('currentStage');
        $nextStage = $currentStageObject?->getNextViableStage($crop->recipe);
        
        if (!$nextStage) {
            Log::warning('Cannot advance crop beyond final stage', [
                'crop_id' => $crop->id,
                'current_stage_id' => $crop->current_stage_id,
                'current_stage_code' => $currentStageObject?->code
            ]);
            return;
        }

        // Find ALL crops in this batch to maintain batch integrity
        $batchCrops = $this->findBatchCrops($crop);

        $advancementTime = $timestamp ?? Carbon::now();
        $count = 0;
        
        // Advance all crops in the batch together
        foreach ($batchCrops as $batchCrop) {
            $batchCrop->current_stage_id = $nextStage->id;
            
            // Set stage-specific timestamps
            match ($nextStage->code) {
                'soaking' => $batchCrop->soaking_at = $advancementTime,
                'germination' => $batchCrop->germination_at = $advancementTime,
                'blackout' => $batchCrop->blackout_at = $advancementTime,
                'light' => $batchCrop->light_at = $advancementTime,
                'harvested' => $batchCrop->harvested_at = $advancementTime,
                default => null
            };

            $batchCrop->save();
            $count++;
        }

        Log::info('Crop batch stage advanced', [
            'initiating_crop_id' => $crop->id,
            'batch_size' => $count,
            'recipe_id' => $crop->recipe_id,
            'planting_at' => $crop->planting_at,
            'from_stage' => $currentStageObject?->code,
            'to_stage' => $nextStage->code
        ]);
    }

    /**
     * Process a crop stage transition task
     */
    public function processCropStageTask(TaskSchedule $task): array
    {
        $conditions = $task->conditions;
        $cropId = $conditions['crop_id'] ?? null;
        $targetStage = $conditions['target_stage'] ?? null;
        $batchIdentifier = $conditions['batch_identifier'] ?? null;
        $trayNumbers = $conditions['tray_numbers'] ?? null;
        $warningType = $conditions['warning_type'] ?? null;
        
        // Handle soaking completion warning
        if ($task->task_name === 'soaking_completion_warning' && $warningType === 'soaking_completion') {
            return $this->processSoakingWarningTask($task, $batchIdentifier, $trayNumbers);
        }
        
        if (!$targetStage) {
            return [
                'success' => false,
                'message' => 'Invalid task conditions: missing target_stage',
            ];
        }
        
        // Process batch if we have batch information
        if ($batchIdentifier && is_array($trayNumbers) && count($trayNumbers) > 0) {
            return $this->processBatchStageTransition($task, $batchIdentifier, $targetStage, $trayNumbers);
        }
        
        // Fallback to single crop processing
        return $this->processSingleCropStageTransition($task, $cropId, $targetStage);
    }

    /**
     * Suspend watering for a crop
     * IMPORTANT: This suspends watering for ALL crops in the batch
     */
    public function suspendWatering(Crop $crop, ?Carbon $timestamp = null): void
    {
        $batchCrops = $this->findBatchCrops($crop);

        $suspensionTime = $timestamp ?? Carbon::now();
        $count = 0;
        $alreadySuspended = 0;
        
        foreach ($batchCrops as $batchCrop) {
            if ($batchCrop->watering_suspended_at) {
                $alreadySuspended++;
                continue;
            }
            
            $batchCrop->watering_suspended_at = $suspensionTime;
            $batchCrop->save();
            $count++;
        }

        Log::info('Watering suspended for crop batch', [
            'initiating_crop_id' => $crop->id,
            'batch_size' => $batchCrops->count(),
            'newly_suspended' => $count,
            'already_suspended' => $alreadySuspended,
            'recipe_id' => $crop->recipe_id,
            'planting_at' => $crop->planting_at
        ]);
    }

    /**
     * Resume watering for a crop
     * IMPORTANT: This resumes watering for ALL crops in the batch
     */
    public function resumeWatering(Crop $crop): void
    {
        $batchCrops = $this->findBatchCrops($crop);

        $count = 0;
        $alreadyActive = 0;
        
        foreach ($batchCrops as $batchCrop) {
            if (!$batchCrop->watering_suspended_at) {
                $alreadyActive++;
                continue;
            }
            
            $batchCrop->watering_suspended_at = null;
            $batchCrop->save();
            $count++;
        }

        Log::info('Watering resumed for crop batch', [
            'initiating_crop_id' => $crop->id,
            'batch_size' => $batchCrops->count(),
            'newly_resumed' => $count,
            'already_active' => $alreadyActive,
            'recipe_id' => $crop->recipe_id,
            'planting_at' => $crop->planting_at
        ]);
    }

    /**
     * Reset a crop to a specific stage
     */
    public function resetToStage(Crop $crop, string $targetStageCode): void
    {
        if (!in_array($targetStageCode, self::STAGES)) {
            throw new \InvalidArgumentException("Invalid stage: {$targetStageCode}");
        }

        $targetStage = CropStage::findByCode($targetStageCode);
        if (!$targetStage) {
            throw new \InvalidArgumentException("Stage not found: {$targetStageCode}");
        }

        $crop->current_stage_id = $targetStage->id;
        $now = Carbon::now();

        // Clear timestamps for stages that come after the target stage
        $stageIndex = array_search($targetStageCode, self::STAGES);
        
        foreach (self::STAGES as $index => $stage) {
            $timestampField = $this->getStageTimestampField($stage);
            
            if ($index > $stageIndex) {
                // Clear future stage timestamps
                $crop->{$timestampField} = null;
            } elseif ($index === $stageIndex && !$crop->{$timestampField}) {
                // Set current stage timestamp if not set
                $crop->{$timestampField} = $now;
            }
        }

        $crop->save();

        Log::info('Crop stage reset', [
            'crop_id' => $crop->id,
            'reset_to_stage' => $targetStageCode
        ]);
    }

    /**
     * Calculate the expected harvest date for a crop
     */
    public function calculateExpectedHarvestDate(Crop $crop): ?Carbon
    {
        if (!$crop->recipe || !$crop->planting_at) {
            return null;
        }

        $plantedAt = Carbon::parse($crop->planting_at);
        $daysToMaturity = $crop->recipe->totalDays();

        if ($daysToMaturity <= 0) {
            return null;
        }

        return $plantedAt->addDays($daysToMaturity);
    }

    /**
     * Calculate how many days the crop has been in its current stage
     */
    public function calculateDaysInCurrentStage(Crop $crop): int
    {
        $stageTimestamp = $this->getCurrentStageTimestamp($crop);
        
        if (!$stageTimestamp) {
            return 0;
        }

        return Carbon::now()->diffInDays(Carbon::parse($stageTimestamp));
    }

    /**
     * Check if watering should be suspended for this crop
     */
    public function shouldSuspendWatering(Crop $crop): bool
    {
        if (!$crop->recipe) {
            return false;
        }

        $suspendHours = $crop->recipe->suspend_watering_hours;
        
        if (!$suspendHours || $suspendHours <= 0) {
            return false;
        }

        $harvestDate = $this->calculateExpectedHarvestDate($crop);
        
        if (!$harvestDate) {
            return false;
        }

        $suspendAt = $harvestDate->subHours($suspendHours);
        
        return Carbon::now()->gte($suspendAt);
    }

    /**
     * Delete all tasks for a specific crop
     */
    public function deleteTasksForCrop(Crop $crop): int
    {
        // Create a batch identifier - handle case where currentStage might be null
        $stageCode = $crop->currentStage?->code ?? 'unknown';
        $batchIdentifier = "{$crop->recipe_id}_{$crop->planting_at->format('Y-m-d')}_{$stageCode}";
        
        // Find and delete all tasks related to this batch
        return TaskSchedule::where('resource_type', 'crops')
            ->where(function($query) use ($crop, $batchIdentifier) {
                $query->where('conditions->crop_id', $crop->id)
                      ->orWhere('conditions->batch_identifier', $batchIdentifier);
            })
            ->delete();
    }

    /**
     * Create a batch stage transition task
     */
    protected function createBatchStageTransitionTask(Crop $crop, string $targetStage, Carbon $transitionTime): TaskSchedule
    {
        // Get variety name with proper fallbacks
        $varietyName = $this->getVarietyName($crop);

        // Find other crops in the same batch
        $batchCrops = $this->findBatchCrops($crop);
        $batchTrays = $batchCrops->pluck('tray_number')->toArray();
        $batchTraysList = implode(', ', $batchTrays);
        $batchSize = count($batchTrays);
        
        // Get current stage for batch identifier
        $currentStageCode = $crop->currentStage?->code ?? ($crop->current_stage_id ? CropStage::find($crop->current_stage_id)?->code : 'unknown');
        
        // Create conditions for the task
        $conditions = [
            'crop_id' => (int) $crop->id,
            'batch_identifier' => "{$crop->recipe_id}_{$crop->planting_at->format('Y-m-d')}_{$currentStageCode}",
            'target_stage' => $targetStage,
            'tray_numbers' => $batchTrays,
            'tray_count' => $batchSize,
            'tray_list' => $batchTraysList,
            'variety' => $varietyName,
        ];
        
        // Create the task
        $taskName = "advance_to_{$targetStage}";
        
        $task = new TaskSchedule();
        $task->name = "Advance crop batch to {$targetStage} - {$varietyName}";
        $task->resource_type = 'crops';
        $task->task_name = $taskName;
        $task->frequency = 'once';
        $task->schedule_config = [];
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $transitionTime;
        $task->save();
        
        return $task;
    }

    /**
     * Create a soaking completion warning task
     */
    protected function createSoakingWarningTask(Crop $crop, Carbon $warningTime): TaskSchedule
    {
        $varietyName = $this->getVarietyName($crop);
        
        // Find other crops in the same batch
        $batchCrops = $this->findBatchCrops($crop);
        $batchTrays = $batchCrops->pluck('tray_number')->toArray();
        $batchTraysList = implode(', ', $batchTrays);
        $batchSize = count($batchTrays);
        
        // Get current stage for batch identifier
        $currentStageCode = $crop->currentStage?->code ?? ($crop->current_stage_id ? CropStage::find($crop->current_stage_id)?->code : 'unknown');
        
        $conditions = [
            'crop_id' => (int) $crop->id,
            'batch_identifier' => "{$crop->recipe_id}_{$crop->planting_at->format('Y-m-d')}_{$currentStageCode}",
            'target_stage' => 'germination', // Soaking leads to germination
            'tray_numbers' => $batchTrays,
            'tray_count' => $batchSize,
            'tray_list' => $batchTraysList,
            'variety' => $varietyName,
            'warning_type' => 'soaking_completion',
            'minutes_until_completion' => 30
        ];

        $task = new TaskSchedule();
        $task->name = "Soaking completes today - {$varietyName}";
        $task->resource_type = 'crops';
        $task->task_name = 'soaking_completion_warning';
        $task->frequency = 'once';
        $task->schedule_config = [];
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $warningTime;
        $task->save();
        
        return $task;
    }

    /**
     * Create a watering suspension task
     */
    protected function createWateringSuspensionTask(Crop $crop, Carbon $suspendTime): TaskSchedule
    {
        $varietyName = $this->getVarietyName($crop);
        
        $conditions = [
            'crop_id' => (int) $crop->id,
            'tray_number' => $crop->tray_number,
            'variety' => $varietyName,
        ];

        $task = new TaskSchedule();
        $task->name = "Suspend watering - {$varietyName} (Tray #{$crop->tray_number})";
        $task->resource_type = 'crops';
        $task->task_name = 'suspend_watering';
        $task->frequency = 'once';
        $task->schedule_config = [];
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $suspendTime;
        $task->save();
        
        return $task;
    }

    /**
     * Process batch stage transition
     */
    protected function processBatchStageTransition(TaskSchedule $task, string $batchIdentifier, string $targetStage, array $trayNumbers): array
    {
        // Find all crops in this batch
        $batchParts = explode('_', $batchIdentifier);
        if (count($batchParts) !== 3) {
            return [
                'success' => false,
                'message' => "Invalid batch identifier format: {$batchIdentifier}",
            ];
        }
        
        list($recipeId, $plantedAtDate, $currentStage) = $batchParts;
        
        $crops = Crop::where('recipe_id', $recipeId)
            ->where('planting_at', $plantedAtDate)
            ->whereHas('currentStage', function($query) use ($currentStage) {
                $query->where('code', $currentStage);
            })
            ->whereIn('tray_number', $trayNumbers)
            ->get();
        
        if ($crops->isEmpty()) {
            return [
                'success' => false,
                'message' => "No crops found in batch with identifier {$batchIdentifier}",
            ];
        }
        
        // Check stage order
        $firstCrop = $crops->first();
        $stageOrder = ['soaking', 'germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($firstCrop->currentStage->code, $stageOrder);
        $targetStageIndex = array_search($targetStage, $stageOrder);
        
        if ($currentStageIndex === false || $targetStageIndex === false) {
            return [
                'success' => false,
                'message' => "Invalid stage definition for batch {$batchIdentifier} or task target stage {$targetStage}",
            ];
        }
        
        // Process stage advancement
        if ($currentStageIndex < $targetStageIndex) {
            // Send notification
            $this->sendStageTransitionNotification($firstCrop, $targetStage, count($crops));
            
            // Mark the task as processed
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            // Advance all crops through required stages
            $stagesNeeded = [];
            for ($i = $currentStageIndex + 1; $i <= $targetStageIndex; $i++) {
                $stagesNeeded[] = $stageOrder[$i];
            }
            
            foreach ($crops as $crop) {
                foreach ($stagesNeeded as $index => $nextStage) {
                    $isFinalStage = ($index === count($stagesNeeded) - 1);
                    
                    if ($isFinalStage) {
                        // Use the unified advanceStage method
                        $this->advanceStage($crop);
                        break; // advanceStage handles the batch, so we only need to call it once
                    } else {
                        // Manually update intermediate stages
                        $stageField = "{$nextStage}_at";
                        $crop->$stageField = now();
                        $nextStageObject = CropStage::findByCode($nextStage);
                        if ($nextStageObject) {
                            $crop->current_stage_id = $nextStageObject->id;
                        }
                        $crop->saveQuietly();
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => "Batch {$batchIdentifier} ({$crops->count()} crops) advanced to {$targetStage} stage" . 
                             (count($stagesNeeded) > 1 ? " (skipped " . (count($stagesNeeded) - 1) . " intermediate stages)" : "") . ".",
            ];
        } elseif ($currentStageIndex >= $targetStageIndex) {
            // Deactivate task if already at or past target stage
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            return [
                'success' => true,
                'message' => "Batch {$batchIdentifier} is already at or past {$targetStage} stage. Task deactivated.",
            ];
        }
        
        return [
            'success' => true,
            'message' => "Batch {$batchIdentifier} not yet ready for {$targetStage}. Notification not sent."
        ];
    }

    /**
     * Process soaking completion warning task
     */
    protected function processSoakingWarningTask(TaskSchedule $task, string $batchIdentifier, array $trayNumbers): array
    {
        // Find the crop
        $conditions = $task->conditions;
        $cropId = $conditions['crop_id'] ?? null;
        $crop = Crop::find($cropId);
        
        if (!$crop) {
            return [
                'success' => false,
                'message' => "Crop with ID {$cropId} not found",
            ];
        }
        
        // Check if crop is still in soaking stage
        if ($crop->currentStage->code !== 'soaking') {
            // Deactivate task if no longer soaking
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            return [
                'success' => true,
                'message' => "Crop batch {$batchIdentifier} is no longer in soaking stage. Warning task deactivated.",
            ];
        }
        
        // Send soaking completion warning notification
        $this->sendSoakingWarningNotification($crop, $conditions);
        
        // Mark the task as processed
        $task->is_active = false;
        $task->last_run_at = now();
        $task->save();
        
        return [
            'success' => true,
            'message' => "Soaking completion warning sent for batch {$batchIdentifier} ({$conditions['tray_count']} trays).",
        ];
    }

    /**
     * Process single crop stage transition
     */
    protected function processSingleCropStageTransition(TaskSchedule $task, int $cropId, string $targetStage): array
    {
        $crop = Crop::find($cropId);
        if (!$crop) {
            return [
                'success' => false,
                'message' => "Crop with ID {$cropId} not found",
            ];
        }
        
        // Check stage order
        $stageOrder = ['soaking', 'germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($crop->currentStage->code, $stageOrder);
        $targetStageIndex = array_search($targetStage, $stageOrder);

        if ($currentStageIndex === false || $targetStageIndex === false) {
            return [
                'success' => false,
                'message' => "Invalid stage definition for crop ID {$cropId} or task target stage {$targetStage}",
            ];
        }

        if ($currentStageIndex < $targetStageIndex) {
            // Send notification
            $this->sendStageTransitionNotification($crop, $targetStage);
            
            // Mark the task as processed
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            // Advance through required stages
            $stagesNeeded = [];
            for ($i = $currentStageIndex + 1; $i <= $targetStageIndex; $i++) {
                $stagesNeeded[] = $stageOrder[$i];
            }
            
            foreach ($stagesNeeded as $index => $nextStage) {
                $isFinalStage = ($index === count($stagesNeeded) - 1);
                
                if ($isFinalStage) {
                    $this->advanceStage($crop);
                } else {
                    $stageField = "{$nextStage}_at";
                    $crop->$stageField = now();
                    $nextStageObject = CropStage::findByCode($nextStage);
                    if ($nextStageObject) {
                        $crop->current_stage_id = $nextStageObject->id;
                    }
                    $crop->saveQuietly();
                }
            }
            
            return [
                'success' => true,
                'message' => "Crop ID {$cropId} advanced to {$targetStage} stage" . 
                             (count($stagesNeeded) > 1 ? " (skipped " . (count($stagesNeeded) - 1) . " intermediate stages)" : "") . ".",
            ];
        } elseif ($currentStageIndex >= $targetStageIndex) {
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            return [
                'success' => true,
                'message' => "Crop ID {$cropId} is already at or past {$targetStage} stage. Task deactivated.",
            ];
        }
        
        return [
            'success' => true,
            'message' => "Crop ID {$cropId} not yet ready for {$targetStage}. Notification not sent."
        ];
    }

    /**
     * Send a notification for soaking completion warning
     */
    protected function sendSoakingWarningNotification(Crop $crop, array $conditions): void
    {
        $setting = NotificationSetting::findByTypeAndEvent('crops', 'stage_transition');
        
        if (!$setting || !$setting->shouldSendEmail()) {
            return;
        }
        
        $recipients = collect($setting->recipients);
        
        if ($recipients->isEmpty()) {
            return;
        }
        
        $minutesUntil = $conditions['minutes_until_completion'] ?? 30;
        $trayCount = $conditions['tray_count'] ?? 1;
        $trayList = $conditions['tray_list'] ?? $crop->tray_number;
        $variety = $conditions['variety'] ?? $this->getVarietyName($crop);
        
        $data = [
            'crop_id' => $crop->id,
            'variety' => $variety,
            'tray_count' => $trayCount,
            'tray_list' => $trayList,
            'minutes_until_completion' => $minutesUntil,
        ];
        
        $subject = "Soaking Completes Today - {$variety}";
        $body = "The soaking stage for {$variety} (Tray" . ($trayCount > 1 ? "s" : "") . " {$trayList}) will complete today. Please monitor for the transition to germination stage.";
        
        Notification::route('mail', $recipients->toArray())
            ->notify(new ResourceActionRequired(
                $subject,
                $body,
                route('filament.admin.resources.crops.edit', ['record' => $crop->id]),
                'View Crop'
            ));
    }

    /**
     * Send a notification for a stage transition
     */
    protected function sendStageTransitionNotification(Crop $crop, string $targetStage, ?int $cropCount = null): void
    {
        $setting = NotificationSetting::findByTypeAndEvent('crops', 'stage_transition');
        
        if (!$setting || !$setting->shouldSendEmail()) {
            return;
        }
        
        $recipients = collect($setting->recipients);
        
        if ($recipients->isEmpty()) {
            return;
        }
        
        $data = [
            'crop_id' => $crop->id,
            'tray_number' => $crop->tray_number,
            'variety' => $this->getVarietyName($crop),
            'stage' => ucfirst($targetStage),
            'days_in_previous_stage' => $crop->daysInCurrentStage(),
        ];
        
        $subject = $setting->getEmailSubject($data);
        $body = $setting->getEmailBody($data);
        
        Notification::route('mail', $recipients->toArray())
            ->notify(new ResourceActionRequired(
                $subject,
                $body,
                route('filament.admin.resources.crops.edit', ['record' => $crop->id]),
                'View Crop'
            ));
    }

    /**
     * Find all crops in the same batch as the given crop
     */
    protected function findBatchCrops(Crop $crop)
    {
        return Crop::where('recipe_id', $crop->recipe_id)
            ->where('planting_at', $crop->planting_at)
            ->where('current_stage_id', $crop->current_stage_id)
            ->get();
    }

    /**
     * Get variety name for a crop
     */
    protected function getVarietyName(Crop $crop): string
    {
        if ($crop->recipe) {
            if ($crop->recipe->seedEntry) {
                return $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name;
            } else if ($crop->recipe->name) {
                return $crop->recipe->name;
            }
        }
        return 'Unknown';
    }

    /**
     * Get the timestamp field name for a stage
     */
    protected function getStageTimestampField(string $stage): string
    {
        return match ($stage) {
            'soaking' => 'soaking_at',
            'germination' => 'germination_at',
            'blackout' => 'blackout_at',
            'light' => 'light_at',
            'harvested' => 'harvested_at',
            default => throw new \InvalidArgumentException("Unknown stage: {$stage}")
        };
    }

    /**
     * Get the timestamp for the crop's current stage
     */
    protected function getCurrentStageTimestamp(Crop $crop): ?string
    {
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }

        if (!$crop->currentStage) {
            return null;
        }

        $timestampField = $this->getStageTimestampField($crop->currentStage->code);
        return $crop->{$timestampField};
    }
}