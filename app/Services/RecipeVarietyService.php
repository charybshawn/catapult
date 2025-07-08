<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Crop;
use App\Models\CropPlan;

/**
 * Service to handle recipe variety information consistently
 * This replaces the old seedEntry-based approach
 */
class RecipeVarietyService
{
    /**
     * Get the full variety name for a recipe
     * Format: "Common Name (Cultivar Name)" or just "Common Name" if no cultivar
     */
    public function getFullVarietyName(?Recipe $recipe): string
    {
        if (!$recipe) {
            return 'Unknown';
        }
        
        if ($recipe->masterCultivar) {
            return $recipe->masterCultivar->getFullNameAttribute();
        }
        
        if ($recipe->masterSeedCatalog) {
            return $recipe->masterSeedCatalog->common_name;
        }
        
        // Fallback to direct fields if relationships not loaded
        if ($recipe->cultivar_name) {
            return $recipe->common_name . ' (' . $recipe->cultivar_name . ')';
        }
        
        return $recipe->common_name ?? 'Unknown';
    }
    
    /**
     * Get just the common name for a recipe
     */
    public function getCommonName(?Recipe $recipe): string
    {
        if (!$recipe) {
            return 'Unknown';
        }
        
        if ($recipe->masterSeedCatalog) {
            return $recipe->masterSeedCatalog->common_name;
        }
        
        return $recipe->common_name ?? 'Unknown';
    }
    
    /**
     * Get just the cultivar name for a recipe
     */
    public function getCultivarName(?Recipe $recipe): ?string
    {
        if (!$recipe) {
            return null;
        }
        
        if ($recipe->masterCultivar) {
            return $recipe->masterCultivar->cultivar_name;
        }
        
        return $recipe->cultivar_name;
    }
    
    /**
     * Get variety name for a crop
     */
    public function getCropVarietyName(Crop $crop): string
    {
        if (!$crop->recipe) {
            return 'Unknown';
        }
        
        return $this->getFullVarietyName($crop->recipe);
    }
    
    /**
     * Get variety name for a crop plan
     */
    public function getCropPlanVarietyName(CropPlan $cropPlan): string
    {
        if (!$cropPlan->recipe) {
            return 'Unknown';
        }
        
        return $this->getFullVarietyName($cropPlan->recipe);
    }
}