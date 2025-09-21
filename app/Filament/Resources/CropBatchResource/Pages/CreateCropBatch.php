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
        $trayNumbers = $data['tray_numbers'] ?? [];

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}