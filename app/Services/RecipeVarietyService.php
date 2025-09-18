<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropPlan;
use App\Models\Recipe;

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
        if (! $recipe) {
            return 'Unknown';
        }

        // Load masterSeedCatalog relationship if not already loaded
        if (! $recipe->relationLoaded('masterSeedCatalog')) {
            $recipe->load('masterSeedCatalog');
        }

        // Get common name from relationship or direct field
        $commonName = $recipe->masterSeedCatalog?->common_name ?? $recipe->common_name ?? 'Unknown';

        // Add cultivar name if available
        if ($recipe->cultivar_name) {
            return $commonName . ' (' . $recipe->cultivar_name . ')';
        }

        return $commonName;
    }

    /**
     * Get just the common name for a recipe
     */
    public function getCommonName(?Recipe $recipe): string
    {
        if (! $recipe) {
            return 'Unknown';
        }

        // Load relationship if not already loaded
        if (! $recipe->relationLoaded('masterSeedCatalog')) {
            $recipe->load('masterSeedCatalog');
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
        if (! $recipe) {
            return null;
        }

        return $recipe->cultivar_name;
    }

    /**
     * Get variety name for a crop
     */
    public function getCropVarietyName(Crop $crop): string
    {
        // Load recipe relationship if not already loaded
        if (! $crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (! $crop->recipe) {
            return 'Unknown';
        }

        return $this->getFullVarietyName($crop->recipe);
    }

    /**
     * Get variety name for a crop plan
     */
    public function getCropPlanVarietyName(CropPlan $cropPlan): string
    {
        // Load recipe relationship if not already loaded
        if (! $cropPlan->relationLoaded('recipe')) {
            $cropPlan->load('recipe');
        }

        if (! $cropPlan->recipe) {
            return 'Unknown';
        }

        return $this->getFullVarietyName($cropPlan->recipe);
    }
}
