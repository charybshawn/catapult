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
     * Handle the Crop "saving" event with smart time calculations.
     */
    public function saving(Crop $crop): void
    {
        // Skip during bulk operations to prevent performance issues
        if (Crop::isInBulkOperation()) {
            return;
        }
        
        // Only recalculate time values when relevant fields change
        $timeRelevantFields = ['current_stage_id', 'planting_at', 'germination_at', 'blackout_at', 'light_at', 'harvested_at'];
        if (!$crop->exists || $crop->isDirty($timeRelevantFields)) {
            $this->updateTimeCalculations($crop);
        }
        
        // Always update tray-related fields
        $this->updateTrayCalculations($crop);
    }

    /**
     * Update time calculations using the sophisticated CropTimeCalculator service.
     */
    protected function updateTimeCalculations(Crop $crop): void
    {
        try {
            // Use the sophisticated CropTimeCalculator service for all time calculations
            $timeCalculator = app(\App\Services\CropTimeCalculator::class);
            $timeCalculator->updateTimeCalculations($crop);
        } catch (\Exception $e) {
            Log::warning('CropObserver: Failed to update time calculations', [
                'crop_id' => $crop->id,
                'error' => $e->getMessage()
            ]);
            
            // Set safe fallback values to prevent null database constraints
            $crop->stage_age_minutes = $crop->stage_age_minutes ?? 0;
            $crop->stage_age_display = $crop->stage_age_display ?? '0m';
            $crop->time_to_next_stage_minutes = $crop->time_to_next_stage_minutes ?? 0;
            $crop->time_to_next_stage_display = $crop->time_to_next_stage_display ?? 'Unknown';
            $crop->total_age_minutes = $crop->total_age_minutes ?? 0;
            $crop->total_age_display = $crop->total_age_display ?? '0m';
        }
    }

    /**
     * Update tray-related calculations.
     */
    protected function updateTrayCalculations(Crop $crop): void
    {
        // Update tray count and tray numbers
        if ($crop->tray_number && !$crop->isDirty('tray_count')) {
            // Only set tray_count to 1 if it's not being explicitly changed
            $crop->tray_count = $crop->tray_count ?: 1;
            $crop->tray_numbers = (string)$crop->tray_number;
        }
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