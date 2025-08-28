<?php

namespace App\Actions\Crop;

use InvalidArgumentException;
use RuntimeException;
use App\Models\Crop;
use App\Models\CropStage;
use Carbon\Carbon;

/**
 * Advances crops from soaking to germination stage in microgreens production.
 * 
 * Handles the critical transition from seed soaking to active growing phase
 * in agricultural microgreens production. Validates soaking duration completion,
 * updates crop stage tracking, and assigns production tray numbers for facility
 * organization and workflow management.
 * 
 * @business_domain Agricultural Microgreens Production Workflow
 * @crop_lifecycle Soaking to Germination Stage Transition Management
 * @production_tracking Tray assignment and facility organization
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class AdvanceFromSoaking
{
    /**
     * Advance a crop from soaking to germination stage with agricultural timing validation.
     * 
     * Performs critical stage transition in microgreens production lifecycle, moving
     * crops from controlled soaking environment to active growing phase. Validates
     * minimum soaking time requirements per variety specifications, updates production
     * tracking with germination timestamp, and assigns or updates tray numbers for
     * facility organization and harvest planning.
     * 
     * @business_process Soaking to Germination Transition Workflow
     * @agricultural_context Microgreens production stage management with timing controls
     * @production_validation Ensures proper soaking duration before germination advance
     * 
     * @param Crop $crop The crop record to advance from soaking stage
     * @param string|null $trayNumber Optional tray number assignment for production tracking
     * @return Crop Updated crop instance with germination stage and timestamp
     * 
     * @throws InvalidArgumentException If crop is not in soaking stage
     * @throws InvalidArgumentException If soaking time requirements not met
     * @throws RuntimeException If germination stage configuration missing
     * 
     * @stage_validation Confirms current_stage === 'soaking' before transition
     * @timing_control Validates getSoakingTimeRemaining() <= 0 minutes
     * @tracking_update Sets germination_at timestamp for production monitoring
     * 
     * @usage Called from crop management UI during production workflow transitions
     * @database_impact Updates crops table with stage_id, tray_number, germination_at
     */
    public function execute(Crop $crop, ?string $trayNumber = null): Crop
    {
        // Validate crop is in soaking stage
        if ($crop->current_stage !== 'soaking') {
            throw new InvalidArgumentException('Crop must be in soaking stage to advance from soaking.');
        }
        
        // Check if soaking time is complete
        $soakingTimeRemaining = $crop->getSoakingTimeRemaining();
        if ($soakingTimeRemaining > 0) {
            throw new InvalidArgumentException("Soaking not complete. {$soakingTimeRemaining} minutes remaining.");
        }
        
        // Get germination stage
        $germinationStage = CropStage::findByCode('germination');
        if (!$germinationStage) {
            throw new RuntimeException('Germination stage not found in database.');
        }
        
        // Update crop to germination stage
        $crop->update([
            'current_stage_id' => $germinationStage->id,
            'tray_number' => $trayNumber ?: $crop->tray_number,
            'germination_at' => Carbon::now(),
        ]);
        
        return $crop->fresh();
    }
}