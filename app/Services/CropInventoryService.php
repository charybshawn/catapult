<?php

namespace App\Services;

/**
 * @deprecated Use InventoryManagementService instead. Scheduled for removal in next major version.
 * @migration_path Functionality moved to InventoryManagementService for unified inventory handling
 * @removal_timeline Phased out in favor of consolidated inventory architecture
 */
use Exception;
use App\Models\Recipe;
use App\Models\Crop;
use App\Models\Consumable;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural crop creation inventory management service.
 * 
 * Manages automatic seed inventory deduction during crop creation, ensuring
 * accurate tracking of seed consumption based on recipe specifications.
 * Implements FIFO (First-In-First-Out) consumption principles and supports
 * both modern lot-based and legacy consumable-based inventory systems.
 * 
 * @deprecated Superseded by unified InventoryManagementService architecture
 * @business_domain Agricultural seed consumption tracking and crop resource management
 * @agricultural_automation Automatic seed deduction during crop creation workflow
 * @inventory_accuracy Ensures precise seed consumption tracking for agricultural operations
 * @fifo_compliance Implements proper seed rotation through oldest-first consumption
 * 
 * @features
 * - Automatic seed deduction on crop creation
 * - FIFO-based lot inventory consumption
 * - Recipe-specific seed density calculations
 * - Dual system support (lot-based and legacy consumable)
 * - Seed availability validation for production planning
 * - Comprehensive audit logging for agricultural traceability
 * 
 * @example
 * $cropInventory = new CropInventoryService($inventoryService, $lotService);
 * $success = $cropInventory->deductSeedForCrop($newCrop);
 * if (!$success) {
 *     // Handle insufficient seed inventory
 * }
 * 
 * @migration_note New implementations should use InventoryManagementService
 * @see InventoryManagementService For unified crop and inventory management
 * @see LotInventoryService For lot-specific inventory operations
 */
class CropInventoryService
{
    private InventoryService $inventoryService;
    private LotInventoryService $lotInventoryService;

    public function __construct(
        InventoryService $inventoryService,
        LotInventoryService $lotInventoryService
    ) {
        $this->inventoryService = $inventoryService;
        $this->lotInventoryService = $lotInventoryService;
    }

