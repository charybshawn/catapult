<?php

namespace App\Actions\Harvest;

use App\Models\Crop;
use App\Models\MasterCultivar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validates comprehensive harvest data for agricultural operations.
 * 
 * Provides multi-layered validation for microgreens harvest operations including
 * basic data validation, business rule enforcement, crop-cultivar relationship
 * validation, harvest eligibility verification, and data consistency checks.
 * Ensures harvest data integrity before database operations.
 * 
 * @business_domain Agricultural Microgreens Harvest Data Validation
 * @validation_layers Basic validation, business rules, relationship validation, data consistency
 * @harvest_integrity Comprehensive data validation for harvest operations
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class ValidateHarvestAction
{
    /**
     * Execute comprehensive harvest data validation with multi-layer checks.
     * 
     * Performs complete validation workflow including basic field validation,
     * business rule enforcement, and data consistency verification. Ensures
     * harvest data meets all requirements before database operations proceed.
     * 
     * @business_process Complete Harvest Data Validation Workflow
     * @agricultural_context Microgreens harvest data with crop relationships
     * @validation_comprehensive Multi-layer validation with business rule enforcement
     * 
     * @param array $data Raw harvest data for validation
     * @return array Validated and sanitized harvest data
     * 
     * @throws ValidationException For any validation failures with detailed messages
     * 
     * @validation_layers:
     *   1. Basic field validation (required fields, data types, formats)
     *   2. Cultivar-crop relationship validation
     *   3. Crop harvest eligibility verification
     *   4. Weight and percentage data consistency
     * 
     * @business_rules Enforces agricultural production business logic
     * @data_integrity Ensures consistency between weight and percentage data
     * 
     * @usage Called from CreateHarvestAction before database operations
     * @error_handling Provides detailed validation messages for UI display
     */
    public function execute(array $data): array
    {
        $validator = Validator::make($data, $this->getRules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Additional business logic validations
        $this->validateCultivarsMatch($validated);
        $this->validateCropsAreHarvestable($validated);
        $this->validateWeightData($validated);

        return $validated;
    }

    /**
     * Define comprehensive validation rules for harvest data structure.
     * 
     * Establishes complete validation rule set for all harvest data components
     * including main harvest fields and nested crop data arrays. Ensures
     * proper data types, required fields, and business constraints.
     * 
     * @validation_structure Complete rule set for harvest and crop data
     * @agricultural_context Rules specific to microgreens harvest operations
     * 
     * @return array Complete validation rule array for Laravel validator
     * 
     * @rule_categories:
     *   - Main harvest: cultivar, date, user, notes
     *   - Crop array: required crops with weight and percentage data
     *   - Individual crops: ID, weight, percentage, and optional notes
     * 
     * @constraint_enforcement Database existence checks and business limits
     * @data_integrity Weight minimums, percentage limits, field length restrictions
     */
    protected function getRules(): array
    {
        return [
            'master_cultivar_id' => ['required', 'integer', 'exists:master_cultivars,id'],
            'harvest_date' => ['required', 'date', 'before_or_equal:today'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'crops' => ['required', 'array', 'min:1'],
            'crops.*.crop_id' => ['required', 'integer', 'exists:crops,id'],
            'crops.*.harvested_weight_grams' => ['required', 'numeric', 'min:0'],
            'crops.*.percentage_harvested' => ['required', 'numeric', 'min:0', 'max:100'],
            'crops.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Validate crop-cultivar relationship consistency for harvest integrity.
     * 
     * Ensures all selected crops belong to the specified master cultivar through
     * recipe and seed catalog relationships. Prevents harvesting crops of different
     * varieties under single harvest record, maintaining data integrity and
     * agricultural production accuracy.
     * 
     * @relationship_validation Crop-cultivar association through recipe linkage
     * @agricultural_context Variety consistency in microgreens harvest operations
     * @data_integrity Prevents mixed-variety harvest records
     * 
     * @param array $data Validated harvest data with cultivar and crop selections
     * @throws ValidationException If any crops don't belong to specified cultivar
     * 
     * @validation_logic Uses nested whereHas queries to verify:
     *   Crop -> Recipe -> MasterSeedCatalog -> MasterCultivar relationship chain
     * 
     * @business_rule All crops in single harvest must be same variety/cultivar
     * @error_handling Provides clear error message for relationship violations
     */
    protected function validateCultivarsMatch(array $data): void
    {
        $masterCultivarId = $data['master_cultivar_id'];
        $cropIds = collect($data['crops'])->pluck('crop_id');

        $invalidCrops = Crop::whereIn('id', $cropIds)
            ->whereDoesntHave('recipe', function ($query) use ($masterCultivarId) {
                $query->whereHas('masterSeedCatalog', function ($q) use ($masterCultivarId) {
                    $q->whereHas('masterCultivars', function ($mc) use ($masterCultivarId) {
                        $mc->where('id', $masterCultivarId);
                    });
                });
            })
            ->exists();

        if ($invalidCrops) {
            throw ValidationException::withMessages([
                'crops' => 'Some selected crops do not belong to the chosen variety.'
            ]);
        }
    }

    /**
     * Validate crop production stage eligibility for harvest operations.
     * 
     * Ensures selected crops are in appropriate production stages for harvest,
     * excluding crops already harvested or cancelled. Prevents duplicate harvest
     * operations and maintains accurate production lifecycle tracking.
     * 
     * @stage_validation Production stage eligibility for harvest operations
     * @agricultural_context Microgreens production lifecycle stage verification
     * @duplicate_prevention Prevents re-harvesting completed crops
     * 
     * @param array $data Validated harvest data with crop selections
     * @throws ValidationException If any crops are in non-harvestable stages
     * 
     * @ineligible_stages 'harvested' and 'cancelled' crops excluded from harvest
     * @error_detail Provides specific tray numbers for non-harvestable crops
     * @business_rule Crops can only be harvested once (full or partial)
     * 
     * @usage Prevents harvest interface from accepting completed crops
     * @data_consistency Maintains accurate production stage tracking
     */
    protected function validateCropsAreHarvestable(array $data): void
    {
        $cropIds = collect($data['crops'])->pluck('crop_id');

        $unharvestableCrops = Crop::with('currentStage')
            ->whereIn('id', $cropIds)
            ->whereHas('currentStage', function ($query) {
                $query->whereIn('code', ['harvested', 'cancelled']);
            })
            ->pluck('tray_number')
            ->toArray();

        if (!empty($unharvestableCrops)) {
            throw ValidationException::withMessages([
                'crops' => 'The following trays cannot be harvested: ' . implode(', ', $unharvestableCrops)
            ]);
        }
    }

    /**
     * Validate weight and percentage data consistency for harvest accuracy.
     * 
     * Ensures logical consistency between harvested weight and percentage values
     * for each crop in harvest operation. Prevents data inconsistencies that would
     * affect yield calculations and production analytics.
     * 
     * @data_consistency Weight and percentage logical relationship validation
     * @agricultural_context Harvest yield data accuracy for production metrics
     * @analytics_integrity Ensures reliable data for yield calculations
     * 
     * @param array $data Validated harvest data with crop weight and percentage data
     * @throws ValidationException If weight-percentage relationships are inconsistent
     * 
     * @consistency_rules:
     *   - Weight > 0 required when percentage > 0
     *   - Percentage > 0 required when weight > 0
     *   - Both zero values acceptable for no-harvest scenarios
     * 
     * @error_detail Provides field-specific error messages for UI display
     * @yield_accuracy Maintains accurate harvest yield data for production analysis
     */
    protected function validateWeightData(array $data): void
    {
        $errors = [];

        foreach ($data['crops'] as $index => $cropData) {
            $weight = $cropData['harvested_weight_grams'];
            $percentage = $cropData['percentage_harvested'];

            // Basic validation - weight should be positive if percentage > 0
            if ($percentage > 0 && $weight <= 0) {
                $errors["crops.{$index}.harvested_weight_grams"] = 'Weight must be greater than 0 when percentage harvested is greater than 0.';
            }

            // Weight should be 0 if percentage is 0
            if ($percentage == 0 && $weight > 0) {
                $errors["crops.{$index}.percentage_harvested"] = 'Percentage harvested should be greater than 0 when weight is provided.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate that the master cultivar is active and available
     *
     * @param int $masterCultivarId
     * @throws ValidationException
     */
    public function validateCultivarIsActive(int $masterCultivarId): void
    {
        $cultivar = MasterCultivar::with('masterSeedCatalog')->find($masterCultivarId);

        if (!$cultivar || !$cultivar->is_active) {
            throw ValidationException::withMessages([
                'master_cultivar_id' => 'Selected variety is not active or available.'
            ]);
        }

        if (!$cultivar->masterSeedCatalog || !$cultivar->masterSeedCatalog->is_active) {
            throw ValidationException::withMessages([
                'master_cultivar_id' => 'Selected variety\'s seed catalog is not active.'
            ]);
        }
    }

    /**
     * Check if crops have already been harvested in this harvest
     *
     * @param array $cropIds
     * @param int|null $excludeHarvestId
     * @return array
     */
    public function checkForDuplicateHarvests(array $cropIds, ?int $excludeHarvestId = null): array
    {
        $query = Crop::whereIn('id', $cropIds)
            ->whereHas('harvests', function ($query) use ($excludeHarvestId) {
                if ($excludeHarvestId) {
                    $query->where('id', '!=', $excludeHarvestId);
                }
            });

        return $query->pluck('tray_number', 'id')->toArray();
    }
}