<?php

namespace App\Actions\Harvest;

use App\Models\Crop;
use App\Models\MasterCultivar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for validating harvest data before creation
 */
class ValidateHarvestAction
{
    /**
     * Validate harvest data
     *
     * @param array $data
     * @return array Validated data
     * @throws ValidationException
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
     * Get validation rules for harvest data
     *
     * @return array
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
     * Validate that all selected crops belong to the chosen cultivar
     *
     * @param array $data
     * @throws ValidationException
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
     * Validate that all selected crops are in a harvestable state
     *
     * @param array $data
     * @throws ValidationException
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
     * Validate weight and percentage data consistency
     *
     * @param array $data
     * @throws ValidationException
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