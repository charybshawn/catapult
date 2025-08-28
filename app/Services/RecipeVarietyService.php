<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropPlan;
use App\Models\Recipe;

/**
 * Agricultural variety name resolution service for recipe-based crop identification.
 * 
 * Provides consistent variety naming and identification throughout the microgreens
 * production system. Handles complex relationships between recipes, cultivars,
 * and seed catalogs to generate standardized variety names for crops, crop plans,
 * and agricultural reporting. Replaces legacy seed entry approach with modern
 * recipe-based variety management.
 * 
 * @business_domain Agricultural variety identification and crop naming consistency
 * @agricultural_taxonomy Manages common names, cultivar names, and botanical classifications
 * @recipe_integration Links growing recipes with variety identification
 * @production_consistency Ensures uniform variety naming across all agricultural operations
 * 
 * @example
 * $varietyService = new RecipeVarietyService();
 * $fullName = $varietyService->getFullVarietyName($recipe);
 * // Returns: "Arugula (Astro)" or "Pea Shoots" based on available data
 * 
 * $cropName = $varietyService->getCropVarietyName($crop);
 * // Returns consistent variety name for crop regardless of data source
 * 
 * @features
 * - Multi-source variety name resolution
 * - Fallback logic for incomplete variety data
 * - Consistent formatting across agricultural operations
 * - Relationship-aware lazy loading for performance
 * - Legacy data compatibility with direct field access
 * 
 * @see Recipe For growing recipe definitions
 * @see MasterCultivar For cultivar variety information
 * @see MasterSeedCatalog For seed variety catalog data
 */
class RecipeVarietyService
{
    /**
     * Generate complete agricultural variety name from recipe relationships.
     * 
     * Creates standardized variety identification combining common name and
     * cultivar name when available. Uses intelligent fallback logic to handle
     * incomplete variety data while maintaining naming consistency across
     * agricultural operations and reporting.
     * 
     * @variety_formatting Standard format: "Common Name (Cultivar Name)"
     * @agricultural_identification Provides complete variety identification
     * @relationship_resolution Intelligently navigates recipe-cultivar-catalog relationships
     * @fallback_logic Handles missing or incomplete variety data gracefully
     * 
     * @param Recipe|null $recipe Recipe with variety relationship data
     * @return string Complete variety name in standardized format
     * 
     * @naming_examples
     * - "Arugula (Astro)" - Full variety with cultivar
     * - "Pea Shoots" - Common name only
     * - "Astro" - Cultivar name fallback
     * - "Unknown" - No variety data available
     * 
     * @relationship_priority
     * 1. Recipe -> MasterCultivar + MasterSeedCatalog (complete data)
     * 2. Recipe -> MasterCultivar -> MasterSeedCatalog (indirect catalog)
     * 3. Recipe -> MasterSeedCatalog only (common name)
     * 4. Recipe direct fields (legacy compatibility)
     * 5. "Unknown" fallback
     */
    public function getFullVarietyName(?Recipe $recipe): string
    {
        if (! $recipe) {
            return 'Unknown';
        }

        // Load relationships if not already loaded
        if (! $recipe->relationLoaded('masterCultivar')) {
            $recipe->load('masterCultivar');
        }
        if (! $recipe->relationLoaded('masterSeedCatalog')) {
            $recipe->load('masterSeedCatalog');
        }

        // Priority 1: Complete variety data from direct relationships
        if ($recipe->masterCultivar && $recipe->masterSeedCatalog) {
            $commonName = $recipe->masterSeedCatalog->common_name;
            $cultivarName = $recipe->masterCultivar->cultivar_name;

            return $commonName.' ('.$cultivarName.')'; // Full agricultural variety identification
        }

        // Priority 2: Indirect catalog access through cultivar relationship
        if ($recipe->relationLoaded('masterCultivar') && $recipe->masterCultivar) {
            $cultivarName = $recipe->masterCultivar->cultivar_name;

            // Attempt to resolve common name through cultivar's seed catalog
            if (! $recipe->masterCultivar->relationLoaded('masterSeedCatalog')) {
                $recipe->masterCultivar->load('masterSeedCatalog');
            }

            if ($recipe->masterCultivar->masterSeedCatalog) {
                $commonName = $recipe->masterCultivar->masterSeedCatalog->common_name;

                return $commonName.' ('.$cultivarName.')'; // Indirect agricultural identification
            }

            // Cultivar-only fallback when seed catalog unavailable
            return $cultivarName;
        }

        // Priority 3: Common name only from seed catalog
        if ($recipe->relationLoaded('masterSeedCatalog') && $recipe->masterSeedCatalog) {
            return $recipe->masterSeedCatalog->common_name; // Common agricultural name without cultivar
        }

        // Priority 4: Legacy direct field access for backward compatibility
        if ($recipe->cultivar_name) {
            $commonName = $recipe->common_name ?? 'Unknown';

            return $commonName.' ('.$recipe->cultivar_name.')'; // Legacy field access
        }

        // Final fallback: Common name or unknown
        return $recipe->common_name ?? 'Unknown';
    }

