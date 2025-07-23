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

        // Note: Calculated columns like stage_age_minutes, time_to_next_stage_display, etc.
        // have been moved to the crop_batches_list_view and are no longer stored on individual crops.
        // These values can be accessed through the CropBatchListView when needed.
        
        // The only update we might still need is ensuring tray_count is set correctly
        // if it's still a column in the crops table
        if ($crop->tray_number && !$crop->tray_count) {
            $crop->tray_count = 1;
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
        // Check if crop stage was changed (crop was planted/advanced)
        if ($crop->wasChanged('current_stage_id') && $crop->current_stage_id && $crop->order_id) {
            // If moving to germination stage, it means crop was planted
            $germinationStage = \App\Models\CropStage::findByCode('germination');
            if ($germinationStage && $crop->current_stage_id == $germinationStage->id) {
                event(new OrderCropPlanted($crop->order, $crop));
            }
        }
        
        // Check if crop is now ready to harvest
        if ($crop->wasChanged('current_stage_id') && $crop->isReadyToHarvest() && $crop->order_id) {
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