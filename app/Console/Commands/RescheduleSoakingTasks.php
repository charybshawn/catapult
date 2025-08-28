<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Crop;
use App\Services\CropTaskManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RescheduleSoakingTasks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crops:reschedule-soaking-tasks';

    /**
     * The console command description.
     */
    protected $description = 'Reschedule tasks for existing soaking crops to apply new alert logic';

    /**
     * Execute the console command.
     */
    public function handle(CropTaskManagementService $taskService): int
    {
        $this->info('Finding soaking crops...');
        
        // Find all crops that are currently in soaking stage
        $soakingCrops = Crop::whereHas('currentStage', function($query) {
            $query->where('code', 'soaking');
        })
        ->where('requires_soaking', true)
        ->get();
        
        $this->info("Found {$soakingCrops->count()} soaking crops");
        
        if ($soakingCrops->isEmpty()) {
            $this->info('No soaking crops found to reschedule.');
            return 0;
        }
        
        $rescheduled = 0;
        
        foreach ($soakingCrops as $crop) {
            try {
                $this->line("Rescheduling tasks for crop {$crop->id} (Tray {$crop->tray_number})...");
                
                // Reschedule all tasks for this crop
                $taskService->scheduleAllStageTasks($crop);
                $rescheduled++;
                
                $this->line("  ✓ Tasks rescheduled for crop {$crop->id}");
                
            } catch (Exception $e) {
                $this->error("  ✗ Error rescheduling crop {$crop->id}: " . $e->getMessage());
                Log::error('Error rescheduling soaking crop tasks', [
                    'crop_id' => $crop->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("Successfully rescheduled tasks for {$rescheduled} soaking crops.");
        
        return 0;
    }
}