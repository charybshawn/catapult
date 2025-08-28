<?php

namespace App\Filament\Resources\CropResource\Utilities;

use App\Models\CropBatch;
use App\Models\Crop;

/**
 * Shared query logic for crop operations
 * Eliminates duplicate getCropsForRecord implementations
 */
class CropQueryHelpers
{
    /**
     * Get crops for a record (handles batch grouping)
     * Centralized implementation to eliminate duplication
     */
    public static function getCropsForRecord($record)
    {
        // If this is a CropBatch, use its ID as the crop_batch_id
        if ($record instanceof CropBatch) {
            return Crop::where('crop_batch_id', $record->id)
                ->with(['recipe', 'currentStage'])
                ->get();
        }
        
        // If has crop_batch_id, use that
        if ($record->crop_batch_id) {
            return Crop::where('crop_batch_id', $record->crop_batch_id)
                ->with(['recipe', 'currentStage'])
                ->get();
        }
        
        // Fall back to implicit batching
        return Crop::where('recipe_id', $record->recipe_id)
            ->where('germination_at', $record->germination_at)
            ->where('current_stage_id', $record->current_stage_id)
            ->with(['recipe', 'currentStage'])
            ->get();
    }

    /**
     * Get first real crop for a grouped record
     */
    public static function getFirstCropForRecord($record): ?Crop
    {
        // If this is already a real crop, return it
        if ($record instanceof Crop && $record->exists && $record->tray_number) {
            return $record;
        }
        
        // Otherwise find the first real crop in the batch
        return self::getCropsForRecord($record)->first();
    }
}