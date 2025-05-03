<?php

namespace App\Console\Commands;

use App\Models\Crop;
use Illuminate\Console\Command;

class UpdateCropTimeToNextStage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crops:update-time-to-next-stage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update time_to_next_stage and stage_age values for all crops';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating time values for all crops...');
        
        // Disable model events to prevent infinite loops
        Crop::withoutEvents(function () {
            // Get all crops
            $crops = Crop::all();
            $count = $crops->count();
            
            $this->output->progressStart($count);
            
            foreach ($crops as $crop) {
                // Get the time to next stage status from the timeToNextStage method
                $nextStageStatus = $crop->timeToNextStage();
                $crop->time_to_next_stage_status = $nextStageStatus;
                
                // Calculate minutes for sorting
                if ($nextStageStatus === 'Ready to advance') {
                    // Highest priority (lowest minutes) for ready to advance
                    $crop->time_to_next_stage_minutes = 0;
                } elseif ($nextStageStatus === '-' || $nextStageStatus === 'No recipe' || $nextStageStatus === 'Unknown') {
                    // Lowest priority (highest minutes) for special statuses
                    $crop->time_to_next_stage_minutes = PHP_INT_MAX;
                } else {
                    // Extract time components
                    $days = preg_match('/(\d+)d/', $nextStageStatus, $dayMatches) ? (int)$dayMatches[1] : 0;
                    $hours = preg_match('/(\d+)h/', $nextStageStatus, $hourMatches) ? (int)$hourMatches[1] : 0;
                    $minutes = preg_match('/(\d+)m/', $nextStageStatus, $minuteMatches) ? (int)$minuteMatches[1] : 0;
                    
                    // Calculate total minutes
                    $crop->time_to_next_stage_minutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
                }
                
                // Get stage age status
                $stageAgeStatus = $crop->getStageAgeStatus();
                $crop->stage_age_status = $stageAgeStatus;
                
                // Calculate stage age minutes for sorting
                if ($stageAgeStatus === '0m' || empty($stageAgeStatus)) {
                    $crop->stage_age_minutes = 0;
                } else {
                    // Extract time components
                    $days = preg_match('/(\d+)d/', $stageAgeStatus, $dayMatches) ? (int)$dayMatches[1] : 0;
                    $hours = preg_match('/(\d+)h/', $stageAgeStatus, $hourMatches) ? (int)$hourMatches[1] : 0;
                    $minutes = preg_match('/(\d+)m/', $stageAgeStatus, $minuteMatches) ? (int)$minuteMatches[1] : 0;
                    
                    // Calculate total minutes
                    $crop->stage_age_minutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
                }
                
                // Calculate and store total age values
                $totalAgeStatus = $crop->getTotalAgeStatus();
                $crop->total_age_status = $totalAgeStatus;
                
                // Calculate total age minutes for sorting
                if ($totalAgeStatus === '0m' || empty($totalAgeStatus)) {
                    $crop->total_age_minutes = 0;
                } else {
                    // Extract time components
                    $days = preg_match('/(\d+)d/', $totalAgeStatus, $dayMatches) ? (int)$dayMatches[1] : 0;
                    $hours = preg_match('/(\d+)h/', $totalAgeStatus, $hourMatches) ? (int)$hourMatches[1] : 0;
                    $minutes = preg_match('/(\d+)m/', $totalAgeStatus, $minuteMatches) ? (int)$minuteMatches[1] : 0;
                    
                    // Calculate total minutes
                    $crop->total_age_minutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
                }
                
                // Save the crop without triggering events
                $crop->saveQuietly();
                
                $this->output->progressAdvance();
            }
            
            $this->output->progressFinish();
        });
        
        $this->info('Done! Updated ' . Crop::count() . ' crops.');
        
        return Command::SUCCESS;
    }
} 