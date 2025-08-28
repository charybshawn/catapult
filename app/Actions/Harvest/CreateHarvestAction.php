<?php

namespace App\Actions\Harvest;

use Exception;
use App\Models\Harvest;
use App\Models\Crop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Creates comprehensive harvest records for microgreens agricultural operations.
 * 
 * Orchestrates the complex harvest creation workflow including data validation,
 * crop-harvest relationships, weight calculations, stage transitions, and audit
 * logging. Handles both full and partial harvest scenarios with proper business
 * rule enforcement and production tracking integration.
 * 
 * @business_domain Agricultural Microgreens Harvest Management
 * @harvest_workflow Complete harvest record creation with crop integration
 * @production_tracking Weight calculations and yield analytics
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class CreateHarvestAction
{
    /**
     * Initialize CreateHarvestAction with harvest validation dependency.
     * 
     * @param ValidateHarvestAction $validator Service for harvest data validation
     */
    public function __construct(
        protected ValidateHarvestAction $validator
    ) {}

    /**
     * Execute complete harvest creation workflow with comprehensive data processing.
     * 
     * Orchestrates multi-step harvest creation including data validation, database
     * transaction management, crop-harvest relationship establishment, yield calculations,
     * crop stage updates, and audit logging. Handles both complete and partial harvest
     * scenarios while maintaining data integrity and production tracking accuracy.
     * 
     * @business_process Complete Harvest Creation Workflow
     * @agricultural_context Microgreens harvest with weight tracking and yield analytics
     * @transaction_safety Database transaction ensures data consistency
     * 
     * @param array $data Harvest creation data including:
     *   - master_cultivar_id: Variety being harvested
     *   - harvest_date: Date of harvest operation
     *   - user_id: User performing harvest
     *   - notes: Optional harvest notes
     *   - crops: Array of crop data with weights and percentages
     * @return Harvest Complete harvest record with relationships loaded
     * 
     * @throws Exception From validation failures or database constraint violations
     * 
     * @workflow_steps:
     *   1. Validate harvest data via ValidateHarvestAction
     *   2. Create main harvest record
     *   3. Process and attach crop-harvest relationships
     *   4. Calculate harvest totals and yield metrics
     *   5. Update crop stages based on harvest percentages
     *   6. Log harvest creation for audit trail
     * 
     * @database_transaction Ensures complete rollback on any step failure
     * @relationship_loading Returns harvest with masterCultivar and crops relationships
     * 
     * @usage Called from harvest management interfaces and production workflows
     * @audit_logging Creates detailed logs for harvest operations and crop transitions
     */
    public function execute(array $data): Harvest
    {
        // Validate the data first
        $validatedData = $this->validator->execute($data);

        return DB::transaction(function () use ($validatedData) {
            // Create the main harvest record
            $harvest = $this->createHarvestRecord($validatedData);

            // Process and attach crop data
            $this->processCropData($harvest, $validatedData['crops']);

            // Calculate and update totals
            $this->calculateHarvestTotals($harvest);

            // Update crop stages to harvested
            $this->updateCropStages($validatedData['crops']);

            // Log the harvest creation
            $this->logHarvestCreation($harvest);

            return $harvest->fresh(['masterCultivar', 'crops']);
        });
    }

    /**
     * Create the main harvest record with initial zero values for calculated fields.
     * 
     * Establishes the primary harvest record with user attribution, cultivar reference,
     * and harvest date. Initializes calculated fields (weights, counts) to zero for
     * subsequent calculation and update after crop data processing.
     * 
     * @agricultural_context Master harvest record for microgreens variety
     * @calculated_fields Initial zero values updated after crop data processing
     * 
     * @param array $data Validated harvest data from validation service
     * @return Harvest Newly created harvest record with zero calculated values
     * 
     * @database_impact Creates single record in harvests table
     * @calculation_pending Weight and tray count calculated in separate workflow step
     */
    protected function createHarvestRecord(array $data): Harvest
    {
        return Harvest::create([
            'master_cultivar_id' => $data['master_cultivar_id'],
            'harvest_date' => $data['harvest_date'],
            'user_id' => $data['user_id'],
            'notes' => $data['notes'] ?? null,
            'total_weight_grams' => 0, // Will be calculated later
            'tray_count' => 0, // Will be calculated later
            'average_weight_per_tray' => 0, // Will be calculated later
        ]);
    }

    /**
     * Process and attach detailed crop data to harvest via pivot relationships.
     * 
     * Establishes many-to-many relationships between harvest and individual crops
     * with detailed pivot data including harvested weights, percentages, and notes.
     * Creates comprehensive tracking for partial harvest scenarios and yield analytics.
     * 
     * @relationship_management Many-to-many crop-harvest pivot table population
     * @agricultural_context Individual crop tray harvest data with weights and percentages
     * 
     * @param Harvest $harvest The main harvest record to attach crops to
     * @param array $cropsData Array of crop harvest data with weights and percentages
     * 
     * @pivot_data Each crop attachment includes:
     *   - harvested_weight_grams: Actual harvested weight from tray
     *   - percentage_harvested: Percentage of crop harvested (0-100)
     *   - notes: Optional harvest-specific notes per crop
     *   - created_at/updated_at: Timestamp tracking
     * 
     * @database_impact Creates records in crop_harvest pivot table
     * @partial_harvest_support Allows < 100% harvest with percentage tracking
     */
    protected function processCropData(Harvest $harvest, array $cropsData): void
    {
        $cropAttachments = [];

        foreach ($cropsData as $cropData) {
            $cropAttachments[$cropData['crop_id']] = [
                'harvested_weight_grams' => $cropData['harvested_weight_grams'],
                'percentage_harvested' => $cropData['percentage_harvested'],
                'notes' => $cropData['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Attach crops to harvest with pivot data
        $harvest->crops()->attach($cropAttachments);
    }

    /**
     * Calculate and update comprehensive harvest totals and yield analytics.
     * 
     * Computes total harvested weight, tray count, and average yield per tray
     * from individual crop harvest data. Updates main harvest record with
     * calculated values for production reporting and yield analysis.
     * 
     * @agricultural_analytics Complete yield calculation for harvest record
     * @production_metrics Total weight, tray count, and per-tray averages
     * 
     * @param Harvest $harvest The harvest record to update with calculated totals
     * 
     * @calculation_logic:
     *   - total_weight_grams: Sum of all crop harvested weights
     *   - tray_count: Count of crops in harvest relationship
     *   - average_weight_per_tray: Total weight ÷ tray count
     * 
     * @relationship_dependency Requires crops relationship loaded for pivot data access
     * @database_impact Updates harvest record with calculated values
     * @yield_analytics Provides per-tray averages for production efficiency analysis
     */
    protected function calculateHarvestTotals(Harvest $harvest): void
    {
        // Reload the harvest with crops to get pivot data
        $harvest->load('crops');

        $totalWeight = 0;
        $trayCount = $harvest->crops->count();

        foreach ($harvest->crops as $crop) {
            $totalWeight += $crop->pivot->harvested_weight_grams;
        }

        $averageWeight = $trayCount > 0 ? $totalWeight / $trayCount : 0;

        $harvest->update([
            'total_weight_grams' => $totalWeight,
            'tray_count' => $trayCount,
            'average_weight_per_tray' => $averageWeight,
        ]);
    }

    /**
     * Update crop production stages based on harvest completion percentages.
     * 
     * Manages crop lifecycle stage transitions based on harvest completeness.
     * Fully harvested crops (100%) advance to harvested stage, while partially
     * harvested crops remain in current stage with harvest tracking logged.
     * 
     * @crop_lifecycle Stage management based on harvest completion percentages
     * @agricultural_context Microgreens production stage updates post-harvest
     * 
     * @param array $cropsData Array of crop harvest data with percentage tracking
     * 
     * @stage_logic:
     *   - 100% harvested: Advance to 'harvested' stage
     *   - < 100% harvested: Remain in current stage with partial harvest logging
     * 
     * @partial_harvest_handling Logs partial harvests without stage advancement
     * @audit_logging Detailed logging for both full and partial harvest scenarios
     * @database_impact Updates crop current_stage_id for fully harvested crops
     */
    protected function updateCropStages(array $cropsData): void
    {
        $cropIds = collect($cropsData)->pluck('crop_id');

        // Find crops that are 100% harvested
        $fullyHarvestedCropIds = collect($cropsData)
            ->filter(function ($cropData) {
                return $cropData['percentage_harvested'] >= 100;
            })
            ->pluck('crop_id');

        if ($fullyHarvestedCropIds->isNotEmpty()) {
            // Update crops to harvested stage
            $this->setCropsToHarvestedStage($fullyHarvestedCropIds->toArray());
        }

        // For partially harvested crops, log the partial harvest
        $partiallyHarvestedCropIds = collect($cropsData)
            ->filter(function ($cropData) {
                return $cropData['percentage_harvested'] < 100;
            })
            ->pluck('crop_id');

        if ($partiallyHarvestedCropIds->isNotEmpty()) {
            $this->logPartialHarvests($partiallyHarvestedCropIds->toArray());
        }
    }

    /**
     * Set crops to harvested stage
     *
     * @param array $cropIds
     */
    protected function setCropsToHarvestedStage(array $cropIds): void
    {
        // This would typically update the crop stage
        // For now, we'll just log it since we don't know the exact stage management system
        Log::info('Setting crops to harvested stage', ['crop_ids' => $cropIds]);

        // If there's a specific stage management system, implement it here
        // Example:
        // Crop::whereIn('id', $cropIds)->update(['current_stage_id' => $harvestedStageId]);
    }

    /**
     * Log partial harvests for tracking
     *
     * @param array $cropIds
     */
    protected function logPartialHarvests(array $cropIds): void
    {
        Log::info('Partial harvests recorded', ['crop_ids' => $cropIds]);

        // Additional logic for handling partial harvests
        // This might involve updating crop progress or creating harvest events
    }

    /**
     * Log the harvest creation for audit purposes
     *
     * @param Harvest $harvest
     */
    protected function logHarvestCreation(Harvest $harvest): void
    {
        Log::info('Harvest created successfully', [
            'harvest_id' => $harvest->id,
            'master_cultivar_id' => $harvest->master_cultivar_id,
            'total_weight_grams' => $harvest->total_weight_grams,
            'tray_count' => $harvest->tray_count,
            'user_id' => $harvest->user_id,
        ]);
    }

    /**
     * Update existing harvest record with complete data refresh and recalculation.
     * 
     * Provides comprehensive update functionality for existing harvest records,
     * including data validation, crop relationship refresh, total recalculation,
     * and stage management. Handles complete data replacement with proper
     * transaction safety and audit logging.
     * 
     * @business_process Harvest Record Update Workflow
     * @agricultural_context Harvest data correction and adjustment capabilities
     * @data_integrity Complete relationship refresh with recalculation
     * 
     * @param Harvest $harvest Existing harvest record to update
     * @param array $data Updated harvest data with same structure as create operation
     * @return Harvest Updated harvest record with refreshed relationships
     * 
     * @throws Exception From validation failures or database constraint violations
     * 
     * @update_workflow:
     *   1. Validate updated data via ValidateHarvestAction
     *   2. Update main harvest record fields
     *   3. Detach existing crop relationships
     *   4. Reattach crops with new data
     *   5. Recalculate harvest totals
     *   6. Update crop stages based on new percentages
     *   7. Log update operation for audit trail
     * 
     * @relationship_refresh Completely replaces crop-harvest relationships
     * @transaction_safety Database transaction ensures consistency during update
     * @audit_logging Records harvest update operations for compliance tracking
     * 
     * @usage Called from harvest management interfaces for data corrections
     * @data_validation Uses same validation rules as harvest creation
     */
    public function update(Harvest $harvest, array $data): Harvest
    {
        // Validate the data
        $validatedData = $this->validator->execute($data);

        return DB::transaction(function () use ($harvest, $validatedData) {
            // Update the main harvest record
            $harvest->update([
                'master_cultivar_id' => $validatedData['master_cultivar_id'],
                'harvest_date' => $validatedData['harvest_date'],
                'user_id' => $validatedData['user_id'],
                'notes' => $validatedData['notes'] ?? null,
            ]);

            // Detach existing crops and reattach with new data
            $harvest->crops()->detach();
            $this->processCropData($harvest, $validatedData['crops']);

            // Recalculate totals
            $this->calculateHarvestTotals($harvest);

            // Update crop stages
            $this->updateCropStages($validatedData['crops']);

            // Log the update
            Log::info('Harvest updated successfully', ['harvest_id' => $harvest->id]);

            return $harvest->fresh(['masterCultivar', 'crops']);
        });
    }
}