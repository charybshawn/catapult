<?php

namespace App\Models;

use App\Services\CropPlanningService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * ProductMix Model for Agricultural Seed Mix Combinations
 * 
 * Manages pre-defined combinations of microgreens varieties that are grown together
 * and sold as mixed products. Each mix consists of multiple seed varieties with
 * specific percentages, creating unique flavor profiles and visual presentations
 * for customers.
 * 
 * This model handles:
 * - Mix composition with percentage-based variety allocation
 * - Recipe assignment for each variety component in the mix
 * - Production planning with tray calculations for each variety
 * - Quality control through percentage validation and recipe verification
 * - Product integration for mixed offerings in the catalog
 * 
 * Business Context:
 * Mixed microgreens products are popular with restaurants and consumers who want
 * variety without purchasing multiple individual products. Common mixes include:
 * - Spicy Mix (radish, mustard, arugula)
 * - Mild Mix (peas, sunflower, broccoli)  
 * - Colorful Mix (red cabbage, beets, kale)
 * - Chef's Special blends for specific restaurant clients
 * 
 * Mixes require precise production coordination since each variety has different:
 * - Growing timelines and harvest schedules
 * - Seed densities and yield expectations
 * - Recipe requirements for optimal quality
 * - Inventory availability and lot management
 * 
 * @property int $id Primary key
 * @property string $name Mix name for identification (e.g., "Spicy Mix", "Rainbow Blend")
 * @property string|null $description Detailed mix description for marketing and production notes
 * @property bool $is_active Whether mix is available for new orders and production
 * @property \Carbon\Carbon $created_at Mix creation timestamp
 * @property \Carbon\Carbon $updated_at Last mix modification
 * 
 * @relationship masterSeedCatalogs BelongsToMany relationship to seed varieties with percentages
 * @relationship products HasMany relationship to Products using this mix
 * 
 * @business_rules
 * - Mix component percentages must total exactly 100.00%
 * - Each component must have a valid recipe for production
 * - Inactive mixes (is_active = false) cannot be used for new products
 * - Components must reference active master seed catalog entries
 * - Recipe assignments can be component-specific or use default variety recipes
 * 
 * @workflow_patterns
 * Mix Development Workflow:
 * 1. Market research identifies desired flavor/color profiles
 * 2. Mix created with component varieties and target percentages
 * 3. Test batches produced to validate taste and appearance
 * 4. Component percentages adjusted based on testing results
 * 5. Recipes assigned to each component for production consistency
 * 6. Mix activated for product creation and customer orders
 * 
 * Production Planning Integration:
 * 1. Order requires mixed product with specific quantities
 * 2. System calculates individual variety requirements using percentages
 * 3. Component recipes validated for availability and active status
 * 4. Tray calculations performed for each variety in the mix
 * 5. Individual crops planned with coordinated harvest timing
 * 6. Mixed harvest combined according to percentage specifications
 * 
 * Quality Control Process:
 * 1. Percentage validation ensures components total 100%
 * 2. Recipe validation confirms all components have active recipes
 * 3. Inventory validation checks seed availability for all components
 * 4. Production validation coordinates timing across varieties
 * 
 * @pivot_table_fields product_mix_components
 * - product_mix_id: Link to ProductMix
 * - master_seed_catalog_id: Link to seed variety
 * - percentage: Decimal percentage of this variety in mix (0.00-100.00)
 * - cultivar: Specific cultivar name override for this component
 * - recipe_id: Optional specific recipe ID for this component
 * 
 * @agricultural_context
 * Mix production requires coordinating multiple growing timelines to achieve
 * synchronized harvests. Varieties with different maturation periods may need
 * staggered planting or specialized timing to ensure fresh mixed products.
 * 
 * @see \App\Models\Product For products using mixes instead of single varieties
 * @see \App\Models\MasterSeedCatalog For individual varieties in mixes
 * @see \App\Models\Recipe For component-specific growing instructions
 * @see \App\Services\CropPlanningService For production planning with mixes
 * 
 * @author Agricultural Systems Team
 * @package App\Models
 */
