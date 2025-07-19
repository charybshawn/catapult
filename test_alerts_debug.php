<?php

require_once 'vendor/autoload.php';

use App\Models\Crop;
use App\Models\TaskSchedule;
use App\Services\CropTaskManagementService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel app
$app = new Application(realpath(__DIR__));
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing alert generation..." . PHP_EOL;

try {
    // Clear existing alerts
    $deletedCount = TaskSchedule::where('resource_type', 'crops')->delete();
    echo "Deleted {$deletedCount} existing crop alerts" . PHP_EOL;
    
    // Find active crops
    $crops = Crop::with(['recipe', 'currentStage'])
        ->whereHas('currentStage', function($query) {
            $query->where('code', '!=', 'harvested');
        })->get();
    
    echo "Found {$crops->count()} active crops to process" . PHP_EOL;
    
    $cropTaskService = app(CropTaskManagementService::class);
    
    foreach ($crops as $crop) {
        echo "Processing crop {$crop->id} (Tray {$crop->tray_number}, Stage: {$crop->currentStage->code})" . PHP_EOL;
        
        try {
            $cropTaskService->scheduleAllStageTasks($crop);
            echo "  ✓ Tasks scheduled successfully" . PHP_EOL;
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    // Check how many tasks were created
    $newTaskCount = TaskSchedule::where('resource_type', 'crops')->count();
    echo PHP_EOL . "Created {$newTaskCount} new crop tasks" . PHP_EOL;
    
    // Show the created tasks
    $tasks = TaskSchedule::where('resource_type', 'crops')->get();
    foreach ($tasks as $task) {
        echo "- {$task->name} (runs at: {$task->next_run_at})" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}