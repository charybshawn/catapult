<?php

namespace App\Actions\Recipe;

use App\Models\Recipe;
use App\Models\Crop;
use App\Models\CropStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Apply recipe parameters to existing grows
 * 
 * Pure business logic extracted from RecipeResource
 */
class UpdateGrowsFromRecipe
{
    public function __construct(
        protected \App\Observers\CropObserver $cropObserver
    ) {}
    
    /**
     * Update existing grows with recipe parameters
     */
    public function execute(Recipe $recipe, array $options): array
    {
        if (!($options['confirm_updates'] ?? false)) {
            throw new \InvalidArgumentException('Confirmation required for updating grows');
        }
        
        return DB::transaction(function () use ($recipe, $options) {
            $crops = $this->getCropsToUpdate($recipe, $options['current_stage']);
            
            if ($crops->isEmpty()) {
                return ['total' => 0, 'updated' => 0];
            }
            
            $totalCrops = $crops->count();
            $updatedCrops = 0;
            
            foreach ($crops as $crop) {
                if ($this->updateCrop($crop, $recipe, $options)) {
                    $updatedCrops++;
                }
            }
            
            return ['total' => $totalCrops, 'updated' => $updatedCrops];
        });
    }
    
    /**
     * Get crops that need updating
     */
    protected function getCropsToUpdate(Recipe $recipe, string $stageFilter): Collection
    {
        $harvestedStage = CropStage::findByCode('harvested');
        $query = Crop::where('recipe_id', $recipe->id)
            ->where('current_stage_id', '!=', $harvestedStage?->id);
        
        if ($stageFilter !== 'all') {
            $stageRecord = CropStage::findByCode($stageFilter);
            if ($stageRecord) {
                $query->where('current_stage_id', $stageRecord->id);
            }
        }
        
        return $query->get();
    }
    
    /**
     * Update a single crop with recipe parameters
     */
    protected function updateCrop(Crop $crop, Recipe $recipe, array $options): bool
    {
        $needsUpdate = false;
        $recalculateHarvestDate = $options['update_expected_harvest_dates'] ?? false;
        
        // Update stage-specific timings
        $needsUpdate |= $this->updateGerminationTiming($crop, $recipe, $options);
        $needsUpdate |= $this->updateBlackoutTiming($crop, $recipe, $options);
        $needsUpdate |= $this->updateLightTiming($crop, $recipe, $options);
        
        // Update harvest date if needed
        if (($options['update_days_to_maturity'] ?? false) || $recalculateHarvestDate) {
            $needsUpdate = true;
            $this->updateHarvestDate($crop, $recipe);
        }
        
        if ($needsUpdate) {
            $crop->save();
        }
        
        return $needsUpdate;
    }
    
    /**
     * Update germination stage timing
     */
    protected function updateGerminationTiming(Crop $crop, Recipe $recipe, array $options): bool
    {
        if (!($options['update_germination_days'] ?? false) || $crop->current_stage !== 'germination') {
            return false;
        }
        
        if ($crop->germination_at) {
            $this->updateStageEndTime($crop, $crop->germination_at, $recipe->germination_days);
        }
        
        return true;
    }
    
    /**
     * Update blackout stage timing
     */
    protected function updateBlackoutTiming(Crop $crop, Recipe $recipe, array $options): bool
    {
        if (!($options['update_blackout_days'] ?? false) || $crop->current_stage !== 'blackout') {
            return false;
        }
        
        if ($crop->blackout_at) {
            $this->updateStageEndTime($crop, $crop->blackout_at, $recipe->blackout_days);
        }
        
        return true;
    }
    
    /**
     * Update light stage timing
     */
    protected function updateLightTiming(Crop $crop, Recipe $recipe, array $options): bool
    {
        if (!($options['update_light_days'] ?? false) || $crop->current_stage !== 'light') {
            return false;
        }
        
        if ($crop->light_at) {
            $this->updateStageEndTime($crop, $crop->light_at, $recipe->light_days);
        }
        
        return true;
    }
    
    /**
     * Update stage end time calculations
     * @deprecated These fields are now calculated in crop_batches_list_view
     */
    protected function updateStageEndTime(Crop $crop, \Carbon\Carbon $stageStart, float $stageDays): void
    {
        // These calculated fields have been moved to crop_batches_list_view
        // and are no longer stored on individual crop records
    }
    
    /**
     * Update expected harvest date
     * @deprecated expected_harvest_at is now calculated in crop_batches_list_view
     */
    protected function updateHarvestDate(Crop $crop, Recipe $recipe): void
    {
        // expected_harvest_at is now calculated in crop_batches_list_view
        // and is no longer stored on individual crop records
    }
    
    /**
     * Get count of affected grows for preview
     */
    public function getAffectedGrowsCount(Recipe $recipe, string $stageFilter): int
    {
        return $this->getCropsToUpdate($recipe, $stageFilter)->count();
    }
}