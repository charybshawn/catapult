<?php

namespace App\Actions\SeedEntry;

use App\Models\Recipe;
use App\Models\CropStage;
use App\Models\Crop;
use App\Models\Consumable;
use App\Models\SeedEntry;

/**
 * Pure business logic for validating seed entry deletion safety
 */
class ValidateSeedEntryDeletionAction
{
    /**
     * Check if a seed entry can be safely deleted
     * 
     * @param SeedEntry $seedEntry
     * @return array Array of issues preventing deletion (empty if safe to delete)
     */
    public function execute(SeedEntry $seedEntry): array
    {
        $issues = [];
        
        // Check for recipes using this seed entry
        $recipesCount = Recipe::where('seed_entry_id', $seedEntry->id)->count();
        if ($recipesCount > 0) {
            // Check if any of these recipes have active crops
            $harvestedStage = CropStage::findByCode('harvested');
            $activeCropsCount = Crop::whereHas('recipe', function($query) use ($seedEntry) {
                $query->where('seed_entry_id', $seedEntry->id);
            })->where('current_stage_id', '!=', $harvestedStage?->id)->count();
            
            if ($activeCropsCount > 0) {
                $issues[] = "{$activeCropsCount} active crops are using recipes with this seed entry";
            }
            
            $issues[] = "{$recipesCount} recipe(s) are using this seed entry";
        }
        
        // Check for consumables linked to this seed entry
        $consumablesCount = Consumable::where('seed_entry_id', $seedEntry->id)
            ->where('is_active', true)
            ->count();
        if ($consumablesCount > 0) {
            $issues[] = "{$consumablesCount} active consumable(s) are linked to this seed entry";
        }
        
        // Price history is not considered a blocking dependency since it's just historical data
        // and doesn't affect the ability to delete seed entries safely
        
        return $issues;
    }
}