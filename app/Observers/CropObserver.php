<?php

namespace App\Observers;

use App\Models\Crop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CropObserver
{
    /**
     * Handle the Crop "saving" event.
     */
    public function saving(Crop $crop): void
    {
        $this->updateCalculatedColumns($crop);
    }

    /**
     * Update all calculated columns for the crop.
     */
    protected function updateCalculatedColumns(Crop $crop): void
    {
        // Ensure recipe is loaded to avoid lazy loading violations
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        // Always use the current time for calculations
        $now = now();

        // Update stage age
        $stageField = "{$crop->current_stage}_at";
        if ($crop->$stageField) {
            $stageStart = Carbon::parse($crop->$stageField);
            // Calculate stage age using the current timestamp, not updated_at
            $crop->stage_age_minutes = abs($now->diffInMinutes($stageStart));
            $crop->stage_age_display = $this->formatDuration($now->diff($stageStart));
            
            // Debug logging only in debug mode to prevent memory issues
            if (config('app.debug') && config('logging.default') !== 'production') {
                Log::debug('CropObserver: Updated stage age', [
                    'crop_id' => $crop->id,
                    'current_stage' => $crop->current_stage,
                    'diff_minutes' => $crop->stage_age_minutes,
                ]);
            }
        }

        // Update time to next stage
        if ($crop->recipe) {
            $stageDuration = match ($crop->current_stage) {
                'germination' => $crop->recipe->germination_days ?? 0,
                'blackout' => $crop->recipe->blackout_days ?? 0,
                'light' => $crop->recipe->light_days ?? 0,
                default => 0,
            };

            if ($stageDuration > 0) {
                $stageStart = Carbon::parse($crop->$stageField);
                $stageEnd = $stageStart->copy()->addDays($stageDuration);
                
                if ($now->gt($stageEnd)) {
                    $crop->time_to_next_stage_minutes = 0;
                    $crop->time_to_next_stage_display = 'Ready to advance';
                } else {
                    $crop->time_to_next_stage_minutes = abs($now->diffInMinutes($stageEnd));
                    $crop->time_to_next_stage_display = $this->formatDuration($now->diff($stageEnd));
                }
            }
        }

        // Update total age
        if ($crop->planting_at) {
            $plantedAt = Carbon::parse($crop->planting_at);
            $crop->total_age_minutes = abs($now->diffInMinutes($plantedAt));
            $crop->total_age_display = $this->formatDuration($now->diff($plantedAt));
        }

        // Update expected harvest date
        if ($crop->recipe && $crop->planting_at) {
            $plantedAt = Carbon::parse($crop->planting_at);
            $daysToMaturity = $crop->recipe->days_to_maturity ?? 0;
            if ($daysToMaturity > 0) {
                $crop->expected_harvest_at = $plantedAt->copy()->addDays($daysToMaturity);
            }
        }

        // Update tray count and tray numbers
        if ($crop->tray_number) {
            $crop->tray_count = 1;
            $crop->tray_numbers = (string)$crop->tray_number;
        }
    }

    /**
     * Format a DateInterval into a human-readable duration.
     */
    public function formatDuration(\DateInterval $interval): string
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