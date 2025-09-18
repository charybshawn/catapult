<?php

namespace App\Services;

use App\Models\Crop;
use Carbon\Carbon;

class CropTimeCalculator
{
    /**
     * Update all time-related calculated fields for a crop
     * @deprecated Use getTimeToNextStageDisplay() or access via CropBatchListView
     */
    public function updateTimeCalculations(Crop $crop): void
    {
        // This method is deprecated as these fields are now in crop_batches_list_view
        // Keeping it empty to avoid breaking existing code
    }
    
    /**
     * Get the time to next stage display value
     * This can be used when the value is needed but not available in the view
     */
    public function getTimeToNextStageDisplay(Crop $crop): string
    {
        // Ensure recipe is loaded to avoid lazy loading violations
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe || $crop->current_stage_code === 'harvested') {
            return 'Calculating...';
        }

        $expectedTransitionTime = $this->getExpectedStageTransitionTime($crop);
        
        if (!$expectedTransitionTime) {
            return 'Calculating...';
        }

        // Calculate minutes FROM now TO expected transition time (can be negative if overdue)
        $minutesRemaining = Carbon::now()->diffInMinutes($expectedTransitionTime, false);
        
        if ($minutesRemaining <= 0) {
            return 'Overdue';
        }
        
        return $this->formatTimeDisplay((int) $minutesRemaining);
    }
    
    /**
     * Get the stage age display value
     */
    public function getStageAgeDisplay(Crop $crop): string
    {
        $minutes = $this->calculateStageAge($crop);
        return $this->formatTimeDisplay($minutes);
    }
    
    /**
     * Get the total age display value
     */
    public function getTotalAgeDisplay(Crop $crop): string
    {
        $minutes = $this->calculateTotalAge($crop);
        return $this->formatTimeDisplay($minutes);
    }

    /**
     * Calculate minutes until the next stage transition
     */
    public function calculateTimeToNextStage(Crop $crop): ?int
    {
        // Ensure recipe is loaded to avoid lazy loading violations
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe || $crop->current_stage_code === 'harvested') {
            return null;
        }

        $expectedTransitionTime = $this->getExpectedStageTransitionTime($crop);
        
        if (!$expectedTransitionTime) {
            return null;
        }

        // Calculate minutes FROM now TO expected transition time
        $minutesRemaining = Carbon::now()->diffInMinutes($expectedTransitionTime, false);
        
        return max(0, (int) $minutesRemaining);
    }

    /**
     * Calculate how long the crop has been in its current stage (in minutes)
     */
    public function calculateStageAge(Crop $crop): ?int
    {
        $stageStartTime = $this->getCurrentStageStartTime($crop);
        
        if (!$stageStartTime) {
            return null;
        }

        // Calculate minutes FROM stage start TO now (positive value)
        return (int) $stageStartTime->diffInMinutes(Carbon::now());
    }

    /**
     * Calculate the total age of the crop (in minutes)
     */
    public function calculateTotalAge(Crop $crop): ?int
    {
        // For soaking crops, calculate from soaking_at
        if ($crop->requires_soaking && $crop->soaking_at) {
            return (int) Carbon::parse($crop->soaking_at)->diffInMinutes(Carbon::now());
        }
        
        // For regular crops, calculate from germination_at
        if ($crop->germination_at) {
            return (int) Carbon::parse($crop->germination_at)->diffInMinutes(Carbon::now());
        }
        
        return null;
    }

    /**
     * Get the status text for stage age
     */
    public function getStageAgeStatus(Crop $crop): string
    {
        if (!$crop->recipe) {
            return 'No recipe configured';
        }

        $stageAge = $this->calculateStageAge($crop);
        $expectedDuration = $this->getExpectedStageDuration($crop);

        if (!$stageAge || !$expectedDuration) {
            return 'Calculating...';
        }

        $expectedMinutes = $expectedDuration * 24 * 60; // Convert days to minutes
        $percentComplete = ($stageAge / $expectedMinutes) * 100;

        if ($percentComplete < 90) {
            return 'On Track';
        } elseif ($percentComplete < 110) {
            return 'Due Soon';
        } else {
            return 'Overdue';
        }
    }

    /**
     * Get the status text for total age
     */
    public function getTotalAgeStatus(Crop $crop): string
    {
        if (!$crop->recipe || !$crop->recipe->days_to_maturity) {
            return 'No maturity target';
        }

        $totalAge = $this->calculateTotalAge($crop);
        
        if (!$totalAge) {
            return 'Not started';
        }

        $expectedMinutes = $crop->recipe->days_to_maturity * 24 * 60;
        $percentComplete = ($totalAge / $expectedMinutes) * 100;

        if ($percentComplete < 80) {
            return 'Growing';
        } elseif ($percentComplete < 95) {
            return 'Nearly Ready';
        } elseif ($percentComplete < 105) {
            return 'Ready to Harvest';
        } else {
            return 'Past Due';
        }
    }

    /**
     * Format time in minutes to a human-readable string
     */
    public function formatTimeDisplay(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
        }

        $days = intval($hours / 24);
        $remainingHours = $hours % 24;

        if ($days < 7) {
            return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days}d";
        }

        $weeks = intval($days / 7);
        $remainingDays = $days % 7;

        return $remainingDays > 0 ? "{$weeks}w {$remainingDays}d" : "{$weeks}w";
    }

    /**
     * Get when the next stage transition is expected
     */
    private function getExpectedStageTransitionTime(Crop $crop): ?Carbon
    {
        $stageStartTime = $this->getCurrentStageStartTime($crop);
        $stageDuration = $this->getExpectedStageDuration($crop);

        if (!$stageStartTime || !$stageDuration) {
            return null;
        }

        // Use copy() to avoid modifying the original Carbon instance
        return $stageStartTime->copy()->addDays($stageDuration);
    }

    /**
     * Get when the current stage started
     */
    private function getCurrentStageStartTime(Crop $crop): ?Carbon
    {
        $timestamp = match ($crop->current_stage_code) {
            'soaking' => $crop->soaking_at,
            'germination' => $crop->germination_at,
            'blackout' => $crop->blackout_at,
            'light' => $crop->light_at,
            'harvested' => $crop->harvested_at,
            default => null
        };

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Get the expected duration for the current stage in days
     */
    private function getExpectedStageDuration(Crop $crop): ?float
    {
        // Ensure recipe is loaded to avoid lazy loading violations
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe) {
            return null;
        }

        return match ($crop->current_stage_code) {
            'soaking' => $crop->recipe->seed_soak_hours ? ($crop->recipe->seed_soak_hours / 24) : null,
            'germination' => $crop->recipe->germination_days,
            'blackout' => $crop->recipe->blackout_days,  
            'light' => $crop->recipe->light_days,
            'harvested' => null, // No duration for final stage
            default => null
        };
    }
}