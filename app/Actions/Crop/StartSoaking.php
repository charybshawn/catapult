<?php

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use Carbon\Carbon;

class StartSoaking
{
    /**
     * Start the soaking process for a crop that requires it.
     *
     * @param array $data
     * @return Crop
     */
    public function execute(array $data): Crop
    {
        $recipe = Recipe::findOrFail($data['recipe_id']);
        
        // Check if recipe requires soaking
        if (!$recipe->requiresSoaking()) {
            throw new \InvalidArgumentException('This recipe does not require soaking.');
        }
        
        // Get the soaking stage
        $soakingStage = CropStage::findByCode('soaking');
        if (!$soakingStage) {
            throw new \RuntimeException('Soaking stage not found in database.');
        }
        
        // Create the crop in soaking stage
        $crop = Crop::create([
            'recipe_id' => $recipe->id,
            'order_id' => $data['order_id'] ?? null,
            'crop_plan_id' => $data['crop_plan_id'] ?? null,
            'tray_number' => 'SOAKING-' . time(), // Temporary tray number until assigned
            'tray_count' => $data['tray_count'] ?? 1,
            'current_stage_id' => $soakingStage->id,
            'requires_soaking' => true,
            'soaking_at' => Carbon::now(),
            'notes' => $data['notes'] ?? null,
        ]);
        
        return $crop;
    }
}