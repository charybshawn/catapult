<?php

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use Carbon\Carbon;

class CreateCrop
{
    protected StartSoaking $startSoaking;
    
    public function __construct(StartSoaking $startSoaking)
    {
        $this->startSoaking = $startSoaking;
    }
    
    /**
     * Create a new crop, handling both soaking and non-soaking recipes.
     *
     * @param array $data
     * @return Crop
     */
    public function execute(array $data): Crop
    {
        $recipe = Recipe::findOrFail($data['recipe_id']);
        
        // If recipe requires soaking, delegate to StartSoaking action
        if ($recipe->requiresSoaking()) {
            return $this->startSoaking->execute($data);
        }
        
        // Otherwise, create crop in germination stage
        $germinationStage = CropStage::findByCode('germination');
        if (!$germinationStage) {
            throw new \RuntimeException('Germination stage not found in database.');
        }
        
        $now = Carbon::now();
        
        $crop = Crop::create([
            'recipe_id' => $recipe->id,
            'order_id' => $data['order_id'] ?? null,
            'crop_plan_id' => $data['crop_plan_id'] ?? null,
            'tray_number' => $data['tray_number'] ?? null,
            'tray_count' => $data['tray_count'] ?? 1,
            'current_stage_id' => $germinationStage->id,
            'requires_soaking' => false,
            'germination_at' => $now,
            'notes' => $data['notes'] ?? null,
        ]);
        
        return $crop;
    }
}