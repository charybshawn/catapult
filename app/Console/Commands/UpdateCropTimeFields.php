<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Models\Crop;
use App\Services\CropTimeCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateCropTimeFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-crop-time-fields 
                            {--batch-size= : Number of crops to process in each batch}
                            {--quiet-mode : Only show summary, not individual updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates time-related fields for all crops (time_to_next_stage, stage_age, total_age)';

    /**
     * The crop time calculator service.
     *
     * @var CropTimeCalculator
     */
    protected $calculator;

    /**
     * Create a new command instance.
     */
    public function __construct(CropTimeCalculator $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('This command is deprecated.');
        $this->info('Time-related calculated columns have been moved to the crop_batches_list_view database view.');
        $this->info('These values are now calculated dynamically and do not need to be updated.');
        return 0;
        
        $total = Crop::whereHas('currentStage', function($q) {
            $q->where('code', '!=', 'harvested');
        })->count();
        $this->info("Found {$total} active crops to update.");
        
        if ($total === 0) {
            $this->info('No active crops to update.');
            return Command::SUCCESS;
        }
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $updatedCount = 0;
        $errorCount = 0;
        $batchSize = (int) ($this->option('batch-size') ?: config('tasks.batch_size', 100));
        $quietMode = $this->option('quiet-mode');
        
        // Process in batches to avoid memory issues
        Crop::whereHas('currentStage', function($q) {
                $q->where('code', '!=', 'harvested');
            })
            ->with(['recipe', 'currentStage']) // Eager load recipe and currentStage to avoid N+1 queries
            ->chunk($batchSize, function ($crops) use ($bar, &$updatedCount, &$errorCount, $quietMode) {
                foreach ($crops as $crop) {
                    try {
                        // Track original values for comparison
                        $originalTimeToNextStage = $crop->time_to_next_stage_minutes;
                        $originalStageAge = $crop->stage_age_minutes;
                        $originalTotalAge = $crop->total_age_minutes;
                        
                        // Use the CropTimeCalculator service to update all time fields
                        $this->calculator->updateTimeCalculations($crop);
                        
                        // Check if there were significant changes
                        if (abs($originalTimeToNextStage - $crop->time_to_next_stage_minutes) > 60 || 
                            abs($originalStageAge - $crop->stage_age_minutes) > 60 || 
                            abs($originalTotalAge - $crop->total_age_minutes) > 60) {
                            
                            $updatedCount++;
                            
                            if (!$quietMode) {
                                $this->info(sprintf(
                                    "\nCrop #%d (%s): Time to next: %s → %s, Stage age: %s → %s",
                                    $crop->id,
                                    $crop->recipe ? $crop->recipe->name : 'No Recipe',
                                    $this->formatMinutes($originalTimeToNextStage),
                                    $crop->time_to_next_stage_display,
                                    $this->formatMinutes($originalStageAge),
                                    $crop->stage_age_display
                                ));
                            }
                        }
                        
                    } catch (Exception $e) {
                        $errorCount++;
                        Log::error('Failed to update crop time fields', [
                            'crop_id' => $crop->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        if (!$quietMode) {
                            $this->error("\nFailed to update Crop #{$crop->id}: " . $e->getMessage());
                        }
                    }
                    
                    $bar->advance();
                }
            });
        
        $bar->finish();
        $this->newLine(2);
        
        // Summary
        $this->info("Update completed!");
        $this->info("- Total crops processed: {$total}");
        $this->info("- Crops with significant changes: {$updatedCount}");
        
        if ($errorCount > 0) {
            $this->error("- Errors encountered: {$errorCount}");
            $this->error("Check the logs for details.");
        }
        
        // Log summary for monitoring
        Log::info('Crop time fields update completed', [
            'total_crops' => $total,
            'updated_count' => $updatedCount,
            'error_count' => $errorCount
        ]);
        
        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
    
    /**
     * Format minutes into a readable string.
     */
    protected function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return 'N/A';
        }
        
        if ($minutes < 60) {
            return "{$minutes}m";
        }
        
        $hours = intval($minutes / 60);
        if ($hours < 24) {
            return "{$hours}h";
        }
        
        $days = intval($hours / 24);
        return "{$days}d";
    }
}
