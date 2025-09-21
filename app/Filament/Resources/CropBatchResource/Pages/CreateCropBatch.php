<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Filament\Resources\CropBatchResource;
use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\CropStage;
use App\Models\Recipe;
use App\Actions\Crops\RecordStageHistory;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateCropBatch extends CreateRecord
{
    protected static string $resource = CropBatchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Create the crop batch first
            $cropBatch = CropBatch::create([
                'recipe_id' => $data['recipe_id'],
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
            ]);

            $recipe = Recipe::findOrFail($data['recipe_id']);

            // Handle soaking vs non-soaking recipes
            if ($recipe->requiresSoaking()) {
                $this->createSoakingCrops($cropBatch, $data, $recipe);
            } else {
                $this->createGerminationCrops($cropBatch, $data);
            }

            return $cropBatch;
        });
    }

    /**
     * Create crops for soaking recipes
     */
    protected function createSoakingCrops(CropBatch $cropBatch, array $data, Recipe $recipe): void
    {
        $soakingStage = CropStage::where('code', 'soaking')->firstOrFail();
        $trayCount = $data['soaking_tray_count'] ?? 1;
        $soakingTime = isset($data['soaking_at']) ? Carbon::parse($data['soaking_at']) : Carbon::now();

        for ($i = 1; $i <= $trayCount; $i++) {
            $crop = Crop::create([
                'crop_batch_id' => $cropBatch->id,
                'recipe_id' => $recipe->id,
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
                'tray_number' => 'SOAKING-' . $i,
                'current_stage_id' => $soakingStage->id,
                'requires_soaking' => true,
                'soaking_at' => $soakingTime,
                'notes' => $data['notes'] ?? null,
            ]);

            // Record stage history
            app(RecordStageHistory::class)->execute($crop, $soakingStage, $soakingTime);
        }
    }

    /**
     * Create crops for germination (non-soaking) recipes
     */
    protected function createGerminationCrops(CropBatch $cropBatch, array $data): void
    {
        $germinationStage = CropStage::where('code', 'germination')->firstOrFail();
        $plantingTime = isset($data['germination_at']) ? Carbon::parse($data['germination_at']) : Carbon::now();

        // Transform Livewire serialized tray_numbers data
        $trayNumbers = $this->transformTrayNumbers($data['tray_numbers'] ?? []);

        // Ensure we have at least one tray
        if (empty($trayNumbers)) {
            $trayNumbers = ['UNASSIGNED-' . time()];
        }

        foreach ($trayNumbers as $trayNumber) {
            $crop = Crop::create([
                'crop_batch_id' => $cropBatch->id,
                'recipe_id' => $data['recipe_id'],
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
                'tray_number' => trim($trayNumber),
                'current_stage_id' => $germinationStage->id,
                'requires_soaking' => false,
                'germination_at' => $plantingTime,
                'notes' => $data['notes'] ?? null,
            ]);

            // Record stage history
            app(RecordStageHistory::class)->execute($crop, $germinationStage, $plantingTime);
        }
    }

    /**
     * Transform Livewire serialized tray numbers data into a simple array
     */
    protected function transformTrayNumbers($trayNumbers): array
    {
        // Handle null or empty
        if (empty($trayNumbers)) {
            return [];
        }

        // If it's already a simple array of strings, return as-is
        if (is_array($trayNumbers) && !empty($trayNumbers) && is_string($trayNumbers[0] ?? null)) {
            return array_filter(array_map('trim', $trayNumbers));
        }

        // Handle Livewire's complex serialized format
        $result = [];

        if (is_array($trayNumbers)) {
            foreach ($trayNumbers as $item) {
                if (is_string($item)) {
                    $trimmed = trim($item);
                    if (!empty($trimmed)) {
                        $result[] = $trimmed;
                    }
                } elseif (is_array($item)) {
                    // Recursively handle nested arrays
                    $nested = $this->transformTrayNumbers($item);
                    $result = array_merge($result, $nested);
                }
            }
        } elseif (is_string($trayNumbers)) {
            // Handle comma-separated string
            $items = explode(',', $trayNumbers);
            foreach ($items as $item) {
                $trimmed = trim($item);
                if (!empty($trimmed)) {
                    $result[] = $trimmed;
                }
            }
        }

        return array_unique(array_filter($result));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}