<?php

namespace App\Actions\Crop;

use InvalidArgumentException;
use App\Models\Crop;
use Carbon\Carbon;

/**
 * Advances crops through their complete production lifecycle stages.
 * 
 * Manages sequential stage transitions in microgreens agricultural production
 * from germination through blackout, light exposure, to harvest readiness.
 * Handles automated timestamp tracking for production monitoring and ensures
 * proper workflow progression with business rule validation.
 * 
 * @business_domain Agricultural Microgreens Production Stage Management
 * @crop_lifecycle Complete stage progression: germination → blackout → light → harvested
 * @production_tracking Automated timestamp recording for each stage transition
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class AdvanceStage
{
    /**
     * Advance a crop to the next sequential stage in its agricultural production lifecycle.
     * 
     * Orchestrates stage-by-stage progression through microgreens production workflow,
     * automatically determining next appropriate stage based on current position.
     * Updates production tracking timestamps for accurate harvest timing and facility
     * management. Ensures crops follow proper agricultural development sequence
     * with appropriate environmental controls at each phase.
     * 
     * @business_process Sequential Crop Stage Advancement Workflow
     * @agricultural_context Microgreens production stages with environmental transitions
     * @production_monitoring Automatic timestamp recording for each stage milestone
     * 
     * @param Crop $crop The crop instance to advance to next production stage
     * @return Crop Updated crop with next stage assignment and timestamp
     * 
     * @throws InvalidArgumentException If crop is already at final harvest stage
     * 
     * @stage_mapping Maps stage codes to timestamp fields for production tracking:
     *   - germination → germination_at (growing phase start)
     *   - blackout → blackout_at (light control phase)
     *   - light → light_at (photosynthesis activation)
     *   - harvested → harvested_at (harvest completion)
     * 
     * @workflow_progression Follows agricultural best practices for microgreens development
     * @tracking_precision Records exact transition times for harvest planning
     * 
     * @usage Called from production management interfaces during stage transitions
     * @database_impact Updates crops table with current_stage_id and stage timestamp
     */
    public function execute(Crop $crop): Crop
    {
        $currentStage = $crop->currentStage;
        $nextStage = $currentStage->getNextStage();
        
        if (!$nextStage) {
            throw new InvalidArgumentException('Crop is already at the final stage.');
        }
        
        // Map stage codes to timestamp fields
        $timestampMap = [
            'germination' => 'germination_at',
            'blackout' => 'blackout_at', 
            'light' => 'light_at',
            'harvested' => 'harvested_at',
        ];
        
        $updateData = [
            'current_stage_id' => $nextStage->id,
        ];
        
        // Set the appropriate timestamp
        if (isset($timestampMap[$nextStage->code])) {
            $updateData[$timestampMap[$nextStage->code]] = Carbon::now();
        }
        
        $crop->update($updateData);
        
        return $crop->fresh();
    }
}