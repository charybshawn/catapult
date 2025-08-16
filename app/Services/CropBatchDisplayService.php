<?php

namespace App\Services;

use App\Models\CropBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Service for transforming crop batch data for display purposes
 * Handles complex calculations and formatting for UI components
 */
class CropBatchDisplayService
{
    public function __construct(
        private CropTimeCalculator $timeCalculator,
        private CacheService $cache
    ) {}

    /**
     * Transform a collection of crop batches for table display
     */
    public function transformForTable(EloquentCollection $cropBatches): Collection
    {
        return $cropBatches->map(function ($batch) {
            return $this->cache->remember(
                "crop_batch_display_{$batch->id}_{$batch->updated_at->timestamp}",
                300, // 5 minutes in seconds
                fn() => $this->transformSingle($batch)
            );
        });
    }

    /**
     * Transform a single crop batch with all calculated fields
     */
    private function transformSingle(CropBatch $batch): array
    {
        $firstCrop = $batch->crops->first();
        
        // Get the current stage directly to avoid accessor conflicts
        $currentStage = null;
        if ($firstCrop && $firstCrop->current_stage_id) {
            $currentStage = \App\Models\CropStage::find($firstCrop->current_stage_id);
        }
        
        return [
            'id' => $batch->id,
            'recipe_name' => $this->formatSeedCatalogDisplay($batch),
            'crop_count' => $batch->crops_count ?? $batch->crops->count(),
            'current_stage_name' => $currentStage?->name,
            'current_stage_code' => $currentStage?->code,
            'tray_numbers' => $batch->crops->pluck('tray_number')->sort()->values()->toArray(),
            'tray_numbers_formatted' => $batch->crops->pluck('tray_number')->sort()->values()->implode(', '),
            'stage_age_display' => $this->timeCalculator->getStageAgeDisplay($firstCrop),
            'time_to_next_stage_display' => $this->timeCalculator->getTimeToNextStageDisplay($firstCrop),
            'total_age_display' => $this->timeCalculator->getTotalAgeDisplay($firstCrop),
            'germination_at' => $batch->earliest_germination,
            'germination_date_formatted' => $this->formatGerminationDate($batch->earliest_germination),
            'expected_harvest_at' => $this->calculateExpectedHarvest($batch, $firstCrop),
            'expected_harvest_formatted' => $this->formatExpectedHarvestDate($batch, $firstCrop),
            'created_at' => $batch->created_at,
            'updated_at' => $batch->updated_at,
        ];
    }

    /**
     * Calculate expected harvest date for a crop batch
     */
    private function calculateExpectedHarvest(CropBatch $batch, $firstCrop): ?Carbon
    {
        if (!$firstCrop || !$batch->recipe || !$batch->recipe->days_to_maturity) {
            return null;
        }

        $startDate = $batch->earliest_germination ?: $firstCrop->germination_at;
        
        if (!$startDate) {
            return null;
        }

        return Carbon::parse($startDate)->addDays($batch->recipe->days_to_maturity);
    }

    /**
     * Format germination date for display
     */
    private function formatGerminationDate($germinationAt): string
    {
        if (!$germinationAt) {
            return 'Unknown';
        }

        $date = is_string($germinationAt) ? Carbon::parse($germinationAt) : $germinationAt;
        return $date->format('M j, Y g:i A');
    }

    /**
     * Format expected harvest date for display
     */
    private function formatExpectedHarvestDate(CropBatch $batch, $firstCrop): string
    {
        $expectedHarvest = $this->calculateExpectedHarvest($batch, $firstCrop);
        
        if (!$expectedHarvest) {
            return 'Not calculated';
        }

        return $expectedHarvest->format('M j, Y');
    }

    /**
     * Get variety name from recipe for display
     */
    public function getVarietyName(CropBatch $batch): string
    {
        if (!$batch->recipe) {
            return 'Unknown';
        }

        // Extract just the variety part (before the lot number)
        $parts = explode(' - ', $batch->recipe->name);
        if (count($parts) >= 2) {
            return $parts[0] . ' - ' . $parts[1];
        }
        
        return $batch->recipe->name;
    }

    /**
     * Get cached display data for a single batch
     */
    public function getCachedForBatch(int $batchId): ?object
    {
        $batch = CropBatch::with(['crops', 'recipe'])->find($batchId);
        
        if (!$batch) {
            return null;
        }
        
        $cached = $this->cache->remember(
            "crop_batch_display_{$batch->id}_{$batch->updated_at->timestamp}",
            300, // 5 minutes in seconds
            fn() => $this->transformSingle($batch)
        );
        
        return (object) $cached;
    }

    /**
     * Format the seed catalog display name (common name + cultivar)
     */
    private function formatSeedCatalogDisplay(CropBatch $batch): string
    {
        if (!$batch->recipe) {
            return 'Unknown Recipe';
        }

        $commonName = $batch->recipe->masterSeedCatalog?->common_name ?? 'Unknown';
        $cultivarName = $batch->recipe->masterCultivar?->cultivar_name ?? 'Unknown';
        
        return "{$commonName} - {$cultivarName}";
    }

    /**
     * Transform for single batch display (detailed view)
     */
    public function transformForDetail(CropBatch $batch): array
    {
        $transformed = $this->transformSingle($batch);
        
        // Add additional detail fields
        $transformed['variety_name'] = $this->getVarietyName($batch);
        $transformed['tray_count'] = $transformed['crop_count'];
        
        return $transformed;
    }
}