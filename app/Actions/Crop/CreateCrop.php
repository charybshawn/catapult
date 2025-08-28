<?php

namespace App\Actions\Crop;

use RuntimeException;
use App\Models\CropBatch;
use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use Carbon\Carbon;

/**
 * Creates new crop production records for microgreens agricultural operations.
 * 
 * Orchestrates the creation of crop production cycles based on agricultural recipes
 * and order requirements. Handles both soaking and direct-germination varieties,
 * manages crop batch organization, and initializes production tracking systems
 * for complete agricultural workflow management.
 * 
 * @business_domain Agricultural Microgreens Crop Production Management
 * @crop_initialization Production cycle creation with recipe-based workflow routing
 * @batch_management Organized crop batch tracking for facility operations
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class CreateCrop
{
    /**
     * Injected StartSoaking action for handling soaking-required crop varieties.
     * 
     * @var StartSoaking
     */
    protected StartSoaking $startSoaking;
    
    /**
     * Initialize CreateCrop action with soaking workflow dependency.
     * 
     * @param StartSoaking $startSoaking Action for managing soaking-required varieties
     */
    public function __construct(StartSoaking $startSoaking)
    {
        $this->startSoaking = $startSoaking;
    }
    
    /**
     * Create new crop production records with recipe-based workflow routing.
     * 
     * Initiates microgreens production cycles by creating appropriate crop records
     * based on agricultural recipe requirements. Routes soaking-required varieties
     * through specialized soaking workflow, while direct-germination varieties
     * begin immediately in growing phase. Establishes crop batch organization
     * for production facility management and order fulfillment tracking.
     * 
     * @business_process Crop Production Initialization Workflow
     * @agricultural_context Recipe-driven production with variety-specific requirements
     * @production_organization Batch-based crop management for facility efficiency
     * 
     * @param array $data Crop creation data including:
     *   - recipe_id: Agricultural recipe defining growth requirements
     *   - order_id: Optional customer order for fulfillment tracking
     *   - crop_plan_id: Optional production planning reference
     *   - tray_numbers: Array of facility tray assignments
     *   - tray_count: Number of production trays (for soaking varieties)
     *   - notes: Optional production notes
     * @return Crop Primary crop instance (first in batch for multi-tray operations)
     * 
     * @throws RuntimeException If germination stage configuration missing
     * 
     * @recipe_routing Delegates to StartSoaking for soaking-required varieties
     * @direct_germination Creates crops directly in germination stage for non-soaking varieties
     * @batch_organization Creates CropBatch record for production management
     * @tray_management Handles single or multiple tray assignments per variety
     * 
     * @workflow_examples:
     *   - Soaking varieties (e.g., sunflower): Routes to StartSoaking action
     *   - Direct varieties (e.g., radish): Creates in germination stage immediately
     * 
     * @usage Called from crop planning interfaces and order fulfillment workflows
     * @database_impact Creates CropBatch and Crop records with production tracking
     */
    public function execute(array $data): Crop
    {
        $recipe = Recipe::findOrFail($data['recipe_id']);
        
        // If recipe requires soaking, delegate to StartSoaking action
        if ($recipe->requiresSoaking()) {
            return $this->startSoaking->execute($data);
        }
        
        // Otherwise, create crop in germination stage
        $germinationStage = CropStage::findByCode('germination');
        if (!$germinationStage) {
            throw new RuntimeException('Germination stage not found in database.');
        }
        
        $now = Carbon::now();
        
        // Create a crop batch first
        $cropBatch = CropBatch::create([
            'recipe_id' => $recipe->id,
            'order_id' => $data['order_id'] ?? null,
            'crop_plan_id' => $data['crop_plan_id'] ?? null,
        ]);
        
        // Handle tray numbers - for non-soaking recipes, create multiple crops if multiple tray numbers
        $trayNumbers = $data['tray_numbers'] ?? [];
        if (empty($trayNumbers)) {
            $trayNumbers = ['UNASSIGNED-' . time()]; // Single crop with temporary tray number
        }
        
        $crops = [];
        foreach ($trayNumbers as $trayNumber) {
            $crops[] = Crop::create([
                'crop_batch_id' => $cropBatch->id,
                'recipe_id' => $recipe->id,
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
                'tray_number' => $trayNumber,
                'tray_count' => 1, // Each tray is a separate crop
                'current_stage_id' => $germinationStage->id,
                'requires_soaking' => false,
                'germination_at' => $now,
                'notes' => $data['notes'] ?? null,
            ]);
        }
        
        $crop = $crops[0]; // Return first crop for compatibility
        
        return $crop;
    }
}