    /**
     * Execute automatic seed inventory deduction for newly created agricultural crop.
     * 
     * Automatically deducts seed inventory based on recipe seed density requirements
     * when crops are created, supporting both modern lot-based FIFO inventory
     * and legacy consumable systems. Essential for maintaining accurate seed
     * consumption tracking throughout agricultural production operations.
     * 
     * @automatic_deduction Triggered during crop creation workflow
     * @agricultural_accounting Maintains accurate seed consumption records
     * @fifo_consumption Prioritizes oldest seed stock for quality management
     * @dual_system_support Handles both lot-based and legacy inventory methods
     * 
     * @param Crop $crop Newly created crop requiring seed inventory deduction
     * @return bool True if seed successfully deducted, false if insufficient inventory
     * 
     * @deduction_logic
     * 1. Calculate required seed based on recipe seed density per tray
     * 2. Check if recipe uses lot-based inventory (preferred)
     * 3. If lot-based: Use FIFO deduction from oldest lot entry
     * 4. If legacy: Fall back to consumable-based deduction
     * 5. Log deduction for agricultural audit trail
     * 
     * @example
     * $newCrop = Crop::create($cropData);
     * $deductionSuccess = $this->deductSeedForCrop($newCrop);
     * if (!$deductionSuccess) {
     *     Log::warning('Crop created but seed inventory insufficient');
     *     // May need to flag crop for manual seed allocation
     * }
     */
    public function deductSeedForCrop(Crop $crop): bool
    {
        // Ensure recipe is loaded
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe || !$crop->recipe->seed_density_grams_per_tray) {
            Log::warning('Cannot deduct seed - missing recipe or seed density', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
            ]);
            return false;
        }

        $requiredAmount = $crop->recipe->seed_density_grams_per_tray;

        // Use lot-based inventory if lot_number is specified
        if ($crop->recipe->lot_number) {
            return $this->deductSeedFromLot($crop, $requiredAmount);
        }

        // Fallback to old consumable-based system (deprecated)
        return $this->deductSeedFromConsumable($crop, $requiredAmount);
    }

    /**
     * Execute FIFO seed deduction from agricultural lot inventory.
     * 
     * Implements First-In-First-Out seed consumption from specified lot,
     * ensuring proper seed rotation and quality management. Deducts required
     * seed amount from oldest available lot entry to maintain inventory
     * freshness and agricultural quality standards.
     * 
     * @fifo_implementation Consumes from oldest lot entries first for seed quality
     * @agricultural_quality Ensures fresh seed usage through proper rotation
     * @lot_management Maintains accurate lot-level consumption tracking
     * @internal Core logic for modern lot-based inventory deduction
     * 
     * @param Crop $crop Agricultural crop requiring seed allocation
     * @param float $requiredAmount Seed quantity needed in grams per recipe specification
     * @return bool True if lot deduction successful, false if insufficient inventory
     * 
     * @fifo_process
     * 1. Validate lot has sufficient total inventory
     * 2. Identify oldest available entry in lot
     * 3. Deduct required amount from oldest entry
     * 4. Log deduction with lot and entry details
     * 5. Return success/failure status
     */
    private function deductSeedFromLot(Crop $crop, float $requiredAmount): bool
    {
        $lotNumber = $crop->recipe->lot_number;
        
        // Validate lot has sufficient seed inventory for crop creation
        $availableQuantity = $this->lotInventoryService->getLotQuantity($lotNumber);
        
        // Convert recipe grams to lot inventory units (kg) for accurate comparison
        $requiredInKg = $requiredAmount / 1000; // Recipe density in grams → lot inventory in kg
        
        if ($availableQuantity < $requiredInKg) {
            Log::warning('Insufficient seed stock in lot for crop creation', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'lot_number' => $lotNumber,
                'required_amount' => $requiredAmount,
                'required_in_kg' => $requiredInKg,
                'available_in_lot' => $availableQuantity,
            ]);
            return false;
        }

        // Identify oldest available seed entry for FIFO agricultural deduction
        $consumable = $this->lotInventoryService->getOldestEntryInLot($lotNumber);
        
        if (!$consumable) {
            Log::error('No consumable entry found for lot', [
                'crop_id' => $crop->id,
                'lot_number' => $lotNumber,
            ]);
            return false;
        }

        // Execute FIFO seed deduction from oldest lot entry
        try {
            $consumable->deduct($requiredAmount, 'g'); // Recipe seed density always specified in grams
            
            Log::info('Agricultural seed automatically deducted for new crop (lot-based FIFO)', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'lot_number' => $lotNumber,
                'consumable_id' => $consumable->id,
                'amount_deducted' => $requiredAmount,
                'unit' => 'g',
                'remaining_in_lot' => $this->lotInventoryService->getLotQuantity($lotNumber),
                'fifo_entry' => true, // Indicates proper FIFO consumption
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Error deducting seed from lot', [
                'crop_id' => $crop->id,
                'lot_number' => $lotNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Execute seed deduction using legacy consumable inventory system.
     * 
     * Handles seed deduction for recipes still using pre-lot consumable-based
     * inventory tracking. Maintains backward compatibility while encouraging
     * migration to modern lot-based inventory management for better agricultural
     * traceability and FIFO compliance.
     * 
     * @legacy_support Maintains compatibility with pre-lot inventory system
     * @backward_compatibility Supports existing recipes during migration period
     * @migration_bridge Facilitates transition to lot-based inventory
     * @deprecated Modern recipes should use lot-based seed assignment
     * 
     * @param Crop $crop Agricultural crop requiring seed allocation
     * @param float $requiredAmount Seed quantity needed in grams per recipe
     * @return bool True if consumable deduction successful, false if insufficient stock
     * 
     * @legacy_process
     * 1. Validate recipe has assigned seed consumable
     * 2. Check consumable has sufficient available stock
     * 3. Convert gram requirements to consumable units
     * 4. Deduct required amount from consumable stock
     * 5. Log legacy deduction for audit trail
     */
    private function deductSeedFromConsumable(Crop $crop, float $requiredAmount): bool
    {
        if (!$crop->recipe->seedConsumable) {
            Log::warning('No seed consumable assigned to recipe', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
            ]);
            return false;
        }

        $seedConsumable = $crop->recipe->seedConsumable;
        $currentStock = $this->inventoryService->getCurrentStock($seedConsumable);
        
        // Convert recipe gram requirements to consumable inventory units for accurate comparison
        $requiredInSeedUnits = $this->convertToConsumableUnits($requiredAmount, $seedConsumable);
        
        if ($currentStock < $requiredInSeedUnits) {
            Log::warning('Insufficient seed stock for crop creation', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'seed_consumable_id' => $seedConsumable->id,
                'required_amount' => $requiredAmount,
                'current_stock' => $currentStock,
                'seed_unit' => $seedConsumable->quantity_unit,
            ]);
            return false;
        }

        try {
            // Execute legacy consumable deduction based on recipe seed density
            $seedConsumable->deduct($requiredAmount, 'g'); // Recipe density always in grams
            
            Log::info('Agricultural seed automatically deducted for new crop (legacy consumable)', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'seed_consumable_id' => $seedConsumable->id,
                'amount_deducted' => $requiredAmount,
                'unit' => 'g',
                'remaining_stock' => $currentStock - $requiredInSeedUnits,
                'legacy_system' => true, // Indicates non-FIFO consumable system
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Error deducting seed inventory for new crop', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Convert agricultural seed quantities from grams to consumable inventory units.
     * 
     * Handles unit conversion between recipe seed density (always in grams) and
     * consumable inventory units (may be grams, kilograms, or other units).
     * Essential for accurate inventory deduction in legacy consumable system.
     * 
     * @unit_conversion Standardizes gram-based recipes with varied inventory units
     * @agricultural_standardization Handles recipe density in grams consistently
     * @inventory_compatibility Ensures proper deduction regardless of storage units
     * @internal Utility method for legacy consumable system operations
     * 
     * @param float $amountInGrams Seed quantity in grams from recipe specification
     * @param Consumable $consumable Seed consumable with specific inventory units
     * @return float Equivalent quantity in consumable's native units
     * 
     * @conversion_logic
     * - kg units: Convert grams to kilograms (÷ 1000)
     * - g units: Use grams directly (no conversion)
     * - other units: Assume direct comparison (legacy fallback)
     */
    private function convertToConsumableUnits(float $amountInGrams, Consumable $consumable): float
    {
        return match($consumable->quantity_unit) {
            'kg' => $amountInGrams / 1000,  // Convert recipe grams to inventory kilograms
            'g' => $amountInGrams,          // Recipe grams match inventory grams directly
            default => $amountInGrams       // Legacy: assume unit compatibility
        };
    }

    /**
     * Validate agricultural seed availability for crop creation planning.
     * 
     * Evaluates whether sufficient seed inventory exists to create specified
     * number of crop trays based on recipe seed density requirements. Critical
     * for production planning and preventing crop creation failures due to
     * insufficient seed inventory.
     * 
     * @production_planning Pre-validates seed availability before crop creation
     * @agricultural_feasibility Prevents resource allocation failures
     * @inventory_validation Confirms sufficient seed stock for production demands
     * @capacity_planning Supports multi-tray production scheduling
     * 
     * @param int $recipeId Recipe identifier for seed requirement calculation
     * @param int $trayCount Number of agricultural trays planned for production (default 1)
     * @return array Comprehensive availability analysis with creation feasibility
     * 
     * @availability_response
     * [
     *   'can_create' => bool,    // Whether sufficient seed exists for production
     *   'message' => string,     // Human-readable availability status
     *   'required' => float,     // Total seed quantity needed for planned trays
     *   'available' => float     // Current seed inventory available
     * ]
     * 
     * @validation_process
     * 1. Validate recipe exists and has seed density configured
     * 2. Calculate total seed needed (density × tray count)
     * 3. Check lot-based inventory if recipe uses lots
     * 4. Fall back to consumable inventory for legacy recipes
     * 5. Return feasibility assessment with detailed metrics
     * 
     * @example
     * $availability = $this->checkSeedAvailability($recipeId, 10);
     * if ($availability['can_create']) {
     *     // Proceed with creating 10 trays
     *     $crops = $this->createCropBatch($recipeId, 10);
     * } else {
     *     // Handle insufficient seed scenario
     *     $this->alertInsufficientSeed($availability);
     * }
     */
    public function checkSeedAvailability(int $recipeId, int $trayCount = 1): array
    {
        $recipe = Recipe::find($recipeId);
        
        if (!$recipe) {
            return [
                'can_create' => false,
                'message' => 'Recipe not found',
                'required' => 0,
                'available' => 0,
            ];
        }

        if (!$recipe->seed_density_grams_per_tray) {
            return [
                'can_create' => false,
                'message' => 'Recipe has no seed density configured',
                'required' => 0,
                'available' => 0,
            ];
        }

        // Calculate total seed requirements based on recipe density and tray count
        $requiredAmount = $recipe->seed_density_grams_per_tray * $trayCount;
        $requiredInKg = $requiredAmount / 1000; // Convert to kg for lot inventory comparison

        // Priority check: Modern lot-based seed inventory availability
        if ($recipe->lot_number) {
            $available = $this->lotInventoryService->getLotQuantity($recipe->lot_number);
            
            return [
                'can_create' => $available >= $requiredInKg,
                'message' => $available >= $requiredInKg 
                    ? 'Sufficient seed available' 
                    : 'Insufficient seed in lot',
                'required' => $requiredInKg,
                'available' => $available,
            ];
        }

        // Fallback: Legacy consumable-based seed availability check
        if ($recipe->seedConsumable) {
            $available = $this->inventoryService->getCurrentStock($recipe->seedConsumable);
            $requiredInConsumableUnits = $this->convertToConsumableUnits($requiredAmount, $recipe->seedConsumable);
            
            return [
                'can_create' => $available >= $requiredInConsumableUnits,
                'message' => $available >= $requiredInConsumableUnits 
                    ? 'Sufficient seed available' 
                    : 'Insufficient seed stock',
                'required' => $requiredInConsumableUnits,
                'available' => $available,
            ];
        }

        return [
            'can_create' => false,
            'message' => 'No seed source configured for recipe',
            'required' => $requiredInKg,
            'available' => 0,
        ];
    }
}