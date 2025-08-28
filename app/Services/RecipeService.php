<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Consumable;
use App\Models\ConsumableType;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural recipe management and calculation service.
 * 
 * Manages comprehensive recipe operations for agricultural microgreens production
 * including recipe naming, inventory validation, growth calculations, and
 * lot tracking. Integrates with inventory management to ensure accurate
 * seed availability and automates recipe lifecycle management.
 *
 * @business_domain Agricultural recipe management and production planning
 * @related_models Recipe, Consumable, ConsumableType
 * @related_services InventoryManagementService
 * @used_by Recipe resources, crop planning, production scheduling
 * @agricultural_context Specialized for microgreens production recipes and calculations
 */
class RecipeService
{
    /**
     * Inventory management service for seed and consumable tracking.
     */
    protected InventoryManagementService $inventoryService;
    
    /**
     * Initialize recipe service with inventory management integration.
     * 
     * Establishes connection to inventory service for seed availability
     * validation and lot tracking in recipe management.
     *
     * @param InventoryManagementService $inventoryService Service for inventory operations
     */
    public function __construct(InventoryManagementService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    /**
     * Generate standardized agricultural recipe name from production parameters.
     * 
     * Creates comprehensive recipe names following agricultural naming conventions:
     * Format: Variety (Cultivar) - Seed Density - DTM - LOT NO
     * Essential for recipe organization and production tracking.
     *
     * @param Recipe $recipe Recipe instance to generate name for
     * @return void Updates recipe name, common_name, and cultivar_name fields
     * @agricultural_context Uses agricultural variety and cultivar naming standards
     * @naming_format Standardized format for production recipe identification
     */
    public function generateRecipeName(Recipe $recipe): void
    {
        // Extract variety and cultivar from the consumable based on lot_number
        if (!$recipe->lot_number) {
            return;
        }

        $seedTypeId = ConsumableType::where('code', 'seed')->first()?->id;
        $consumable = Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', $recipe->lot_number)
            ->where('is_active', true)
            ->first();
        
        if (!$consumable || !$consumable->name) {
            return;
        }

        // Parse consumable name format: "Variety (Cultivar)"
        if (!preg_match('/^(.+?)\s*\((.+?)\)$/', $consumable->name, $matches)) {
            return;
        }

        $variety = trim($matches[1]);
        $cultivar = trim($matches[2]);
        
        $nameParts = [];
        
        // Add variety (cultivar) part
        $nameParts[] = $variety . ' (' . $cultivar . ')';
        
        // Add seed density if available
        if ($recipe->seed_density_grams_per_tray) {
            $nameParts[] = $recipe->seed_density_grams_per_tray . 'G';
        }
        
        // Add DTM if available
        if ($recipe->days_to_maturity) {
            $nameParts[] = $recipe->days_to_maturity . ' DTM';
        }
        
        // Add lot number
        $nameParts[] = $recipe->lot_number;
        
        $baseName = implode(' - ', $nameParts);
        $recipe->name = $this->ensureUniqueRecipeName($recipe, $baseName);
        
        // Also populate the common_name and cultivar_name fields for consistency
        $recipe->common_name = $variety;
        $recipe->cultivar_name = $cultivar;
    }

    /**
     * Ensure agricultural recipe name uniqueness with automated versioning.
     * 
     * Prevents duplicate recipe names in agricultural production system
     * by appending version numbers when necessary. Essential for
     * maintaining clear recipe identification in production workflows.
     *
     * @param Recipe $recipe Recipe instance being named
     * @param string $baseName Base recipe name to make unique
     * @return string Unique recipe name with version number if needed
     * @agricultural_context Ensures clear recipe identification in production
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
     * Calculate total agricultural production cycle duration.
     * 
     * Computes complete growing cycle from planting to harvest for
     * agricultural production planning. Uses either explicit days-to-maturity
     * or sum of individual stage durations for accurate scheduling.
     *
     * @param Recipe $recipe Recipe with growth stage durations
     * @return float Total days from planting to harvest
     * @agricultural_context Essential for crop scheduling and harvest planning
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
     * Calculate effective agricultural production cycle including seed preparation.
     * 
     * Computes complete production timeline including seed soaking preparation
     * time for accurate agricultural scheduling and resource planning.
     *
     * @param Recipe $recipe Recipe with soak time and growth durations
     * @return float Total effective days including seed preparation
     * @agricultural_context Includes pre-planting seed preparation in scheduling
     */
    public function calculateEffectiveTotalDays(Recipe $recipe): float
    {
        $soakDays = ($recipe->seed_soak_hours ?? 0) / 24;
        return $soakDays + $this->calculateTotalDays($recipe);
    }

    /**
     * Mark agricultural seed lot as depleted to prevent production errors.
     * 
     * Records when a recipe's seed lot is exhausted to prevent scheduling
     * crops with unavailable seeds. Essential for accurate agricultural
     * production planning and inventory management.
     *
     * @param Recipe $recipe Recipe with depleted seed lot
     * @return void Updates recipe with depletion timestamp
     * @agricultural_context Prevents production scheduling with unavailable seeds
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
     * Validate agricultural recipe execution feasibility with inventory check.
     * 
     * Verifies recipe can be executed with required seed quantities by
     * checking inventory availability and lot status. Prevents production
     * failures due to insufficient seed supplies.
     *
     * @param Recipe $recipe Recipe to validate for execution
     * @param float $requiredQuantity Required seed quantity in grams
     * @return bool Whether recipe can be executed with required quantity
     * @agricultural_context Prevents production scheduling without sufficient seeds
     * @inventory_integration Real-time seed availability validation
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
        // Auto-generate name if needed
        if ($recipe->isDirty(['lot_number', 'seed_density_grams_per_tray', 'days_to_maturity'])) {
            $this->generateRecipeName($recipe);
        }
    }

    /**
     * Validate agricultural recipe parameters for production feasibility.
     * 
     * Performs comprehensive validation of recipe parameters including
     * growth stages, seed density, yield expectations, and buffer percentages
     * to ensure agricultural production viability.
     *
     * @param Recipe $recipe Recipe to validate
     * @return array Array of validation errors (empty if valid)
     * @agricultural_context Validates parameters for realistic agricultural production
     * @validation_rules Ensures positive durations, realistic densities, and valid percentages
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