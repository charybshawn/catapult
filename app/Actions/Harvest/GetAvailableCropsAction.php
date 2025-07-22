<?php

namespace App\Actions\Harvest;

use App\Models\Crop;

/**
 * Business logic for retrieving available crops for harvesting
 * Extracted from HarvestResource lines 84-100
 */
class GetAvailableCropsAction
{
    /**
     * Get available crops for a given cultivar that can be harvested
     *
     * @param int $cultivarId
     * @return array
     */
    public function execute(int $cultivarId): array
    {
        if (!$cultivarId) {
            return [];
        }
        
        return Crop::with(['recipe.masterSeedCatalog', 'currentStage'])
            ->whereHas('recipe', function ($query) use ($cultivarId) {
                $query->whereHas('masterSeedCatalog', function ($q) use ($cultivarId) {
                    $q->whereHas('masterCultivars', function ($mc) use ($cultivarId) {
                        $mc->where('id', $cultivarId);
                    });
                });
            })
            ->whereHas('currentStage', function ($query) {
                $query->whereNotIn('code', ['harvested', 'cancelled']);
            })
            ->get()
            ->mapWithKeys(function ($crop) {
                return [$crop->id => $this->formatCropOption($crop)];
            })
            ->toArray();
    }

    /**
     * Format crop option for display in dropdown
     *
     * @param Crop $crop
     * @return string
     */
    protected function formatCropOption(Crop $crop): string
    {
        $stageName = $crop->currentStage->name ?? 'Unknown';
        $plantedDate = $crop->planting_at ? $crop->planting_at->format('M j') : 'Not planted';
        
        return "Tray {$crop->tray_number} - {$stageName} (Planted: {$plantedDate})";
    }

    /**
     * Check if a crop is available for harvesting
     *
     * @param Crop $crop
     * @return bool
     */
    public function isCropAvailableForHarvest(Crop $crop): bool
    {
        // Load current stage if not already loaded
        if (!$crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }

        // Check if crop has a current stage and is not harvested or cancelled
        return $crop->currentStage && 
               !in_array($crop->currentStage->code, ['harvested', 'cancelled']);
    }

    /**
     * Get crops by cultivar with stage filtering
     *
     * @param int $cultivarId
     * @param array $excludeStages
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCropsByCultivarWithStageFilter(int $cultivarId, array $excludeStages = ['harvested', 'cancelled'])
    {
        return Crop::with(['recipe.masterSeedCatalog', 'currentStage'])
            ->whereHas('recipe', function ($query) use ($cultivarId) {
                $query->whereHas('masterSeedCatalog', function ($q) use ($cultivarId) {
                    $q->whereHas('masterCultivars', function ($mc) use ($cultivarId) {
                        $mc->where('id', $cultivarId);
                    });
                });
            })
            ->whereHas('currentStage', function ($query) use ($excludeStages) {
                $query->whereNotIn('code', $excludeStages);
            })
            ->get();
    }
}