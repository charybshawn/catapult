<?php

namespace App\Services;

use App\Models\Crop;
use Carbon\Carbon;

class CropTimeCalculator
{
    /**
     * Update all time-related calculated fields for a crop
     */
    public function updateTimeCalculations(Crop $crop): void
    {
        // Prevent recursive saves and memory issues
        if ($crop->isDirty() && !$crop->exists) {
            // Skip calculations for new models to prevent infinite loops
            return;
        }
        
        $crop->time_to_next_stage_minutes = $this->calculateTimeToNextStage($crop);
        $crop->time_to_next_stage_display = $this->formatTimeDisplay($crop->time_to_next_stage_minutes);
        
        $crop->stage_age_minutes = $this->calculateStageAge($crop);
        $crop->stage_age_display = $this->formatTimeDisplay($crop->stage_age_minutes);
        
        $crop->total_age_minutes = $this->calculateTotalAge($crop);
        $crop->total_age_display = $this->formatTimeDisplay($crop->total_age_minutes);
        
        // Only save if the model exists and has changes
        if ($crop->exists && $crop->isDirty()) {
            $crop->saveQuietly(); // Use saveQuietly to prevent triggering observers
        }
    }

    /**
     * Calculate minutes until the next stage transition
     */
    public function calculateTimeToNextStage(Crop $crop): ?int
    {
        if (!$crop->recipe || $crop->current_stage === 'harvested') {
            return null;
        }

        $expectedTransitionTime = $this->getExpectedStageTransitionTime($crop);
        
        if (!$expectedTransitionTime) {
            return null;
        }

        $minutesRemaining = Carbon::now()->diffInMinutes($expectedTransitionTime, false);
        
        return max(0, $minutesRemaining);
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

        return $stageStartTime->diffInMinutes(Carbon::now());
    }

    /**
     * Calculate the total age of the crop since planting (in minutes)
     */
    public function calculateTotalAge(Crop $crop): ?int
    {
        if (!$crop->planted_at) {
            return null;
        }

        return Carbon::parse($crop->planted_at)->diffInMinutes(Carbon::now());
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

        return $stageStartTime->addDays($stageDuration);
    }

    /**
     * Get when the current stage started
     */
    private function getCurrentStageStartTime(Crop $crop): ?Carbon
    {
        $timestamp = match ($crop->current_stage) {
            'germination' => $crop->germination_at ?? $crop->planted_at,
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
    private function getExpectedStageDuration(Crop $crop): ?int
    {
        if (!$crop->recipe) {
            return null;
        }

        return match ($crop->current_stage) {
            'germination' => $crop->recipe->germination_days,
            'blackout' => $crop->recipe->blackout_days,
            'light' => $crop->recipe->light_days,
            'harvested' => null, // No duration for final stage
            default => null
        };
    }
}