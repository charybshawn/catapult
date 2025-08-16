<?php

namespace App\Observers;

use App\Models\CropBatch;

/**
 * Observer for CropBatch model to handle cache invalidation.
 */
class CropBatchObserver
{
    /**
     * Handle the CropBatch "retrieved" event.
     * 
     * @param CropBatch $cropBatch
     * @return void
     */
    public function retrieved(CropBatch $cropBatch): void
    {
        // Reset cache when model is freshly retrieved
        $cropBatch->invalidateFirstCropCache();
    }

    /**
     * Handle the CropBatch "saved" event.
     * 
     * @param CropBatch $cropBatch
     * @return void
     */
    public function saved(CropBatch $cropBatch): void
    {
        // Invalidate cache when batch is saved
        $cropBatch->invalidateFirstCropCache();
    }
}