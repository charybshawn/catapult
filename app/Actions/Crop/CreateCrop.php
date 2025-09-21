<?php

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use App\Actions\Crops\RecordStageHistory;
use Carbon\Carbon;

class CreateCrop
{
    protected RecordStageHistory $recordStageHistory;

    public function __construct(RecordStageHistory $recordStageHistory)
    {
        $this->recordStageHistory = $recordStageHistory;
    }

    /**
     * Create a single crop (for individual crop creation, not batch creation)
     *
     * @param array $data
     * @return Crop
     */
    public function execute(array $data): Crop
    {
        $recipe = Recipe::findOrFail($data['recipe_id']);

        // Determine the appropriate stage
        $stageCode = $data['stage_code'] ?? ($recipe->requiresSoaking() ? 'soaking' : 'germination');
        $stage = CropStage::where('code', $stageCode)->firstOrFail();

        // Set timestamp based on stage
        $timestamp = $this->getTimestampForStage($stageCode, $data);

        // Create the crop
        $crop = Crop::create([
            'crop_batch_id' => $data['crop_batch_id'] ?? null,
            'recipe_id' => $recipe->id,
            'order_id' => $data['order_id'] ?? null,
            'crop_plan_id' => $data['crop_plan_id'] ?? null,
            'tray_number' => $data['tray_number'] ?? 'UNASSIGNED-' . time(),
            'current_stage_id' => $stage->id,
            'requires_soaking' => $recipe->requiresSoaking(),
            'soaking_at' => $stageCode === 'soaking' ? $timestamp : null,
            'germination_at' => $stageCode === 'germination' ? $timestamp : null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Record stage history
        $this->recordStageHistory->execute($crop, $stage, $timestamp);

        return $crop;
    }

    /**
     * Get the appropriate timestamp for a given stage
     */
    protected function getTimestampForStage(string $stageCode, array $data): Carbon
    {
        return match ($stageCode) {
            'soaking' => isset($data['soaking_at']) ? Carbon::parse($data['soaking_at']) : Carbon::now(),
            'germination' => isset($data['germination_at']) ? Carbon::parse($data['germination_at']) : Carbon::now(),
            default => Carbon::now(),
        };
    }
}