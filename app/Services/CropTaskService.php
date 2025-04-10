<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\NotificationSetting;
use App\Models\TaskSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CropTaskService
{
    /**
     * Schedule all stage transition tasks for a crop
     *
     * @param Crop $crop
     * @return void
     */
    public function scheduleAllStageTasks(Crop $crop): void
    {
        $this->deleteExistingTasks($crop);
        
        // Only schedule tasks if the crop has a recipe
        if (!$crop->recipe) {
            return;
        }
        
        $recipe = $crop->recipe;
        $plantedAt = $crop->planted_at;
        $currentStage = $crop->current_stage;
        
        // Get durations from recipe
        $germDays = $recipe->germination_days;
        $blackoutDays = $recipe->blackout_days;
        $lightDays = $recipe->light_days;
        
        // Calculate stage transition times
        $germinationTime = $plantedAt->copy()->addDays(1); // Typically 1 day after planting
        $blackoutTime = $germinationTime->copy()->addDays($germDays);
        $lightTime = $blackoutTime->copy()->addDays($blackoutDays);
        $harvestTime = $lightTime->copy()->addDays($lightDays);
        
        $now = Carbon::now();
        
        // Only schedule tasks for future stages
        if ($currentStage === 'planting' && $germinationTime->gt($now)) {
            $this->createStageTransitionTask($crop, 'germination', $germinationTime);
        }
        
        if (in_array($currentStage, ['planting', 'germination']) && $blackoutTime->gt($now)) {
            // Skip blackout stage if blackoutDays is 0
            if ($blackoutDays > 0) {
                $this->createStageTransitionTask($crop, 'blackout', $blackoutTime);
            } else {
                // If skipping blackout, we go straight to light
                $this->createStageTransitionTask($crop, 'light', $blackoutTime);
            }
        }
        
        if (in_array($currentStage, ['planting', 'germination', 'blackout']) && $lightTime->gt($now)) {
            $this->createStageTransitionTask($crop, 'light', $lightTime);
        }
        
        if (in_array($currentStage, ['planting', 'germination', 'blackout', 'light']) && $harvestTime->gt($now)) {
            $this->createStageTransitionTask($crop, 'harvested', $harvestTime);
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
        
        // Create conditions for the task
        $conditions = [
            'crop_id' => $crop->id,
            'target_stage' => $targetStage,
            'tray_number' => $crop->tray_number,
            'variety' => $crop->recipe->seedVariety->name ?? 'Unknown',
        ];
        
        // Create the task schedule
        $task = new TaskSchedule();
        $task->resource_type = 'crops';
        $task->task_name = $taskName;
        $task->frequency = 'once'; // One-time task
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $transitionTime;
        $task->save();
        
        return $task;
    }
    
    /**
     * Delete existing stage transition tasks for a crop
     *
     * @param Crop $crop
     * @return void
     */
    protected function deleteExistingTasks(Crop $crop): void
    {
        // Find and delete all tasks related to this crop
        TaskSchedule::where('resource_type', 'crops')
            ->where('conditions->crop_id', $crop->id)
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
        
        if (!$cropId || !$targetStage) {
            return [
                'success' => false,
                'message' => 'Invalid task conditions: missing crop_id or target_stage',
            ];
        }
        
        // Find the crop
        $crop = Crop::find($cropId);
        if (!$crop) {
            return [
                'success' => false,
                'message' => "Crop with ID {$cropId} not found",
            ];
        }
        
        // Check if the crop is already at or past the target stage
        $stageOrder = ['planting', 'germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($crop->current_stage, $stageOrder);
        $targetStageIndex = array_search($targetStage, $stageOrder);
        
        if ($currentStageIndex >= $targetStageIndex) {
            return [
                'success' => true,
                'message' => "Crop is already at or past {$targetStage} stage",
            ];
        }
        
        // If the crop's current stage is just before the target stage, we can advance it
        if ($currentStageIndex === $targetStageIndex - 1) {
            // Advance to the target stage
            $crop->current_stage = $targetStage;
            
            // Set the timestamp for the new stage
            $stageTimestampField = "{$targetStage}_at";
            $crop->$stageTimestampField = now();
            
            $crop->save();
            
            // Send notification
            $this->sendStageTransitionNotification($crop, $targetStage);
            
            // Mark the task as completed
            $task->is_active = false;
            $task->last_run_at = now();
            $task->save();
            
            return [
                'success' => true,
                'message' => "Advanced crop to {$targetStage} stage",
            ];
        }
        
        // If we're not ready to advance directly to the target stage,
        // we should create a new task for the intermediate stage
        $nextStage = $stageOrder[$currentStageIndex + 1];
        
        // Calculate time for next stage
        $days = 1; // Default
        if ($nextStage === 'germination') {
            $days = 1;
        } elseif ($nextStage === 'blackout') {
            $days = $crop->recipe->germination_days;
        } elseif ($nextStage === 'light') {
            $days = $crop->recipe->blackout_days;
        } elseif ($nextStage === 'harvested') {
            $days = $crop->recipe->light_days;
        }
        
        $nextTime = now()->addDays($days);
        
        $this->createStageTransitionTask($crop, $nextStage, $nextTime);
        
        // Mark the current task as completed
        $task->is_active = false;
        $task->last_run_at = now();
        $task->save();
        
        return [
            'success' => true,
            'message' => "Created new task to advance crop to {$nextStage} stage",
        ];
    }
    
    /**
     * Send a notification for a stage transition
     *
     * @param Crop $crop
     * @param string $targetStage
     * @return void
     */
    protected function sendStageTransitionNotification(Crop $crop, string $targetStage): void
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
            'variety' => $crop->recipe->seedVariety->name ?? 'Unknown',
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