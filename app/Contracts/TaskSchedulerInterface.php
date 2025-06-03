<?php

namespace App\Contracts;

use App\Models\Crop;
use Carbon\Carbon;

interface TaskSchedulerInterface
{
    /**
     * Schedule a stage transition task for a crop
     */
    public function scheduleStageTask(
        Crop $crop,
        string $taskType,
        Carbon $scheduledAt,
        string $description
    ): void;

    /**
     * Delete all existing tasks for a crop
     */
    public function deleteTasksForCrop(Crop $crop): void;

    /**
     * Schedule all stage transition tasks for a crop
     */
    public function scheduleAllStageTasks(Crop $crop): void;
}