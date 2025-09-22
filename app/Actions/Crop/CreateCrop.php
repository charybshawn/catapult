<?php

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use App\Actions\Crops\RecordStageHistory;
use Carbon\Carbon;

class CreateCrop
{
    protected StartSoaking $startSoaking;
    protected RecordStageHistory $recordStageHistory;
    
    public function __construct(StartSoaking $startSoaking, RecordStageHistory $recordStageHistory)
    {
        $this->startSoaking = $startSoaking;
        $this->recordStageHistory = $recordStageHistory;
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
        
        $plantingTime = isset($data['germination_at']) ? Carbon::parse($data['germination_at']) : Carbon::now();
        
        // Create a crop batch first
        $cropBatch = \App\Models\CropBatch::create([
            'recipe_id' => $recipe->id,
            'order_id' => $data['order_id'] ?? null,
            'crop_plan_id' => $data['crop_plan_id'] ?? null,
        ]);
        
        // Handle tray numbers - for non-soaking recipes, create multiple crops if multiple tray numbers
        $trayNumbers = $data['tray_numbers'] ?? [];
        if (empty($trayNumbers)) {
            $trayNumbers = ['UNASSIGNED-' . time()]; // Single crop with temporary tray number
        }
        
        $crops = [];
        foreach ($trayNumbers as $trayNumber) {
            $crops[] = Crop::create([
                'crop_batch_id' => $cropBatch->id,
                'recipe_id' => $recipe->id,
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
                'tray_number' => $trayNumber,
                'current_stage_id' => $germinationStage->id,
                'requires_soaking' => false,
                'germination_at' => $plantingTime,
                'notes' => $data['notes'] ?? null,
            ]);
        }
        
        // Create stage history records for all crops
        foreach ($crops as $crop) {
            $this->recordStageHistory->execute($crop, $germinationStage, $plantingTime);
        }
        
        $crop = $crops[0]; // Return first crop for compatibility
        
        return $crop;
    }
}