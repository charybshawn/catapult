<?php

namespace App\Actions\Harvest;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Crop;

/**
 * Retrieves available crops for agricultural harvest operations.
 * 
 * Provides filtered crop availability queries for microgreens harvest workflows,
 * managing crop eligibility based on production stages, cultivar relationships,
 * and harvest readiness. Supports harvest interface dropdown populations and
 * crop availability validation for production management.
 * 
 * @business_domain Agricultural Microgreens Harvest Availability Management
 * @crop_filtering Stage-based availability with cultivar relationship validation
 * @harvest_readiness Production stage filtering for harvestable crops
 * 
 * @architecture Extracted from HarvestResource for reusable business logic
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class GetAvailableCropsAction
{
    /**
     * Retrieve available crops for specific cultivar formatted for harvest interface.
     * 
     * Queries crops associated with specified cultivar that are in harvestable stages,
     * formats results for dropdown selection with detailed tray and stage information.
     * Filters out harvested and cancelled crops while providing comprehensive crop
     * context for harvest operation selection.
     * 
     * @business_process Harvest Interface Crop Population Workflow
     * @agricultural_context Cultivar-specific crop availability for microgreens harvest
     * @ui_integration Formatted for Filament dropdown option display
     * 
     * @param int $cultivarId Master cultivar ID to filter crops by variety
     * @return array Formatted crop options keyed by crop ID with descriptive labels
     * 
     * @query_optimization Eager loads recipe.masterSeedCatalog and currentStage relationships
     * @relationship_filtering Uses nested whereHas for cultivar association validation
     * @stage_filtering Excludes 'harvested' and 'cancelled' stage codes
     * 
     * @format_structure Returns [crop_id => "Tray {number} - {stage} (Planted: {date})"]
     * @empty_handling Returns empty array for invalid cultivar ID
     * 
     * @usage Called from harvest form interfaces for crop selection dropdowns
     * @performance Optimized with eager loading and efficient relationship queries
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
     * Format individual crop for user-friendly dropdown display.
     * 
     * Creates descriptive crop option labels including tray identification,
     * current production stage, and planting date for harvest interface selection.
     * Provides comprehensive crop context for informed harvest decisions.
     * 
     * @ui_formatting User-friendly crop identification for harvest selection
     * @agricultural_context Tray number, stage, and planting date display
     * 
     * @param Crop $crop The crop instance to format for display
     * @return string Formatted option: "Tray {number} - {stage} (Planted: {date})"
     * 
     * @format_components:
     *   - Tray number for physical identification
     *   - Current stage name for production status
     *   - Planting date for harvest timing context
     * 
     * @fallback_handling Uses 'Unknown' for missing stage, 'Not planted' for missing date
     * @date_formatting Month and day format (M j) for concise display
     */
    protected function formatCropOption(Crop $crop): string
    {
        $stageName = $crop->currentStage->name ?? 'Unknown';
        $plantedDate = $crop->planting_at ? $crop->planting_at->format('M j') : 'Not planted';
        
        return "Tray {$crop->tray_number} - {$stageName} (Planted: {$plantedDate})";
    }

    /**
     * Validate if individual crop is available for harvest operations.
     * 
     * Performs business rule validation to determine crop harvest eligibility
     * based on current production stage. Ensures crops are not in terminal
     * states (harvested/cancelled) and have valid stage assignments.
     * 
     * @business_validation Crop harvest eligibility based on production stage
     * @agricultural_context Microgreens production stage harvest readiness
     * 
     * @param Crop $crop The crop instance to validate for harvest availability
     * @return bool True if crop is available for harvest, false otherwise
     * 
     * @validation_rules:
     *   - Must have current stage assigned
     *   - Stage cannot be 'harvested' or 'cancelled'
     * 
     * @relationship_loading Auto-loads currentStage if not already loaded
     * @stage_exclusion Prevents harvest operations on completed crops
     * 
     * @usage Called for individual crop validation in harvest workflows
     * @performance Efficient with conditional relationship loading
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
     * Retrieve crops by cultivar with configurable stage exclusion filtering.
     * 
     * Provides flexible crop querying for specific cultivar with customizable
     * stage filtering. Returns Collection for advanced processing while maintaining
     * efficient relationship loading and comprehensive filtering capabilities.
     * 
     * @business_query Flexible cultivar-based crop retrieval with stage filtering
     * @agricultural_context Variety-specific crop collection with production stage control
     * 
     * @param int $cultivarId Master cultivar ID for variety filtering
     * @param array $excludeStages Stage codes to exclude from results (default: harvested, cancelled)
     * @return Collection Crops matching cultivar and stage criteria with loaded relationships
     * 
     * @query_optimization Eager loads recipe.masterSeedCatalog and currentStage
     * @relationship_validation Uses nested whereHas for cultivar association
     * @flexible_filtering Customizable stage exclusion for different use cases
     * 
     * @default_exclusion Excludes harvested and cancelled by default
     * @collection_return Provides Collection for advanced manipulation and processing
     * 
     * @usage Called for bulk crop operations and advanced harvest workflow queries
     * @performance Optimized relationship loading with configurable filtering
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