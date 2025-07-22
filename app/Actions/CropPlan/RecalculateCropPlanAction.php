<?php

namespace App\Actions\CropPlan;

use App\Models\CropPlan;
use App\Services\HarvestYieldCalculator;

/**
 * Pure business logic for recalculating crop plans with latest harvest data
 * NOT a Filament class - independent business logic called FROM Filament hooks
 */
class RecalculateCropPlanAction
{
    protected HarvestYieldCalculator $yieldCalculator;

    public function __construct(HarvestYieldCalculator $yieldCalculator)
    {
        $this->yieldCalculator = $yieldCalculator;
    }

    /**
     * Execute crop plan recalculation using latest harvest data
     * 
     * @param CropPlan $cropPlan The crop plan to recalculate
     * @return array Recalculation results including success status and message
     */
    public function execute(CropPlan $cropPlan): array
    {
        $recipe = $cropPlan->recipe;

        if (!$recipe) {
            return $this->buildErrorResult('No recipe associated with this crop plan');
        }

        // Store original values for comparison
        $oldYield = $cropPlan->grams_per_tray;
        $oldTrays = $cropPlan->trays_needed;

        // Calculate new yield and tray requirements
        $newYield = $this->yieldCalculator->calculatePlanningYield($recipe);
        $newTrays = ceil($cropPlan->grams_needed / $newYield);

        // Get yield statistics for detailed tracking
        $stats = $this->yieldCalculator->getYieldStats($recipe);

        // Update the crop plan with new calculations
        $this->updateCropPlan($cropPlan, $newYield, $newTrays, $oldYield, $oldTrays, $stats);

        // Build success message
        $message = $this->buildSuccessMessage($oldTrays, $newTrays, $stats);

        return $this->buildSuccessResult($message, $oldYield, $newYield, $oldTrays, $newTrays, $stats);
    }

    /**
     * Update the crop plan with new calculations and tracking details
     */
    protected function updateCropPlan(CropPlan $cropPlan, float $newYield, int $newTrays, float $oldYield, int $oldTrays, array $stats): void
    {
        $cropPlan->update([
            'grams_per_tray' => $newYield,
            'trays_needed' => $newTrays,
            'calculation_details' => array_merge(
                $cropPlan->calculation_details ?? [],
                [
                    'recalculated_at' => now()->toISOString(),
                    'harvest_data_used' => $stats['harvest_count'] > 0,
                    'old_yield' => $oldYield,
                    'new_yield' => $newYield,
                    'old_trays' => $oldTrays,
                    'new_trays' => $newTrays,
                    'yield_stats' => $stats,
                ]
            ),
        ]);
    }

    /**
     * Build success message based on recalculation results
     */
    protected function buildSuccessMessage(int $oldTrays, int $newTrays, array $stats): string
    {
        $message = "Recalculated: {$oldTrays} → {$newTrays} trays";
        
        if ($stats['harvest_count'] > 0) {
            $message .= " (using {$stats['harvest_count']} harvest records)";
        } else {
            $message .= ' (using recipe expected yield)';
        }

        return $message;
    }

    /**
     * Build success result array
     */
    protected function buildSuccessResult(string $message, float $oldYield, float $newYield, int $oldTrays, int $newTrays, array $stats): array
    {
        return [
            'success' => true,
            'message' => $message,
            'old_yield' => $oldYield,
            'new_yield' => $newYield,
            'old_trays' => $oldTrays,
            'new_trays' => $newTrays,
            'yield_stats' => $stats,
        ];
    }

    /**
     * Build error result array
     */
    protected function buildErrorResult(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
        ];
    }
}