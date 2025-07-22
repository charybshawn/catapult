<?php

namespace App\Actions\Order;

use App\Models\Crop;
use App\Services\CropTaskManagementService;
use Carbon\Carbon;

/**
 * Handle crop stage advancement operations for Order relation manager
 */
class AdvanceStageAction
{
    public function __construct(
        private CropTaskManagementService $cropTaskService
    ) {}

    /**
     * Advance a single crop to the next stage
     */
    public function execute(Crop $crop, ?Carbon $timestamp = null): void
    {
        $this->cropTaskService->advanceStage($crop, $timestamp);
    }

    /**
     * Advance multiple crops to the next stage (bulk operation)
     */
    public function executeBulk(iterable $crops): void
    {
        foreach ($crops as $crop) {
            if ($crop->current_stage !== 'harvested') {
                $this->execute($crop);
            }
        }
    }

    /**
     * Check if a crop can be advanced to the next stage
     */
    public function canAdvance(Crop $crop): bool
    {
        return $crop->current_stage !== 'harvested';
    }
}