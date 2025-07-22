<?php

namespace App\Actions\CropAlert;

use App\Models\CropAlert;
use App\Services\CropTaskManagementService;

class ExecuteCropAlert
{
    protected CropTaskManagementService $cropTaskService;

    public function __construct(CropTaskManagementService $cropTaskService)
    {
        $this->cropTaskService = $cropTaskService;
    }

    /**
     * Execute a crop alert immediately
     */
    public function execute(CropAlert $record): array
    {
        return $this->cropTaskService->processCropStageTask($record);
    }
}