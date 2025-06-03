<?php

namespace App\Services;

use App\Models\TaskSchedule;
use App\Models\Crop;
use Carbon\Carbon;

class TaskFactoryService
{
    /**
     * Create a new task schedule
     */
    public function createTaskSchedule(
        Crop $crop,
        string $taskType,
        Carbon $scheduledAt,
        string $description
    ): TaskSchedule {
        return TaskSchedule::create([
            'crop_id' => $crop->id,
            'task_type' => $taskType,
            'scheduled_at' => $scheduledAt,
            'description' => $description,
            'status' => 'pending',
        ]);
    }

    /**
     * Create a stage transition task
     */
    public function createStageTransitionTask(
        Crop $crop,
        string $newStage,
        Carbon $scheduledAt
    ): TaskSchedule {
        $description = "Advance crop #{$crop->tray_number} from {$crop->current_stage} to {$newStage}";
        
        $task = $this->createTaskSchedule(
            $crop,
            'stage_transition',
            $scheduledAt,
            $description
        );
        
        // Set additional properties for compatibility with legacy task system
        $task->resource_type = 'crops';
        $task->frequency = 'once';
        $task->is_active = true;
        $task->next_run_at = $scheduledAt;
        $task->save();
        
        return $task;
    }

    /**
     * Create a batch stage transition task with full conditions
     */
    public function createBatchStageTransitionTask(
        Crop $crop,
        string $targetStage,
        Carbon $scheduledAt,
        array $conditions
    ): TaskSchedule {
        $taskName = "advance_to_{$targetStage}";
        
        $task = new TaskSchedule();
        $task->resource_type = 'crops';
        $task->task_name = $taskName;
        $task->frequency = 'once';
        $task->conditions = $conditions;
        $task->is_active = true;
        $task->next_run_at = $scheduledAt;
        $task->save();
        
        return $task;
    }

    /**
     * Create a watering suspension task
     */
    public function createWateringSuspensionTask(
        Crop $crop,
        Carbon $scheduledAt
    ): TaskSchedule {
        $description = "Suspend watering for crop #{$crop->tray_number} before harvest";
        
        return $this->createTaskSchedule(
            $crop,
            'suspend_watering',
            $scheduledAt,
            $description
        );
    }

    /**
     * Create a harvest reminder task
     */
    public function createHarvestReminderTask(
        Crop $crop,
        Carbon $scheduledAt
    ): TaskSchedule {
        $description = "Harvest crop #{$crop->tray_number} - Ready for harvest";
        
        return $this->createTaskSchedule(
            $crop,
            'harvest_reminder',
            $scheduledAt,
            $description
        );
    }

    /**
     * Delete all tasks for a specific crop
     */
    public function deleteTasksForCrop(Crop $crop): int
    {
        // Handle both new and legacy task structures
        return TaskSchedule::where(function($query) use ($crop) {
            $query->where('crop_id', $crop->id)
                  ->orWhere('resource_type', 'crops')
                  ->where('conditions->crop_id', $crop->id);
        })->delete();
    }
}