    /**
     * Extract common agricultural name from recipe variety data.
     * 
     * Retrieves the common name (species-level identification) from recipe
     * variety information using relationship data or direct field fallback.
     * Essential for agricultural categorization, reporting, and user interfaces
     * where cultivar-level detail is not required.
     * 
     * @agricultural_taxonomy Provides species-level plant identification
     * @common_naming User-friendly variety identification without cultivar specifics
     * @relationship_first Prioritizes catalog relationships over direct fields
     * @categorization_support Enables variety grouping and agricultural analysis
     * 
     * @param Recipe|null $recipe Recipe with variety identification data
     * @return string Common agricultural variety name
     * 
     * @examples
     * - "Arugula" (from "Arugula (Astro)")
     * - "Pea Shoots"
     * - "Sunflower"
     * - "Unknown" (no data available)
     */
    public function getCommonName(?Recipe $recipe): string
    {
        if (! $recipe) {
            return 'Unknown';
        }

        // Load seed catalog relationship for common name resolution
        if (! $recipe->relationLoaded('masterSeedCatalog')) {
            $recipe->load('masterSeedCatalog');
        }

        // Priority: Catalog common name over direct field
        if ($recipe->masterSeedCatalog) {
            return $recipe->masterSeedCatalog->common_name;
        }

        // Fallback to direct field for legacy compatibility
        return $recipe->common_name ?? 'Unknown';
    }

    /**
     * Extract cultivar name from recipe variety specification.
     * 
     * Retrieves specific cultivar identification from recipe data for detailed
     * variety tracking and agricultural precision. Cultivars represent specific
     * breeding lines or varieties within a common species, critical for
     * production consistency and quality management.
     * 
     * @agricultural_precision Provides cultivar-level variety identification
     * @breeding_specificity Identifies specific genetic lines and varieties
     * @production_consistency Ensures consistent cultivar tracking
     * @quality_management Enables variety-specific quality and performance analysis
     * 
     * @param Recipe|null $recipe Recipe with cultivar relationship data
     * @return string|null Specific cultivar name or null if not specified
     * 
     * @examples
     * - "Astro" (from Arugula varieties)
     * - "Rambo" (from Radish varieties)
     * - "Speckled Pea" (from Pea varieties)
     * - null (variety has no specific cultivar)
     */
    public function getCultivarName(?Recipe $recipe): ?string
    {
        if (! $recipe) {
            return null;
        }

        // Load cultivar relationship for cultivar name resolution
        if (! $recipe->relationLoaded('masterCultivar')) {
            $recipe->load('masterCultivar');
        }

        // Priority: Relationship cultivar name over direct field
        if ($recipe->masterCultivar) {
            return $recipe->masterCultivar->cultivar_name;
        }

        // Fallback to direct field for legacy compatibility
        return $recipe->cultivar_name;
    }

    /**
     * Resolve variety name for individual crop instance.
     * 
     * Provides complete variety identification for active crops by resolving
     * variety information through the crop's recipe relationship. Essential
     * for crop tracking, harvest records, and production reporting where
     * consistent variety identification is required.
     * 
     * @crop_identification Links individual crops to variety specifications
     * @agricultural_tracking Enables variety-based crop monitoring
     * @harvest_records Provides variety data for production documentation
     * @production_reporting Supports variety-specific performance analysis
     * 
     * @param Crop $crop Active crop instance with recipe relationship
     * @return string Complete variety name for the crop
     * 
     * @example
     * $varietyName = $this->getCropVarietyName($crop);
     * echo "Harvesting {$varietyName} from tray {$crop->tray_number}";
     * // Output: "Harvesting Arugula (Astro) from tray 42"
     */
    public function getCropVarietyName(Crop $crop): string
    {
        // Ensure recipe relationship is loaded for variety resolution
        if (! $crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        // Crop must have recipe for variety identification
        if (! $crop->recipe) {
            return 'Unknown'; // Cannot identify variety without recipe
        }

        // Delegate to full variety name resolution
        return $this->getFullVarietyName($crop->recipe);
    }

    /**
     * Resolve variety name for agricultural crop planning.
     * 
     * Provides complete variety identification for crop plans enabling consistent
     * variety naming in production planning, resource allocation, and scheduling
     * operations. Links planned production with variety-specific requirements
     * and characteristics.
     * 
     * @production_planning Enables variety-specific resource allocation
     * @agricultural_scheduling Links plans to variety requirements
     * @resource_calculation Supports variety-based material and time estimates
     * @planning_consistency Maintains uniform variety identification in plans
     * 
     * @param CropPlan $cropPlan Agricultural crop plan with recipe relationship
     * @return string Complete variety name for production planning
     * 
     * @example
     * $varietyName = $this->getCropPlanVarietyName($cropPlan);
     * echo "Planning {$cropPlan->quantity} trays of {$varietyName}";
     * // Output: "Planning 12 trays of Arugula (Astro)"
     */
    public function getCropPlanVarietyName(CropPlan $cropPlan): string
    {
        // Ensure recipe relationship is loaded for variety resolution
        if (! $cropPlan->relationLoaded('recipe')) {
            $cropPlan->load('recipe');
        }

        // Crop plan must have recipe for variety identification
        if (! $cropPlan->recipe) {
            return 'Unknown'; // Cannot plan production without variety specification
        }

        // Delegate to full variety name resolution
        return $this->getFullVarietyName($cropPlan->recipe);
    }
}
