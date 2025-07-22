<?php

namespace App\Actions\Recipe;

use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

/**
 * Handle recipe cloning with stages and watering schedule
 * 
 * Pure business logic extracted from RecipeResource
 */
class CloneRecipe
{
    /**
     * Clone a recipe with all related records
     */
    public function execute(Recipe $originalRecipe): Recipe
    {
        return DB::transaction(function () use ($originalRecipe) {
            // Clone the main recipe record
            $clone = $this->cloneMainRecord($originalRecipe);
            
            // Clone related records
            $this->cloneStages($originalRecipe, $clone);
            $this->cloneWateringSchedule($originalRecipe, $clone);
            
            return $clone->fresh();
        });
    }
    
    /**
     * Clone the main recipe record
     */
    protected function cloneMainRecord(Recipe $originalRecipe): Recipe
    {
        $clone = $originalRecipe->replicate();
        $clone->name = $originalRecipe->name . ' (Clone)';
        $clone->save();
        
        return $clone;
    }
    
    /**
     * Clone recipe stages
     */
    protected function cloneStages(Recipe $originalRecipe, Recipe $clone): void
    {
        foreach ($originalRecipe->stages as $stage) {
            $stageClone = $stage->replicate();
            $stageClone->recipe_id = $clone->id;
            $stageClone->save();
        }
    }
    
    /**
     * Clone watering schedule
     */
    protected function cloneWateringSchedule(Recipe $originalRecipe, Recipe $clone): void
    {
        foreach ($originalRecipe->wateringSchedule as $schedule) {
            $scheduleClone = $schedule->replicate();
            $scheduleClone->recipe_id = $clone->id;
            $scheduleClone->save();
        }
    }
    
    /**
     * Get the redirect URL for the cloned recipe
     */
    public function getRedirectUrl(Recipe $clone): string
    {
        return route('filament.admin.resources.recipes.edit', ['record' => $clone->id]);
    }
}