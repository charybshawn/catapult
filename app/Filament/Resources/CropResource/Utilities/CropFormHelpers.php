<?php

namespace App\Filament\Resources\CropResource\Utilities;

use Filament\Schemas\Components\Utilities\Get;
use App\Models\Recipe;

/**
 * Shared utility methods for crop forms to eliminate DRY violations
 */
class CropFormHelpers
{
    /**
     * Check if a recipe requires soaking
     * Centralized logic to eliminate duplication across forms
     */
    public static function checkRecipeRequiresSoaking(Get $get): bool
    {
        $recipeId = $get('recipe_id');
        if (!$recipeId) {
            return false;
        }
        
        $recipe = Recipe::find($recipeId);
        return $recipe && $recipe->requiresSoaking();
    }

    /**
     * Get soaking requirement info text for display
     */
    public static function getSoakingRequiredInfo(Get $get): string
    {
        $recipe = Recipe::find($get('recipe_id'));
        if (!$recipe) {
            return '';
        }
        
        return "This recipe requires {$recipe->seed_soak_hours} hours of soaking before planting.";
    }
}