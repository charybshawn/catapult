<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\MasterSeedCatalog;
use Illuminate\Support\Facades\Log;

/**
 * Service class for Recipe business logic
 */
class RecipeService
{
    /**
     * @var InventoryManagementService
     */
    protected InventoryManagementService $inventoryService;
    
    /**
     * Create a new service instance.
     */
    public function __construct(InventoryManagementService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    /**
     * Generate recipe name from variety, cultivar, seed density, DTM, and lot number.
     * Format: "Variety (Cultivar) [Lot# XXXX] [Plant Xg] [XDTM, XG, XB, XL]"
     * 
     * @param Recipe $recipe
     * @return void
     */
    

    /**
     * Ensure the recipe name is unique by appending a number if necessary.
     * 
     * @param Recipe $recipe
     * @param string $baseName The base name to make unique
     * @return string The unique name
     */
    public function ensureUniqueRecipeName(Recipe $recipe, string $baseName): string
    {
        $originalName = $baseName;
        $counter = 1;
        
        // Check if this exact name already exists (excluding current record if updating)
        while ($this->nameExists($recipe, $baseName)) {
            $counter++;
            $baseName = $originalName . ' (' . $counter . ')';
        }
        
        return $baseName;
    }

    /**
     * Check if a recipe name already exists in the database.
     * 
     * @param Recipe $recipe
     * @param string $name The name to check
     * @return bool Whether the name exists
     */
    private function nameExists(Recipe $recipe, string $name): bool
    {
        $query = Recipe::where('name', $name);
        
        // If this is an update (record exists), exclude current record from check
        if ($recipe->exists) {
            $query->where('id', '!=', $recipe->id);
        }
        
        return $query->exists();
    }

    /**
     * Calculate the total days from planting to harvest.
     * 
     * @param Recipe $recipe
     * @return float
     */
    public function calculateTotalDays(Recipe $recipe): float
    {
        // If days_to_maturity is set, prefer that value
        if ($recipe->days_to_maturity) {
            return $recipe->days_to_maturity;
        }
        
        // Otherwise use the sum of all stage durations
        return ($recipe->germination_days ?? 0) + 
               ($recipe->blackout_days ?? 0) + 
               ($recipe->light_days ?? 0);
    }

    /**
     * Calculate days to harvest including seed soak time.
     * 
     * @param Recipe $recipe
     * @return float
     */
    public function calculateEffectiveTotalDays(Recipe $recipe): float
    {
        $soakDays = ($recipe->seed_soak_hours ?? 0) / 24;
        return $soakDays + $this->calculateTotalDays($recipe);
    }

    /**
     * Mark the recipe's lot as depleted.
     * 
     * @param Recipe $recipe
     * @return void
     */
    public function markLotDepleted(Recipe $recipe): void
    {
        $recipe->lot_depleted_at = now();
        $recipe->save();

        Log::info('Recipe lot marked as depleted', [
            'recipe_id' => $recipe->id,
            'recipe_name' => $recipe->name,
            'lot_number' => $recipe->lot_number,
        ]);
    }

    /**
     * Check if a recipe can be executed with the required quantity.
     * 
     * @param Recipe $recipe
     * @param float $requiredQuantity
     * @return bool
     */
    public function canExecuteRecipe(Recipe $recipe, float $requiredQuantity): bool
    {
        if (!$recipe->lot_number) {
            return false;
        }

        if ($recipe->lot_depleted_at) {
            return false;
        }

        if ($this->inventoryService->isLotDepleted($recipe->lot_number)) {
            // Auto-mark as depleted if inventory shows it's depleted
            $this->markLotDepleted($recipe);
            return false;
        }

        return $this->inventoryService->getLotQuantity($recipe->lot_number) >= $requiredQuantity;
    }

    /**
     * Update recipe fields that depend on other fields.
     * 
     * @param Recipe $recipe
     * @return void
     */
    public function updateDependentFields(Recipe $recipe): void
    {
        // Name generation is now handled in the form layer
        // This method is kept for any future field dependencies
    }

    /**
     * Validate recipe data before saving.
     * 
     * @param Recipe $recipe
     * @return array Array of validation errors, empty if valid
     */
    public function validateRecipe(Recipe $recipe): array
    {
        $errors = [];

        // Validate stage durations
        if ($recipe->germination_days < 0) {
            $errors[] = 'Germination days cannot be negative';
        }

        if ($recipe->blackout_days < 0) {
            $errors[] = 'Blackout days cannot be negative';
        }

        if ($recipe->light_days < 0) {
            $errors[] = 'Light days cannot be negative';
        }

        // Validate seed density
        if ($recipe->seed_density_grams_per_tray !== null && $recipe->seed_density_grams_per_tray <= 0) {
            $errors[] = 'Seed density must be greater than zero';
        }

        // Validate expected yield
        if ($recipe->expected_yield_grams !== null && $recipe->expected_yield_grams <= 0) {
            $errors[] = 'Expected yield must be greater than zero';
        }

        // Validate buffer percentage
        if ($recipe->buffer_percentage !== null && ($recipe->buffer_percentage < 0 || $recipe->buffer_percentage > 100)) {
            $errors[] = 'Buffer percentage must be between 0 and 100';
        }

        return $errors;
    }
}