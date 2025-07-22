<?php

namespace App\Actions\Order;

use App\Models\Crop;
use App\Services\CropTaskManagementService;
use Carbon\Carbon;

/**
 * Handle watering suspension and resumption operations for Order relation manager
 */
class ToggleWateringAction
{
    public function __construct(
        private CropTaskManagementService $cropTaskService
    ) {}

    /**
     * Toggle watering status for a crop (suspend if active, resume if suspended)
     */
    public function execute(Crop $crop): void
    {
        if ($crop->watering_suspended_at) {
            $this->resumeWatering($crop);
        } else {
            $this->suspendWatering($crop);
        }
    }

    /**
     * Suspend watering for a crop
     */
    public function suspendWatering(Crop $crop, ?Carbon $timestamp = null): void
    {
        $this->cropTaskService->suspendWatering($crop, $timestamp);
    }

    /**
     * Resume watering for a crop
     */
    public function resumeWatering(Crop $crop): void
    {
        $this->cropTaskService->resumeWatering($crop);
    }

    /**
     * Suspend watering for multiple crops (bulk operation)
     */
    public function suspendBulk(iterable $crops): void
    {
        foreach ($crops as $crop) {
            $this->suspendWatering($crop);
        }
    }

    /**
     * Resume watering for multiple crops (bulk operation)
     */
    public function resumeBulk(iterable $crops): void
    {
        foreach ($crops as $crop) {
            $this->resumeWatering($crop);
        }
    }

    /**
     * Get the appropriate label for watering toggle action
     */
    public function getToggleLabel(Crop $crop): string
    {
        return $crop->watering_suspended_at ? 'Resume Watering' : 'Suspend Watering';
    }

    /**
     * Get the appropriate icon for watering toggle action
     */
    public function getToggleIcon(Crop $crop): string
    {
        return $crop->watering_suspended_at ? 'heroicon-o-play' : 'heroicon-o-pause';
    }
}