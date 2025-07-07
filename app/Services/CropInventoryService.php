<?php

namespace App\Services;

/**
 * @deprecated Use InventoryManagementService instead. This class will be removed in a future version.
 */

use App\Models\Crop;
use App\Models\Consumable;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing crop-related inventory operations.
 * 
 * This service handles seed inventory deductions when crops are created,
 * ensuring proper tracking of seed consumption based on recipe requirements.
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
     * Deduct seed inventory for a newly created crop.
     * 
     * This method handles the automatic deduction of seed inventory when a crop
     * is created, using the FIFO system to consume from the oldest stock first.
     * 
     * @param Crop $crop The crop that was created
     * @return bool True if deduction was successful, false otherwise
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
     * Deduct seed from a specific lot using FIFO.
     * 
     * @param Crop $crop The crop being created
     * @param float $requiredAmount The amount of seed required in grams
     * @return bool True if successful, false otherwise
     */
    private function deductSeedFromLot(Crop $crop, float $requiredAmount): bool
    {
        $lotNumber = $crop->recipe->lot_number;
        
        // Check if lot has sufficient quantity
        $availableQuantity = $this->lotInventoryService->getLotQuantity($lotNumber);
        
        // Convert to consistent units for comparison
        $requiredInKg = $requiredAmount / 1000; // Convert grams to kg
        
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

        // Get the oldest consumable entry for FIFO deduction
        $consumable = $this->lotInventoryService->getOldestEntryInLot($lotNumber);
        
        if (!$consumable) {
            Log::error('No consumable entry found for lot', [
                'crop_id' => $crop->id,
                'lot_number' => $lotNumber,
            ]);
            return false;
        }

        // Deduct the seed amount
        try {
            $consumable->deduct($requiredAmount, 'g'); // Recipe seed density is always in grams
            
            Log::info('Seed automatically deducted for new crop (lot-based)', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'lot_number' => $lotNumber,
                'consumable_id' => $consumable->id,
                'amount_deducted' => $requiredAmount,
                'unit' => 'g',
                'remaining_in_lot' => $this->lotInventoryService->getLotQuantity($lotNumber),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error deducting seed from lot', [
                'crop_id' => $crop->id,
                'lot_number' => $lotNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Deduct seed from legacy consumable-based system.
     * 
     * @deprecated Use lot-based deduction instead
     * @param Crop $crop The crop being created
     * @param float $requiredAmount The amount of seed required in grams
     * @return bool True if successful, false otherwise
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
        
        // Convert required amount to the same unit as the seed consumable for comparison
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
            // Deduct the seed amount specified in the recipe for this tray
            $seedConsumable->deduct($requiredAmount, 'g'); // Recipe seed density is always in grams
            
            Log::info('Seed automatically deducted for new crop (legacy)', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'seed_consumable_id' => $seedConsumable->id,
                'amount_deducted' => $requiredAmount,
                'unit' => 'g',
                'remaining_stock' => $currentStock - $requiredInSeedUnits,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error deducting seed inventory for new crop', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Convert grams to the consumable's unit of measurement.
     * 
     * @param float $amountInGrams The amount in grams
     * @param Consumable $consumable The consumable with its unit
     * @return float The amount in the consumable's unit
     */
    private function convertToConsumableUnits(float $amountInGrams, Consumable $consumable): float
    {
        return match($consumable->quantity_unit) {
            'kg' => $amountInGrams / 1000, // Convert grams to kg
            'g' => $amountInGrams, // Already in grams
            default => $amountInGrams // For other units, assume direct comparison
        };
    }

    /**
     * Check if a crop can be created based on seed availability.
     * 
     * @param int $recipeId The recipe ID
     * @param int $trayCount The number of trays to create
     * @return array ['can_create' => bool, 'message' => string, 'required' => float, 'available' => float]
     */
    public function checkSeedAvailability(int $recipeId, int $trayCount = 1): array
    {
        $recipe = \App\Models\Recipe::find($recipeId);
        
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

        $requiredAmount = $recipe->seed_density_grams_per_tray * $trayCount;
        $requiredInKg = $requiredAmount / 1000;

        // Check lot-based availability
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

        // Fallback to consumable-based check
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