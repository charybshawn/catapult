<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Crop;
use Illuminate\Support\Facades\DB;

class UpdateCropTimeFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-crop-time-fields';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates time-related fields for all crops (time_to_next_stage, stage_age, total_age)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating time fields for all crops...');
        $this->info('This may take a moment for large datasets.');
        
        $total = Crop::count();
        $this->info("Found {$total} crops to update.");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $updatedCount = 0;
        $now = now(); // Capture current time once for consistent calculations
        
        // Process in batches to avoid memory issues
        Crop::chunk(100, function ($crops) use ($bar, &$updatedCount, $now) {
            foreach ($crops as $crop) {
                // Track original values for logging
                $originalTimeToNextStage = $crop->time_to_next_stage_minutes;
                $originalStageAge = $crop->stage_age_minutes;
                $originalTotalAge = $crop->total_age_minutes;
                
                // Update time to next stage
                $timeToNextStage = $crop->timeToNextStage();
                $crop->time_to_next_stage_display = $timeToNextStage;
                
                // Calculate minutes for sorting
                if (str_contains($timeToNextStage, 'Ready to advance')) {
                    // Highest priority (lowest minutes) for ready to advance
                    $crop->time_to_next_stage_minutes = 0;
                } elseif ($timeToNextStage === '-' || $timeToNextStage === 'No recipe' || $timeToNextStage === 'Unknown') {
                    // Lowest priority (highest minutes) for special statuses
                    $crop->time_to_next_stage_minutes = 2147483647; // Max integer value
                } else {
                    // Extract time components
                    $days = preg_match('/(\d+)d/', $timeToNextStage, $dayMatches) ? (int)$dayMatches[1] : 0;
                    $hours = preg_match('/(\d+)h/', $timeToNextStage, $hourMatches) ? (int)$hourMatches[1] : 0;
                    $minutes = preg_match('/(\d+)m/', $timeToNextStage, $minuteMatches) ? (int)$minuteMatches[1] : 0;
                    
                    // Calculate total minutes
                    $totalMinutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
                    $crop->time_to_next_stage_minutes = $totalMinutes;
                }
                
                // Update stage age using the current time
                $stageField = "{$crop->current_stage}_at";
                if ($crop->$stageField) {
                    $stageStart = \Carbon\Carbon::parse($crop->$stageField);
                    // Calculate real-time stage age 
                    $stageAgeMinutes = abs($now->diffInMinutes($stageStart));
                    $stageAgeDisplay = $this->formatDuration($now->diff($stageStart));
                    
                    $crop->stage_age_minutes = $stageAgeMinutes;
                    $crop->stage_age_display = $stageAgeDisplay;
                    
                    $this->info(sprintf(
                        "Crop #%d: Stage %s, Age: %s (%d minutes), Started: %s",
                        $crop->id,
                        $crop->current_stage,
                        $stageAgeDisplay,
                        $stageAgeMinutes,
                        $crop->$stageField
                    ));
                }
                
                // Update total age using the current time
                if ($crop->planted_at) {
                    $plantedAt = \Carbon\Carbon::parse($crop->planted_at);
                    $totalAgeMinutes = abs($now->diffInMinutes($plantedAt));
                    $totalAgeDisplay = $this->formatDuration($now->diff($plantedAt));
                    
                    $crop->total_age_minutes = $totalAgeMinutes;
                    $crop->total_age_display = $totalAgeDisplay;
                }
                
                // Log significant changes for debugging
                if (abs($originalTimeToNextStage - $crop->time_to_next_stage_minutes) > 60 || 
                    abs($originalStageAge - $crop->stage_age_minutes) > 60 || 
                    abs($originalTotalAge - $crop->total_age_minutes) > 60) {
                    $this->info("Significant update for Crop #{$crop->id}: " . 
                        "Stage age: {$originalStageAge}m → {$crop->stage_age_minutes}m, " .
                        "Time to next: {$originalTimeToNextStage}m → {$crop->time_to_next_stage_minutes}m, " .
                        "Total age: {$originalTotalAge}m → {$crop->total_age_minutes}m");
                    $updatedCount++;
                }
                
                // Save the crop with the updated times
                $crop->save();
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        $this->info("All crop time fields have been updated successfully! {$updatedCount} crops had significant changes.");
        
        return Command::SUCCESS;
    }
    
    /**
     * Format a DateInterval into a human-readable duration.
     */
    protected function formatDuration(\DateInterval $interval): string
    {
        $parts = [];
        
        if ($interval->d > 0) {
            $parts[] = $interval->d . 'd';
        }
        if ($interval->h > 0) {
            $parts[] = $interval->h . 'h';
        }
        if ($interval->i > 0 && empty($parts)) {
            $parts[] = $interval->i . 'm';
        }
        
        return implode(' ', $parts) ?: '0m';
    }
}
