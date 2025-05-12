<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Models\CropTask; // No longer using CropTask model directly here
use App\Models\TaskSchedule; // Using TaskSchedule model now
use App\Models\Crop;
use App\Models\User;
use App\Notifications\CropTaskActionDue; // Import the new notification class
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification; // Import Notification facade
use Carbon\Carbon;

class ProcessCropTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Updated signature to reflect focus on TaskSchedule
    protected $signature = 'app:process-task-schedules {--type=crops : The resource type to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    // Updated description
    protected $description = 'Sends notifications for due TaskSchedules based on resource type.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $resourceType = $this->option('type');
        $this->info("Processing pending {$resourceType} task schedules...");
        Log::info("[ProcessTaskSchedules] Starting task processing for type: {$resourceType}...");
        $now = Carbon::now();

        // Query active TaskSchedules for the specified type that are due
        $dueTasksQuery = TaskSchedule::where('resource_type', $resourceType)
                                  ->where('is_active', true)
                                  ->where('next_run_at', '<=', $now);
                                  // Optional: Add check for notified_at to avoid rapid re-notification
                                  // ->where(function ($query) {
                                  //     $query->whereNull('notified_at')
                                  //           ->orWhere('notified_at', '<', now()->subMinutes(5)); // e.g., don't re-notify within 5 mins
                                  // });

        $dueTasks = $dueTasksQuery->get();

        if ($dueTasks->isEmpty()) {
            $this->info("No due {$resourceType} task schedules found.");
            Log::info("[ProcessTaskSchedules] No due {$resourceType} tasks found.");
            return 0;
        }

        $this->info("Found {$dueTasks->count()} due {$resourceType} tasks.");
        $notificationSentCount = 0;
        $errorCount = 0;

        // Determine users to notify (adjust logic as needed - e.g., specific roles, user associated with resource)
        // For now, notify all Admin users (assuming an 'Admin' role exists)
        $usersToNotify = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); 
        })->get();

        if ($usersToNotify->isEmpty()) {
            Log::warning("[ProcessTaskSchedules] No users found to notify for {$resourceType} tasks.");
            $this->warn('No users found to notify.');
            // Depending on requirements, you might want to stop here or log this as an error.
        }

        foreach ($dueTasks as $task) {
            Log::debug("[ProcessTaskSchedules] Processing TaskSchedule ID: {$task->id}, Name: {$task->task_name}");
            try {
                // Extract crop_id from conditions
                $cropId = $task->conditions['crop_id'] ?? null;
                
                if (!$cropId) {
                    Log::warning("[ProcessTaskSchedules] TaskSchedule ID {$task->id} is missing crop_id in conditions. Skipping.");
                    $errorCount++;
                    // Optionally mark task inactive or log error state
                    // $task->update(['is_active' => false, 'status' => 'error_missing_data']);
                    continue;
                }
                
                $crop = Crop::find($cropId);

                if (!$crop) {
                    Log::warning("[ProcessTaskSchedules] Associated crop (ID: {$cropId}) for TaskSchedule ID {$task->id} not found. Skipping.");
                    $errorCount++;
                    // Mark the task inactive so we don't keep trying
                    $task->update(['is_active' => false, 'last_run_at' => $now]); 
                    continue;
                }

                // Send the database notification
                if (!$usersToNotify->isEmpty()) {
                    Notification::send($usersToNotify, new CropTaskActionDue($task, $crop));
                    $notificationSentCount++;
                    Log::info("[ProcessTaskSchedules] Sent CropTaskActionDue notification for TaskSchedule ID {$task->id} to {$usersToNotify->count()} users.");
                    
                    // Optional: Update notified_at timestamp on the task schedule
                    // $task->update(['notified_at' => $now]);
                } else {
                    Log::warning("[ProcessTaskSchedules] No users to notify for TaskSchedule ID {$task->id}. Notification not sent.");
                }
                
                // *** CRITICAL: Do NOT mark the task as inactive here ***
                // The task remains active until the user performs the manual action.

            } catch (\Exception $e) {
                Log::error("[ProcessTaskSchedules] Error processing TaskSchedule ID {$task->id}: " . $e->getMessage(), ['exception' => $e]);
                $errorCount++;
                // Do not deactivate task on generic error, let scheduler retry unless it's a permanent issue like missing crop.
                continue;
            }
        }

        Log::info("[ProcessTaskSchedules] Loop finished for {$resourceType}. Notifications Sent: {$notificationSentCount}, Errors: {$errorCount}.");
        $this->info("Processing complete. Notifications Sent: {$notificationSentCount}, Errors: {$errorCount}.");
        return 0;
    }
}
