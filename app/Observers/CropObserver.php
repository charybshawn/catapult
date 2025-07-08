<?php

namespace App\Observers;

use App\Models\Crop;
use App\Events\OrderCropPlanted;
use App\Events\AllCropsReady;
use App\Events\OrderHarvested;
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
    
    /**
     * Handle the Crop "updated" event.
     */
    public function updated(Crop $crop): void
    {
        // Check if planting_at was just set (crop was planted)
        if ($crop->wasChanged('planting_at') && $crop->planting_at && $crop->order_id) {
            event(new OrderCropPlanted($crop->order, $crop));
        }
        
        // Check if crop is now ready to harvest
        if ($crop->wasChanged(['current_stage', 'stage_age_minutes']) && $crop->isReadyToHarvest() && $crop->order_id) {
            // Check if all crops for this order are ready
            $order = $crop->order;
            if ($order && $order->crops->every(fn($c) => $c->isReadyToHarvest())) {
                event(new AllCropsReady($order));
            }
        }
        
        // Check if crop was just harvested
        if ($crop->wasChanged('current_stage') && $crop->current_stage === 'harvested' && $crop->order_id) {
            // Check if all crops for this order are harvested
            $order = $crop->order;
            if ($order && $order->crops->every(fn($c) => $c->current_stage === 'harvested')) {
                event(new OrderHarvested($order));
            }
        }
    }
} 