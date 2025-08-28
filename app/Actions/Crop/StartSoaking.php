<?php

namespace App\Actions\Crop;

use InvalidArgumentException;
use RuntimeException;
use App\Models\CropBatch;
use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use Carbon\Carbon;

/**
 * Initiates soaking process for seed varieties requiring pre-germination soaking.
 * 
 * Manages the specialized soaking phase for microgreens varieties that require
 * controlled water exposure before germination. Creates crop batch organization,
 * initializes soaking timeline tracking, and establishes temporary tray assignments
 * for facility workflow management during the soaking period.
 * 
 * @business_domain Agricultural Microgreens Soaking Phase Management
 * @crop_preparation Specialized soaking workflow for varieties requiring water pre-treatment
 * @production_timing Controlled soaking duration before germination advancement
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class StartSoaking
{
    /**
     * Initialize soaking process for seed varieties requiring pre-germination treatment.
     * 
     * Creates crop production records in soaking stage for agricultural varieties
     * that require controlled water exposure before active growing phase. Establishes
     * crop batch organization, sets soaking timeline tracking, and assigns temporary
     * facility tray identifiers for workflow management during soaking duration.
     * 
     * @business_process Soaking Phase Initialization Workflow
     * @agricultural_context Varieties requiring water pre-treatment (sunflower, pea, etc.)
     * @production_organization Multi-tray batch creation with temporary identifiers
     * 
     * @param array $data Soaking initialization data including:
     *   - recipe_id: Agricultural recipe requiring soaking process
     *   - order_id: Optional customer order for fulfillment tracking
     *   - crop_plan_id: Optional production planning reference
     *   - tray_count: Number of production trays for this batch
     *   - notes: Optional production notes for soaking process
     * @return Crop Primary crop instance (first in batch for compatibility)
     * 
     * @throws InvalidArgumentException If recipe does not require soaking
     * @throws RuntimeException If soaking stage configuration missing
     * 
     * @recipe_validation Confirms recipe.requiresSoaking() before processing
     * @batch_creation Establishes CropBatch for organized production tracking
     * @tray_assignment Creates temporary 'SOAKING-{n}' identifiers during soaking
     * @timeline_initialization Sets soaking_at timestamp for duration tracking
     * 
     * @soaking_varieties Examples: sunflower microgreens, pea shoots, wheatgrass
     * @duration_control Soaking time managed by recipe specifications
     * @advancement_ready Crops advance to germination via AdvanceFromSoaking action
     * 
     * @usage Called from CreateCrop action for soaking-required varieties
     * @database_impact Creates CropBatch and multiple Crop records in soaking stage
     */
    public function execute(array $data): Crop
    {
        $recipe = Recipe::findOrFail($data['recipe_id']);

        // Check if recipe requires soaking
        if (!$recipe->requiresSoaking()) {
            throw new InvalidArgumentException('This recipe does not require soaking.');
        }

        // Get the soaking stage
        $soakingStage = CropStage::findByCode('soaking');
        if (!$soakingStage) {
            throw new RuntimeException('Soaking stage not found in database.');
        }

        // Create a crop batch first
        $cropBatch = CropBatch::create([
            'recipe_id' => $recipe->id,
            'order_id' => $data['order_id'] ?? null,
            'crop_plan_id' => $data['crop_plan_id'] ?? null,
        ]);

        // Create multiple crops based on tray_count, each with temp tray numbers
        $trayCount = $data['tray_count'] ?? 1;
        $soakingTime = Carbon::now();
        $crops = [];

        for ($i = 1; $i <= $trayCount; $i++) {
            $crops[] = Crop::create([
                'crop_batch_id' => $cropBatch->id,
                'recipe_id' => $recipe->id,
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
                'tray_number' => 'SOAKING-' . $i, // Dynamic temp tray numbers
                'tray_count' => 1, // Each crop represents one tray
                'current_stage_id' => $soakingStage->id,
                'requires_soaking' => true,
                'soaking_at' => $soakingTime,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return $crops[0]; // Return first crop for compatibility
    }
}