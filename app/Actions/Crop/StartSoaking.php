<?php

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use Carbon\Carbon;
use App\Actions\Crops\RecordStageHistory;

class StartSoaking
{
    protected RecordStageHistory $recordStageHistory;
    
    public function __construct(RecordStageHistory $recordStageHistory)
    {
        $this->recordStageHistory = $recordStageHistory;
    }
    
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
        
        // Create a crop batch first
        $cropBatch = \App\Models\CropBatch::create([
            'recipe_id' => $recipe->id,
            'order_id' => $data['order_id'] ?? null,
            'crop_plan_id' => $data['crop_plan_id'] ?? null,
        ]);
        
        // Create multiple crops based on tray_count, each with temp tray numbers
        $trayCount = $data['tray_count'] ?? 1;
        $soakingTime = Carbon::now();
        $crops = [];
        
        for ($i = 1; $i <= $trayCount; $i++) {
            $crop = Crop::create([
                'crop_batch_id' => $cropBatch->id,
                'recipe_id' => $recipe->id,
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
                'tray_number' => 'SOAKING-' . $i, // Dynamic temp tray numbers
                'tray_count' => 1, // Each crop represents one tray
                'current_stage_id' => $soakingStage->id,
                'requires_soaking' => true,
                'soaking_at' => $soakingTime,
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Record stage history for the initial soaking stage
            $this->recordStageHistory->execute(
                $crop,
                $soakingStage,
                $soakingTime,
                'Crop created and started soaking'
            );
            
            $crops[] = $crop;
        }
        
        return $crops[0]; // Return first crop for compatibility
    }
}