class ProductMix extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     * 
     * Defines which mix fields can be bulk assigned during creation
     * and updates, supporting agricultural mix management workflows.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];
    
    /**
     * The attributes that should be cast to appropriate data types.
     * 
     * Ensures proper handling of boolean flags for mix availability
     * and status tracking in agricultural product management.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * The "booted" method of the model.
     * 
     * Defines model lifecycle hooks for ProductMix. Currently includes
     * placeholder for mix percentage validation after saving relationships.
     * Validation is handled in forms for better user experience.
     */
    protected static function booted()
    {
        // Validate mix percentages after saving relationships
        static::saved(function ($productMix) {
            // This will run after the mix is saved, giving time for relationships to be updated
            // We'll handle validation in the form instead to provide better UX
        });
    }
    
    
    /**
     * Get the master seed catalog entries that make up this mix.
     * 
     * Returns many-to-many relationship with seed varieties including
     * pivot data for percentages, cultivar overrides, and recipe assignments.
     * Forms the core composition data for mixed products.
     * 
     * @return BelongsToMany<MasterSeedCatalog> Seed varieties with mix percentages
     * @pivot percentage Decimal percentage of this variety in mix (0.00-100.00)
     * @pivot cultivar Optional cultivar name override for this component
     * @pivot recipe_id Optional specific recipe ID for this component
     * @business_context Percentages must total 100% for production accuracy
     */
    public function masterSeedCatalogs(): BelongsToMany
    {
        return $this->belongsToMany(MasterSeedCatalog::class, 'product_mix_components', 'product_mix_id', 'master_seed_catalog_id')
            ->withPivot('percentage', 'cultivar', 'recipe_id')
            ->withTimestamps();
    }
    
    /**
     * Get the products that use this mix.
     * 
     * Returns all products that are based on this seed variety mix rather
     * than single varieties. Supports impact analysis when modifying mix
     * compositions and production planning for mixed offerings.
     * 
     * @return HasMany<Product> Products using this mix composition
     * @business_context Mix changes affect all associated products
     * @usage Product catalog management and mix impact analysis
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_mix_id');
    }
    
    /**
     * Calculate the number of trays needed for each cultivar in this mix.
     * 
     * Distributes total tray requirement across mix components based on
     * their percentages, using ceiling function to ensure sufficient
     * production capacity for each variety component.
     * 
     * @param int $totalTrays The total number of trays for this mix
     * @return array<int, int> Array of [master_seed_catalog_id => trays_needed]
     * @business_context Ensures adequate production for each mix component
     * @algorithm Uses ceiling to prevent under-production due to rounding
     * @usage Production planning and resource allocation for mixed crops
     */
    public function calculateCultivarTrays(int $totalTrays): array
    {
        $cultivarTrays = [];
        
        foreach ($this->masterSeedCatalogs as $catalog) {
            $percentage = $catalog->pivot->percentage;
            $trays = ceil(($percentage / 100) * $totalTrays);
            $cultivarTrays[$catalog->id] = $trays;
        }
        
        return $cultivarTrays;
    }
    
    /**
     * Configure the activity log options for this model.
     * 
     * Defines which mix fields are tracked for audit and agricultural
     * compliance purposes. Logs changes to mix composition, descriptions,
     * and availability status for quality control.
     * 
     * @return LogOptions Configured logging options for mix changes
     * @business_context Mix changes affect product offerings and production
     * @compliance Required for agricultural quality control and traceability
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    /**
     * Validate that the mix percentages add up to 100%.
     * 
     * Verifies mathematical accuracy of mix composition by ensuring
     * component percentages sum to exactly 100.00%. Includes tolerance
     * for floating-point precision to handle rounding differences.
     * 
     * @return bool True if percentages total 100% within tolerance
     * @business_context Prevents production errors from incorrect percentages
     * @tolerance Allows 0.01% variance for floating-point precision
     * @usage Quality control validation before mix activation
     */
    public function validatePercentages(): bool
    {
        $total = $this->masterSeedCatalogs()->sum('percentage');
        
        // Round to 2 decimal places to match database precision
        $total = round($total, 2);
        
        // Allow for very small floating point differences
        return abs($total - 100) < 0.01;
    }
    
    /**
     * Get the total percentage of the mix.
     * 
     * Calculates sum of all component percentages for validation and
     * display purposes. Should equal 100.00% for valid mixes but may
     * show discrepancies during editing or configuration errors.
     * 
     * @return float Sum of all component percentages
     * @business_context Used for validation and troubleshooting mix configuration
     * @usage Mix validation forms and administrative reporting
     */
    public function getTotalPercentageAttribute(): float
    {
        return $this->masterSeedCatalogs()->sum('percentage');
    }
    
    /**
     * Get the recipe for a specific component in this mix.
     * 
     * Resolves recipe for a mix component by first checking for component-specific
     * recipe assignments, then falling back to standard variety recipes.
     * Ensures only active, non-depleted recipes are returned for production.
     * 
     * @param int $masterSeedCatalogId Master seed catalog ID to lookup recipe for
     * @return Recipe|null Active recipe for component or null if none available
     * @business_context Component-specific recipes allow mix customization
     * @fallback Uses CropPlanningService for standard variety recipe lookup
     * @usage Production planning and component validation
     */
    public function getComponentRecipe(int $masterSeedCatalogId): ?Recipe
    {
        // First check if the component has a specific recipe assigned
        $component = $this->masterSeedCatalogs()
            ->where('master_seed_catalog_id', $masterSeedCatalogId)
            ->first();
            
        if ($component && $component->pivot->recipe_id) {
            $recipe = Recipe::where('id', $component->pivot->recipe_id)
                ->where('is_active', true)
                ->whereNull('lot_depleted_at')
                ->first();
                
            if ($recipe) {
                return $recipe;
            }
        }
        
        // Fallback to standard recipe lookup for this variety
        return app(CropPlanningService::class)
            ->findActiveRecipeForVariety($masterSeedCatalogId);
    }
    
    /**
     * Validate that all components have resolvable recipes.
     * 
     * Performs comprehensive validation of mix components including
     * recipe availability, naming consistency, and production readiness.
     * Returns detailed results for troubleshooting and quality control.
     * 
     * @return array<int, array> Detailed validation results per component
     * @structure [
     *   catalog_id => [
     *     'catalog_name' => string,
     *     'cultivar' => string,
     *     'percentage' => float,
     *     'has_recipe' => bool,
     *     'recipe_name' => string|null,
     *     'recipe_id' => int|null,
     *     'component_recipe_id' => int|null
     *   ]
     * ]
     * @business_context Ensures all components can be produced before activation
     * @usage Mix validation workflows and production readiness checks
     */
    public function validateComponentRecipes(): array
    {
        $results = [];
        
        // Ensure the relationship is loaded to avoid lazy loading
        if (!$this->relationLoaded('masterSeedCatalogs')) {
            $this->load('masterSeedCatalogs');
        }
        
        foreach ($this->masterSeedCatalogs as $catalog) {
            $recipe = $this->getComponentRecipe($catalog->id);
            $results[$catalog->id] = [
                'catalog_name' => $catalog->common_name,
                'cultivar' => $catalog->pivot->cultivar,
                'percentage' => $catalog->pivot->percentage,
                'has_recipe' => $recipe !== null,
                'recipe_name' => $recipe ? $recipe->name : null,
                'recipe_id' => $recipe ? $recipe->id : null,
                'component_recipe_id' => $catalog->pivot->recipe_id,
            ];
        }
        
        return $results;
    }
    
    /**
     * Check if all components have resolvable recipes.
     * 
     * Simplified boolean check for mix production readiness by verifying
     * that every component has an active, available recipe. Used for
     * quick validation in production workflows and order acceptance.
     * 
     * @return bool True if all components have active recipes available
     * @business_context Prevents production failures from missing recipes
     * @performance Uses lazy loading prevention for efficient validation
     * @usage Order validation and production planning workflows
     */
    public function hasAllRecipes(): bool
    {
        // Ensure the relationship is loaded to avoid lazy loading
        if (!$this->relationLoaded('masterSeedCatalogs')) {
            $this->load('masterSeedCatalogs');
        }
        
        $validation = $this->validateComponentRecipes();
        
        foreach ($validation as $result) {
            if (!$result['has_recipe']) {
                return false;
            }
        }
        
        return true;
    }
} 