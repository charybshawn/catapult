<?php

namespace App\Actions\Crop;

use App\Models\Crop;
use Carbon\Carbon;

class AdvanceStage
{
    /**
     * Advance a crop to the next stage in its lifecycle.
     *
     * @param Crop $crop
     * @return Crop
     */
    public function execute(Crop $crop): Crop
    {
        $currentStage = $crop->currentStage;
        $nextStage = $currentStage->getNextStage();
        
        if (!$nextStage) {
            throw new \InvalidArgumentException('Crop is already at the final stage.');
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