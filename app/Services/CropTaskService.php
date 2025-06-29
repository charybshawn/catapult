<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\NotificationSetting;
use App\Models\TaskSchedule;
use App\Services\TaskFactoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CropTaskService
{
    /**
     * Constructor injection for dependencies
     */
    public function __construct(
        private TaskFactoryService $taskFactory
    ) {}
    /**
     * Schedule all stage transition tasks for a crop
     *
     * @param Crop $crop
     * @return void
     */
    public function scheduleAllStageTasks(Crop $crop): void
    {
        // Prevent memory issues during bulk operations
        if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB
            \Illuminate\Support\Facades\Log::warning('Memory limit approaching, skipping task scheduling', [
                'crop_id' => $crop->id,
                'memory_usage' => memory_get_usage(true)
            ]);
            return;
        }
        
        $this->taskFactory->deleteTasksForCrop($crop);
        
        // Only schedule tasks if the crop has a recipe
        if (!$crop->recipe) {
            return;
        }
        
        $recipe = $crop->recipe;
        $plantedAt = $crop->planting_at;
        $currentStage = $crop->current_stage;
        
        // Get durations from recipe
        $germDays = $recipe->germination_days;
        $blackoutDays = $recipe->blackout_days;
        $lightDays = $recipe->light_days;
        
        // Calculate stage transition times using precise days calculation
        // We no longer need germinationTime calculation since we start at germination
        $blackoutTime = $plantedAt->copy()->addDays($germDays);
        $lightTime = $blackoutTime->copy()->addDays($blackoutDays);
        $harvestTime = $lightTime->copy()->addDays($lightDays);
        
        $now = Carbon::now();
        
        // Only schedule tasks for future stages
        // Remove planting-to-germination transition since we start at germination
        
        if ($currentStage === 'germination' && $blackoutTime->gt($now)) {
            // Skip blackout stage if blackoutDays is 0
            if ($blackoutDays > 0) {
                $this->createStageTransitionTask($crop, 'blackout', $blackoutTime);
            } else {
                // If skipping blackout, we go straight to light
                $this->createStageTransitionTask($crop, 'light', $blackoutTime);
            }
        }
        
        // Only create light transition if we're going through blackout stage
        if ($currentStage === 'blackout' && $lightTime->gt($now)) {
            $this->createStageTransitionTask($crop, 'light', $lightTime);
        }
        
        if (in_array($currentStage, ['germination', 'blackout', 'light']) && $harvestTime->gt($now)) {
            $this->createStageTransitionTask($crop, 'harvested', $harvestTime);
        }

        // Schedule Suspend Watering Task (if applicable)
        if ($recipe->suspend_water_hours > 0) {
            $suspendTime = $harvestTime->copy()->subHours($recipe->suspend_water_hours);
            if ($suspendTime->isAfter($plantedAt) && $suspendTime->gt($now)) { // Only schedule if in the future and after planting
                // Get variety name (reuse logic from createStageTransitionTask)
                $varietyName = 'Unknown';
                if ($crop->recipe) {
                    if ($crop->recipe->seedEntry) {
                        $varietyName = $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name;
                    } else if ($crop->recipe->name) {
                        $varietyName = $crop->recipe->name;
                    }
                }
                
                // Create conditions - Note: No target_stage needed here
                $conditions = [
                    'crop_id' => (int) $crop->id,
                    'tray_number' => $crop->tray_number,
                    'variety' => $varietyName,
                ];

                // Create the watering suspension task
                $task = $this->taskFactory->createWateringSuspensionTask($crop, $suspendTime);
                $task->is_active = true;
                $task->next_run_at = $suspendTime;
                $task->save();
            } else {
                 Log::warning("Suspend watering time ({$suspendTime->toDateTimeString()}) is before planting time ({$plantedAt->toDateTimeString()}) or in the past for Crop ID {$crop->id}. Task not generated.");
            }
        }
    }
    
    /**
     * Create a task for a stage transition
     *
     * @param Crop $crop
     * @param string $targetStage
     * @param Carbon $transitionTime
     * @return TaskSchedule
     */
    protected function createStageTransitionTask(Crop $crop, string $targetStage, Carbon $transitionTime): TaskSchedule
    {
        // Format task name: "advance_to_X"
        $taskName = "advance_to_{$targetStage}";
        
        // Get variety name with proper fallbacks
        $varietyName = 'Unknown';
        if ($crop->recipe) {
            if ($crop->recipe->seedEntry) {
                $varietyName = $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name;
            } else if ($crop->recipe->name) {
                $varietyName = $crop->recipe->name;
            }
        }

        // Find other crops in the same batch (same recipe, planting_at date, and current_stage)
        $batchTrays = Crop::where('recipe_id', $crop->recipe_id)
            ->where('planting_at', $crop->planting_at)
            ->where('current_stage', $crop->current_stage)
            ->pluck('tray_number')
            ->toArray();
            
        $batchTraysList = implode(', ', $batchTrays);
        $batchSize = count($batchTrays);
        
        // Create conditions for the task - using batch information instead of individual tray
        $conditions = [
            'crop_id' => (int) $crop->id, // Keep main crop ID as reference
            'batch_identifier' => "{$crop->recipe_id}_{$crop->planting_at->format('Y-m-d')}_{$crop->current_stage}",
            'target_stage' => $targetStage,
            'tray_numbers' => $batchTrays,
            'tray_count' => $batchSize,
            'tray_list' => $batchTraysList,
            'variety' => $varietyName,
        ];
        
        // Create the task schedule using the factory
        return $this->taskFactory->createBatchStageTransitionTask(
            $crop,
            $targetStage,
            $transitionTime,
            $conditions
        );
    }
    
    /**
     * Delete existing stage transition tasks for a crop
     *
     * @param Crop $crop
     * @return void
     */
    protected function deleteExistingTasks(Crop $crop): void
    {
        // Create a batch identifier
        $batchIdentifier = "{$crop->recipe_id}_{$crop->planting_at->format('Y-m-d')}_{$crop->current_stage}";
        
        // Find and delete all tasks related to this batch
        TaskSchedule::where('resource_type', 'crops')
            ->where(function($query) use ($crop, $batchIdentifier) {
                $query->where('conditions->crop_id', $crop->id)
                      ->orWhere('conditions->batch_identifier', $batchIdentifier);
            })
            ->delete();
    }
    
    /**
     * Process a crop stage transition task
     *
     * @param TaskSchedule $task
     * @return array
     */
    public function processCropStageTask(TaskSchedule $task): array
    {
        $conditions = $task->conditions;
        $cropId = $conditions['crop_id'] ?? null;
        $targetStage = $conditions['target_stage'] ?? null;
        $batchIdentifier = $conditions['batch_identifier'] ?? null;
        $trayNumbers = $conditions['tray_numbers'] ?? null;
        
        if (!$targetStage) {
            return [
                'success' => false,
                'message' => 'Invalid task conditions: missing target_stage',
            ];
        }
        
        // Process batch if we have batch information
        if ($batchIdentifier && is_array($trayNumbers) && count($trayNumbers) > 0) {
            // Find all crops in this batch
            $batchParts = explode('_', $batchIdentifier);
            if (count($batchParts) === 3) {
                list($recipeId, $plantedAtDate, $currentStage) = $batchParts;
                
                $crops = Crop::where('recipe_id', $recipeId)
                    ->where('planting_at', $plantedAtDate)
                    ->where('current_stage', $currentStage)
                    ->whereIn('tray_number', $trayNumbers)
                    ->get();
                
                if ($crops->isEmpty()) {
                    return [
                        'success' => false,
                        'message' => "No crops found in batch with identifier {$batchIdentifier}",
                    ];
                }
                
                // Check stage order - use first crop as reference since all crops in batch should be in same stage
                $firstCrop = $crops->first();
                $stageOrder = ['germination', 'blackout', 'light', 'harvested'];
                $currentStageIndex = array_search($firstCrop->current_stage, $stageOrder);
                $targetStageIndex = array_search($targetStage, $stageOrder);
                
                if ($currentStageIndex === false || $targetStageIndex === false) {
                    return [
                        'success' => false,
                        'message' => "Invalid stage definition for batch {$batchIdentifier} or task target stage {$targetStage}",
                    ];
                }
                
                // Proceed only if all crops are in the stage immediately preceding the target stage
                // or if they're earlier in the sequence (allowing multi-stage advancement for overdue alerts)
                if ($currentStageIndex < $targetStageIndex) {
                    // For overdue alerts, we may need to advance through multiple stages
                    $stagesNeeded = [];
                    for ($i = $currentStageIndex + 1; $i <= $targetStageIndex; $i++) {
                        $stagesNeeded[] = $stageOrder[$i];
                    }
                    
                    // Send notification that action is due
                    // Only need to send one notification for the batch
                    $this->sendStageTransitionNotification($firstCrop, $targetStage, count($crops));
                    
                    // Mark the task as processed
                    $task->is_active = false;
                    $task->last_run_at = now();
                    $task->save();
                    
                    // Process each crop in the batch
                    foreach ($crops as $crop) {
                        // For each stage needed, advance the crop
                        foreach ($stagesNeeded as $index => $nextStage) {
                            // Only log for the final stage advancement
                            $isFinalStage = ($index === count($stagesNeeded) - 1);
                            
                            // If we need to go through multiple stages, move through them silently until the target
                            if ($isFinalStage) {
                                // This is the target stage, so use advanceStage which will trigger events
                                $crop->advanceStage();
                            } else {
                                // This is an intermediate stage, manually update to avoid triggering extra alerts
                                $stageField = "{$nextStage}_at";
                                $crop->$stageField = now();
                                $crop->current_stage = $nextStage;
                                $crop->saveQuietly(); // Save without firing events
                            }
                        }
                    }
                    
                    return [
                        'success' => true,
                        'message' => "Batch {$batchIdentifier} ({$crops->count()} crops) advanced to {$targetStage} stage" . 
                                     (count($stagesNeeded) > 1 ? " (skipped " . (count($stagesNeeded) - 1) . " intermediate stages)" : "") . ".",
                    ];
                } elseif ($currentStageIndex >= $targetStageIndex) {
                    // If the crops are already at or past the target stage, simply deactivate the task
                    $task->is_active = false;
                    $task->last_run_at = now();
                    $task->save();
                    return [
                        'success' => true,
                        'message' => "Batch {$batchIdentifier} is already at or past {$targetStage} stage. Task deactivated.",
                    ];
                } else {
                    // If the crops are not yet ready for this stage transition, log it and do nothing to the task.
                    // The task will be picked up again by the scheduler later.
                    return [
                        'success' => true,
                        'message' => "Batch {$batchIdentifier} not yet ready for {$targetStage}. Notification not sent."
                    ];
                }
            }
        }
        
        // Fallback to single crop processing if no batch information available
        // Find the crop
        $crop = Crop::find($cropId);
        if (!$crop) {
            return [
                'success' => false,
                'message' => "Crop with ID {$cropId} not found",
            ];
        }
        
        // Check if the crop is ready for the action indicated by the task
        $stageOrder = ['germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($crop->current_stage, $stageOrder);
        $targetStageIndex = array_search($targetStage, $stageOrder);

        // Validate stage order before attempting index access
        if ($currentStageIndex === false || $targetStageIndex === false) {
            return [
                'success' => false,
                'message' => "Invalid stage definition for crop ID {$cropId} or task target stage {$targetStage}",
            ];
        }

        // Proceed only if the crop is in the stage immediately preceding the target stage
        // or if it's earlier in the sequence (allowing multi-stage advancement for overdue alerts)
        if ($currentStageIndex < $targetStageIndex) {
            // For overdue alerts, we may need to advance through multiple stages
            $stagesNeeded = [];
            for ($i = $currentStageIndex + 1; $i <= $targetStageIndex; $i++) {
                $stagesNeeded[] = $stageOrder[$i];
            }
            
            // Send notification that action is due
            $this->sendStageTransitionNotification($crop, $targetStage);
            
            // Mark the task as processed (notification sent)
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            // For each stage needed, advance the crop
            foreach ($stagesNeeded as $index => $nextStage) {
                // Only log for the final stage advancement
                $isFinalStage = ($index === count($stagesNeeded) - 1);
                
                // If we need to go through multiple stages, move through them silently until the target
                if ($isFinalStage) {
                    // This is the target stage, so use advanceStage which will trigger events
                    $crop->advanceStage();
                } else {
                    // This is an intermediate stage, manually update to avoid triggering extra alerts
                    $stageField = "{$nextStage}_at";
                    $crop->$stageField = now();
                    $crop->current_stage = $nextStage;
                    $crop->saveQuietly(); // Save without firing events
                }
            }
            
            return [
                'success' => true,
                'message' => "Crop ID {$cropId} advanced to {$targetStage} stage" . 
                             (count($stagesNeeded) > 1 ? " (skipped " . (count($stagesNeeded) - 1) . " intermediate stages)" : "") . ".",
            ];
        } elseif ($currentStageIndex >= $targetStageIndex) {
             // If the crop is already at or past the target stage, simply deactivate the task
             $task->is_active = false;
             $task->last_run_at = now();
             $task->save();
             return [
                 'success' => true,
                 'message' => "Crop ID {$cropId} is already at or past {$targetStage} stage. Task deactivated.",
             ];
        } else {
             // If the crop is not yet ready for this stage transition, log it and do nothing to the task.
             return [
                 'success' => true,
                 'message' => "Crop ID {$cropId} not yet ready for {$targetStage}. Notification not sent."
             ];
        }
    }
    
    /**
     * Send a notification for a stage transition
     *
     * @param Crop $crop
     * @param string $targetStage
     * @param int|null $cropCount Number of crops in batch if batch notification
     * @return void
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
            'variety' => ($crop->recipe->seedEntry ? $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name : 'Unknown'),
            'stage' => ucfirst($targetStage),
            'days_in_previous_stage' => $crop->daysInCurrentStage(),
        ];
        
        $subject = $setting->getEmailSubject($data);
        $body = $setting->getEmailBody($data);
        
        // Use the Notification facade to send the email
        \Illuminate\Support\Facades\Notification::route('mail', $recipients->toArray())
            ->notify(new \App\Notifications\ResourceActionRequired(
                $subject,
                $body,
                route('filament.admin.resources.crops.edit', ['record' => $crop->id]),
                'View Crop'
            ));
    }
} 