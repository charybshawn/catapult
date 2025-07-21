<?php

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Models\CropStage;
use Carbon\Carbon;

class AdvanceFromSoaking
{
    /**
     * Advance a crop from soaking to germination stage.
     *
     * @param Crop $crop
     * @param string|null $trayNumber
     * @return Crop
     */
    public function execute(Crop $crop, ?string $trayNumber = null): Crop
    {
        // Validate crop is in soaking stage
        if ($crop->current_stage !== 'soaking') {
            throw new \InvalidArgumentException('Crop must be in soaking stage to advance from soaking.');
        }
        
        // Check if soaking time is complete
        $soakingTimeRemaining = $crop->getSoakingTimeRemaining();
        if ($soakingTimeRemaining > 0) {
            throw new \InvalidArgumentException("Soaking not complete. {$soakingTimeRemaining} minutes remaining.");
        }
        
        // Get germination stage
        $germinationStage = CropStage::findByCode('germination');
        if (!$germinationStage) {
            throw new \RuntimeException('Germination stage not found in database.');
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