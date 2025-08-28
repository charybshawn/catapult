<?php

namespace App\Console\Commands;

use Exception;
use App\Models\TaskSchedule;
use App\Services\ResourceMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckResourceLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-resource-levels {--resource= : The resource type to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check resource levels and send notifications if thresholds are met';

    /**
     * Execute the console command.
     */
    public function handle(ResourceMonitorService $monitorService)
    {
        $resourceType = $this->option('resource');
        
        $query = TaskSchedule::query()->where('is_active', true);
        
        if ($resourceType) {
            $query->where('resource_type', $resourceType);
            $this->info("Checking tasks for resource type: {$resourceType}");
        } else {
            $this->info("Checking all scheduled tasks");
        }
        
        $dueTasks = $query->get()->filter(function ($task) {
            return $task->isDue();
        });
        
        $this->info("Found {$dueTasks->count()} due tasks");
        
        foreach ($dueTasks as $task) {
            $this->info("Processing task: {$task->task_name} for {$task->resource_type}");
            
            try {
                $result = $monitorService->processTask($task);
                
                if ($result['success']) {
                    $this->info("  - Processed successfully: {$result['message']}");
                } else {
                    $this->warn("  - Failed: {$result['message']}");
                }
                
                // Mark the task as run regardless of the result to avoid repeated processing
                $task->markAsRun();
            } catch (Exception $e) {
                $this->error("  - Error processing task: {$e->getMessage()}");
                Log::error("Error processing task {$task->id}: {$e->getMessage()}", [
                    'task' => $task->toArray(),
                    'exception' => $e,
                ]);
            }
        }
        
        return Command::SUCCESS;
    }
}
