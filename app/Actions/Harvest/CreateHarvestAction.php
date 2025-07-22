<?php

namespace App\Actions\Harvest;

use App\Models\Harvest;
use App\Models\Crop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Business logic for creating harvest records
 * Handles the complex transaction logic for harvest creation
 */
class CreateHarvestAction
{
    public function __construct(
        protected ValidateHarvestAction $validator
    ) {}

    /**
     * Create a new harvest record with associated crop data
     *
     * @param array $data
     * @return Harvest
     * @throws \Exception
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
     * Create the main harvest record
     *
     * @param array $data
     * @return Harvest
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
     * Process and attach crop data to the harvest
     *
     * @param Harvest $harvest
     * @param array $cropsData
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
     * Calculate and update harvest totals
     *
     * @param Harvest $harvest
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
     * Update crop stages to harvested
     *
     * @param array $cropsData
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
     * Update an existing harvest
     *
     * @param Harvest $harvest
     * @param array $data
     * @return Harvest
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