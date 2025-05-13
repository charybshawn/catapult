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
        
        // Process in batches to avoid memory issues
        Crop::chunk(100, function ($crops) use ($bar) {
            foreach ($crops as $crop) {
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
                
                // Update stage age
                $stageAgeStatus = $crop->getStageAgeStatus();
                $crop->stage_age_display = $stageAgeStatus;
                
                // Calculate stage age minutes for sorting
                $days = preg_match('/(\d+)d/', $stageAgeStatus, $dayMatches) ? (int)$dayMatches[1] : 0;
                $hours = preg_match('/(\d+)h/', $stageAgeStatus, $hourMatches) ? (int)$hourMatches[1] : 0;
                $minutes = preg_match('/(\d+)m/', $stageAgeStatus, $minuteMatches) ? (int)$minuteMatches[1] : 0;
                
                // Calculate total minutes
                $totalStageMinutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
                $crop->stage_age_minutes = $totalStageMinutes;
                
                // Add debug logging to track what's being calculated
                $this->info(sprintf(
                    "Crop #%d: Stage %s, Age: %s (%d minutes), Started: %s",
                    $crop->id,
                    $crop->current_stage,
                    $stageAgeStatus,
                    $totalStageMinutes,
                    $crop->{$crop->current_stage . '_at'}
                ));
                
                // Update total age
                $totalAgeStatus = $crop->getTotalAgeStatus();
                $crop->total_age_display = $totalAgeStatus;
                
                // Calculate total age minutes for sorting
                $days = preg_match('/(\d+)d/', $totalAgeStatus, $dayMatches) ? (int)$dayMatches[1] : 0;
                $hours = preg_match('/(\d+)h/', $totalAgeStatus, $hourMatches) ? (int)$hourMatches[1] : 0;
                $minutes = preg_match('/(\d+)m/', $totalAgeStatus, $minuteMatches) ? (int)$minuteMatches[1] : 0;
                
                // Calculate total minutes
                $totalAgeMinutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
                $crop->total_age_minutes = $totalAgeMinutes;
                
                // Save the crop
                $crop->save();
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        $this->info('All crop time fields have been updated successfully!');
        
        return Command::SUCCESS;
    }
}
