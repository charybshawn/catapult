<?php

namespace App\Observers;

use DateInterval;
use App\Models\CropStage;
use App\Models\Crop;
use App\Events\OrderCropPlanted;
use App\Events\AllCropsReady;
use App\Events\OrderHarvested;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive crop lifecycle observer for agricultural workflow automation.
 * 
 * Monitors crop model lifecycle events to automatically trigger agricultural
 * business events based on crop stage transitions. Handles crop planting detection,
 * harvest readiness evaluation, and harvest completion tracking. Critical component
 * for automated order progression through microgreens production workflow.
 * 
 * @business_domain Agricultural crop lifecycle monitoring and workflow automation
 * @agricultural_process Crop stage transition detection and business event triggering
 * @workflow_automation Order progression based on crop milestone achievements
 * @event_driven Triggers business events for crop planting, readiness, and harvest
 */
class CropObserver
{
    /**
     * Handle crop model saving event with calculated column updates.
     * 
     * Processes crop model before persistence to update calculated fields
     * and ensure data consistency for agricultural production tracking.
     * Maintains tray count and other derived fields.
     * 
     * @param Crop $crop Crop model being saved
     * @return void
     * 
     * @data_integrity Ensures calculated fields are updated before persistence
     * @agricultural_tracking Maintains crop production metadata consistency
     */
    public function saving(Crop $crop): void
    {
        $this->updateCalculatedColumns($crop);
    }

    /**
     * Update calculated columns and derived fields for agricultural crop tracking.
     * 
     * Ensures calculated fields are properly set for crop production monitoring.
     * Handles recipe relationship loading and tray count calculations while
     * respecting migration to crop batch list view for complex calculations.
     * 
     * @param Crop $crop Crop model to update calculated fields for
     * @return void
     * 
     * @calculation_management Updates derived fields for agricultural tracking
     * @performance_optimization Handles recipe loading to prevent lazy loading issues
     * @migration_aware Respects move of complex calculations to crop batch views
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
     * Format date intervals into human-readable duration strings for agricultural timing.
     * 
     * Converts DateInterval objects to readable duration strings for crop timing
     * displays in agricultural production monitoring. Prioritizes larger time units
     * and provides fallback for short durations.
     * 
     * @param DateInterval $interval Time interval to format
     * @return string Human-readable duration string (e.g., '2d 4h', '45m')
     * 
     * @agricultural_timing Formats crop stage durations for production monitoring
     * @user_interface Provides readable time displays for agricultural staff
     */
    public function formatDuration(DateInterval $interval): string
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
     * Handle crop model update event with agricultural workflow automation.
     * 
     * Monitors crop stage changes to automatically trigger agricultural business
     * events including order crop planted, all crops ready, and order harvested.
     * Essential for automated order progression through microgreens production workflow.
     * 
     * @param Crop $crop Updated crop model with potential stage changes
     * @return void
     * 
     * @workflow_automation Triggers business events based on crop stage milestones
     * @agricultural_events Planting detection, harvest readiness, completion tracking
     * @order_integration Links crop lifecycle to order fulfillment workflow
     */
    public function updated(Crop $crop): void
    {
        // Check if crop stage was changed (crop was planted/advanced)
        if ($crop->wasChanged('current_stage_id') && $crop->current_stage_id && $crop->order_id) {
            // If moving to germination stage, it means crop was planted
            $germinationStage = CropStage::findByCode('germination');
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