<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use App\Traits\Logging\ExtendedLogsActivity;
use App\Services\InventoryManagementService;
use App\Services\RecipeService;
use App\Traits\HasActiveStatus;
use App\Traits\HasTimestamps;

/**
 * Recipe Model for Agricultural Growing Methods and Parameters
 * 
 * Defines comprehensive growing instructions for microgreens production including
 * timing parameters, environmental conditions, seed densities, and expected yields.
 * Each recipe represents a proven methodology for growing a specific variety
 * under controlled conditions.
 * 
 * This model handles:
 * - Seed variety specifications and lot tracking
 * - Growing timeline parameters (germination, blackout, light periods)
 * - Soil/growing media requirements and supplier relationships
 * - Watering schedules and environmental conditions
 * - Expected yield calculations and success metrics
 * - Inventory integration for seed lot management
 * 
 * Business Context:
 * Agricultural recipes are the foundation of consistent microgreens production.
 * They encode years of growing experience into repeatable methodologies that
 * ensure:
 * - Consistent product quality and yields
 * - Optimal use of seeds, soil, and growing space
 * - Predictable harvest timing for order fulfillment
 * - Efficient resource planning and inventory management
 * - Quality control and crop standardization
 * 
 * @property int $id Primary key
 * @property string $name Auto-generated recipe name (Variety - Density - DTM - Lot)
 * @property int|null $master_seed_catalog_id Link to master seed variety (legacy)
 * @property int|null $master_cultivar_id Link to master cultivar (legacy)
 * @property string|null $common_name Variety common name (e.g., "Red Cabbage")
 * @property string|null $cultivar_name Specific cultivar name (e.g., "Red Acre")
 * @property int|null $seed_consumable_id Direct seed inventory link (deprecated)
 * @property string|null $lot_number Seed lot identifier for inventory tracking
 * @property \Carbon\Carbon|null $lot_depleted_at Manual lot depletion timestamp
 * @property int|null $soil_consumable_id Growing media inventory reference
 * @property float $germination_days Days for seed germination phase
 * @property float $blackout_days Days in darkness after germination
 * @property float $days_to_maturity Total days from seed to harvest
 * @property float $light_days Days under growing lights after blackout
 * @property int $seed_soak_hours Pre-planting seed soak time (0 = no soak)
 * @property float $expected_yield_grams Expected harvest weight per tray
 * @property float $buffer_percentage Yield safety margin percentage
 * @property float $seed_density_grams_per_tray Grams of seed per growing tray
 * @property bool $is_active Whether recipe is available for new crops
 * @property string|null $notes Additional growing notes and observations
 * @property int|null $suspend_water_hours Hours to suspend watering (e.g., pre-harvest)
 * @property \Carbon\Carbon $created_at Recipe creation timestamp
 * @property \Carbon\Carbon $updated_at Last recipe modification
 * 
 * @relationship masterSeedCatalog BelongsTo relationship to MasterSeedCatalog (legacy)
 * @relationship masterCultivar BelongsTo relationship to MasterCultivar (legacy)
 * @relationship soilConsumable BelongsTo relationship to soil/growing media Consumable
 * @relationship seedConsumable BelongsTo relationship to seed Consumable (deprecated)
 * @relationship wateringSchedule HasMany relationship to watering schedule entries
 * @relationship crops HasMany relationship to Crops using this recipe
 * @relationship harvests HasMany relationship to Harvests from this recipe
 * 
 * @business_rules
 * - Recipe names auto-generate from variety, seed density, DTM, and lot number
 * - Lot numbers link recipes to specific seed inventory batches
 * - Deactivated recipes (is_active = false) cannot be used for new crops
 * - Depleted lots prevent new crop creation until lot is replenished
 * - Days calculations: germination + blackout + light = days_to_maturity
 * - Seed density and expected yield determine efficiency metrics
 * - Buffer percentage adds safety margin to yield calculations
 * 
 * @workflow_patterns
 * Recipe Development Workflow:
 * 1. New seed variety acquired with lot number assigned
 * 2. Recipe created with initial growing parameters
 * 3. Test crops grown to validate timing and yields
 * 4. Recipe parameters adjusted based on results
 * 5. Recipe activated for production use
 * 
 * Production Planning Integration:
 * 1. Orders require specific varieties and quantities
 * 2. System selects active recipes for required varieties
 * 3. Inventory checked for seed lot availability
 * 4. Crop plans created using recipe timing parameters
 * 5. Resources allocated based on seed density and yield expectations
 * 
 * Lot Management Workflow:
 * 1. Seeds received and assigned lot numbers
 * 2. Recipes linked to specific seed lots
 * 3. Inventory tracked as seeds consumed in production
 * 4. Lot automatically or manually marked depleted
 * 5. New lots assigned to recipes for continued production
 * 
 * @agricultural_context
 * - Germination: Initial sprouting phase requiring moisture and controlled temperature
 * - Blackout: Dark period promoting stem elongation and tender growth
 * - Light period: Photosynthesis phase developing color and flavor
 * - Seed soaking: Pre-treatment to improve germination rates for hard seeds
 * - Growing media: Soil/coir blend providing nutrients and root support
 * - Yield expectations: Target harvest weights for planning and efficiency
 * 
 * @performance_considerations
 * - Lot-based inventory queries use efficient service layer
 * - Recipe calculations delegated to RecipeService for consistency
 * - Activity logging tracks critical parameter changes
 * - Relationship eager loading prevents N+1 query issues
 * 
 * @see \App\Services\RecipeService For recipe business logic and calculations
 * @see \App\Services\InventoryManagementService For lot-based inventory integration
 * @see \App\Models\Crop For recipe implementation in production
 * @see \App\Models\Consumable For seed and soil inventory management
 * 
 * @author Agricultural Systems Team
 * @package App\Models
 */
class Recipe extends Model
{
    use HasFactory, ExtendedLogsActivity, HasActiveStatus, HasTimestamps;
    
    /**
     * The attributes that are mass assignable.
     * 
     * Defines which recipe fields can be bulk assigned during creation
     * and updates, supporting agricultural recipe management workflows.
     * Includes all growing parameters, timing, and inventory relationships.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'master_seed_catalog_id',
        'master_cultivar_id',
        'common_name',
        'cultivar_name',
        'seed_consumable_id',
        'lot_number',
        'lot_depleted_at',
        'soil_consumable_id',
        'germination_days',
        'blackout_days',
        'days_to_maturity',
        'light_days',
        'seed_soak_hours',
        'expected_yield_grams', 
        'buffer_percentage',
        'seed_density_grams_per_tray',
        'is_active',
        'notes',
        'suspend_water_hours',
    ];
    
    /**
     * The attributes that should be cast to appropriate data types.
     * 
     * Ensures proper handling of agricultural timing parameters (floats),
     * boolean flags, and datetime stamps for lot management and depletion tracking.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'germination_days' => 'float',
        'blackout_days' => 'float',
        'days_to_maturity' => 'float',
        'light_days' => 'float',
        'seed_soak_hours' => 'integer',
        'expected_yield_grams' => 'float',
        'buffer_percentage' => 'decimal:2',
        'seed_density_grams_per_tray' => 'float',
        'is_active' => 'boolean',
        'lot_depleted_at' => 'datetime',
    ];
    
    
    /**
     * Get the master seed catalog for this recipe.
     * 
     * Legacy relationship to the master seed catalog system. Modern recipes
     * use lot-based inventory management instead of direct catalog links.
     * Maintained for backward compatibility with older recipe data.
     * 
     * @return BelongsTo<MasterSeedCatalog, Recipe> Legacy seed catalog relationship
     * @deprecated Use lot_number and lotConsumables() for inventory management
     * @business_context Historical seed variety tracking before lot system
     */
    public function masterSeedCatalog(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }
    
    /**
     * Get the master cultivar for this recipe.
     * 
     * Legacy relationship to master cultivar system. Replaced by lot-based
     * inventory management that tracks variety information within consumable
     * records linked by lot numbers.
     * 
     * @return BelongsTo<MasterCultivar, Recipe> Legacy cultivar relationship
     * @deprecated Use lot_number and lotConsumables() for variety information
     * @business_context Historical cultivar tracking before lot system
     */
    public function masterCultivar(): BelongsTo
    {
        return $this->belongsTo(MasterCultivar::class);
    }
    
    /**
     * Get the soil consumable for this recipe.
     * 
     * Returns the growing media (soil, coconut coir, peat moss blend)
     * specified for this recipe. Links to inventory system for stock tracking
     * and supplier management for growing media procurement.
     * 
     * @return BelongsTo<Consumable, Recipe> Growing media consumable relationship
     * @business_context Growing media directly affects germination and yield
     * @usage Inventory planning and supplier coordination for growing media
     */
    public function soilConsumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class, 'soil_consumable_id');
    }
    
    /**
     * Get the seed consumable for this recipe.
     * 
     * Direct link to seed inventory consumable record. Deprecated in favor
     * of lot-based inventory management which provides better traceability
     * and supports multiple consumable entries per seed variety.
     * 
     * @return BelongsTo<Consumable, Recipe> Direct seed consumable relationship
     * @deprecated Use lot-based methods instead (lotConsumables, availableLotConsumables)
     * @migration_path Use $recipe->lotConsumables() for current seed inventory
     */
    public function seedConsumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class, 'seed_consumable_id');
    }
    
    /**
     * Get all consumable entries for the recipe's lot_number.
     * 
     * Returns all seed inventory entries sharing the same lot number,
     * supporting modern lot-based inventory tracking. Delegates to
     * InventoryManagementService for consistent business logic and caching.
     * 
     * @return Collection<Consumable> All consumable entries in recipe's lot
     * @business_context Lot numbers group seeds from same supplier batch
     * @performance Uses service layer for efficient querying and caching
     * @usage Inventory reporting and lot quality tracking
     */
    public function lotConsumables(): Collection
    {
        if (!$this->lot_number) {
            return collect();
        }
        
        return app(InventoryManagementService::class)->getEntriesInLot($this->lot_number);
    }
    
    /**
     * Get available (not depleted) consumable entries for the recipe's lot.
     * 
     * Returns only seed inventory entries with positive stock levels,
     * filtering out depleted consumables. Critical for production planning
     * and ensuring recipes can be executed with available inventory.
     * 
     * @return Collection<Consumable> Available consumable entries with stock > 0
     * @business_context Only available seeds can be used for new crops
     * @performance Combines lot lookup with stock level filtering
     * @usage Crop planning validation and production scheduling
     */
    public function availableLotConsumables(): Collection
    {
        if (!$this->lot_number) {
            return collect();
        }
        
        $inventoryService = app(InventoryManagementService::class);
        return $inventoryService->getEntriesInLot($this->lot_number)
            ->filter(function (Consumable $consumable) use ($inventoryService) {
                return $inventoryService->getCurrentStock($consumable) > 0;
            });
    }
    
    
    /**
     * Get the watering schedule for this recipe.
     * 
     * Returns detailed watering instructions including frequency, duration,
     * and stage-specific parameters. Used for automated irrigation systems
     * and manual watering guidance throughout the growing cycle.
     * 
     * @return HasMany<RecipeWateringSchedule> Watering schedule entries
     * @business_context Proper watering prevents crop failure and optimizes yield
     * @usage Irrigation automation and grower instruction systems
     */
    public function wateringSchedule(): HasMany
    {
        return $this->hasMany(RecipeWateringSchedule::class);
    }
    
    /**
     * Get the crops using this recipe.
     * 
     * Returns all crops (active and historical) that have been grown using
     * this recipe. Supports performance analysis, yield tracking, and
     * recipe validation through historical production data.
     * 
     * @return HasMany<Crop> Crops grown using this recipe
     * @business_context Historical data validates recipe effectiveness
     * @performance Supports eager loading for recipe analytics
     * @usage Recipe performance analysis and crop history tracking
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class);
    }
    
    /**
     * Get the harvests for this recipe.
     * 
     * Returns all harvest records from crops using this recipe.
     * Provides yield analysis, quality tracking, and recipe optimization
     * data for continuous improvement of growing methodologies.
     * 
     * @return HasMany<Harvest> Harvest records from this recipe's crops
     * @business_context Harvest data validates expected yields and timing
     * @analytics Supports recipe performance metrics and optimization
     * @usage Yield analysis and recipe refinement workflows
     */
    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }
    
    /**
     * Calculate the total days from planting to harvest.
     * 
     * Computes the complete growing cycle duration by summing germination,
     * blackout, and light periods. Used for crop scheduling, order planning,
     * and resource allocation in production workflows.
     * 
     * @return float Total days from planting to harvest readiness
     * @business_context Critical for order fulfillment timing and planning
     * @delegation Uses RecipeService for consistent calculation logic
     * @usage Production scheduling and delivery date planning
     */
    public function totalDays(): float
    {
        return app(RecipeService::class)->calculateTotalDays($this);
    }
    
    /**
     * Calculate days to harvest including seed soak time.
     * 
     * Computes effective growing time including pre-planting seed soak period.
     * Provides complete timeline from seed preparation to harvest for accurate
     * production planning and customer delivery commitments.
     * 
     * @return float Total effective days including soak time
     * @business_context Includes all time from initial seed preparation
     * @delegation Uses RecipeService for consistent calculation with soak time
     * @usage Complete production timeline planning and customer communications
     */
    public function effectiveTotalDays(): float
    {
        return app(RecipeService::class)->calculateEffectiveTotalDays($this);
    }
    
    /**
     * Get total available quantity for the recipe's lot.
     * 
     * Returns aggregate quantity of seeds available across all consumable
     * entries in the recipe's lot. Essential for determining how many crops
     * can be produced with current inventory levels.
     * 
     * @return float Total available seed quantity in grams
     * @business_context Determines production capacity with current inventory
     * @delegation Uses InventoryManagementService for consistent lot calculations
     * @usage Production planning and crop capacity analysis
     */
    public function getLotQuantity(): float
    {
        if (!$this->lot_number) {
            return 0.0;
        }
        
        return app(InventoryManagementService::class)->getLotQuantity($this->lot_number);
    }

    /**
     * Get the relationships that should be logged with this model.
     * 
     * Defines which related models should be included in activity logs
     * for comprehensive audit trails of recipe changes including
     * soil specifications and watering schedule modifications.
     * 
     * @return array<string> Relationship names to include in activity logs
     * @business_context Recipe changes affect crop quality and success
     * @compliance Tracks changes to critical growing parameters
     */
    public function getLoggedRelationships(): array
    {
        return ['soilConsumable', 'wateringSchedule'];
    }

    /**
     * Get specific attributes to include from related models.
     * 
     * Specifies which attributes from related models should be logged
     * to provide meaningful audit context without excessive data storage.
     * Focuses on business-critical information for troubleshooting.
     * 
     * @return array<string, array<string>> Relationship attributes to log
     * @business_context Tracks critical parameters affecting crop outcomes
     * @performance Limits logged data to essential information
     */
    public function getRelationshipAttributesToLog(): array
    {
        return [
            'soilConsumable' => ['id', 'item_name', 'supplier_name', 'current_stock'],
            'wateringSchedule' => ['id', 'stage', 'frequency_hours', 'duration_seconds', 'notes'],
        ];
    }
    
    /**
     * Check if the recipe's assigned lot is depleted.
     * 
     * Determines if recipe can be used for new crop production by checking
     * both manual depletion flags and actual inventory levels. Prevents
     * crop creation when insufficient seeds are available.
     * 
     * @return bool True if lot is depleted and cannot be used
     * @business_context Prevents crop failures due to insufficient seeds
     * @checks Manual depletion flag and actual inventory levels
     * @usage Production validation and recipe availability filtering
     */
    public function isLotDepleted(): bool
    {
        if (!$this->lot_number) {
            return true;
        }
        
        // Check if manually marked as depleted
        if ($this->lot_depleted_at) {
            return true;
        }
        
        // Check actual inventory
        return app(InventoryManagementService::class)->isLotDepleted($this->lot_number);
    }
    
    /**
     * Check if recipe can be executed with required seed amount.
     * 
     * Validates that sufficient seed inventory exists to fulfill a specific
     * production requirement. Used during crop planning to ensure recipes
     * can be executed before committing to production schedules.
     * 
     * @param float $requiredQuantity Required seed quantity in grams
     * @return bool True if sufficient inventory exists for execution
     * @business_context Prevents over-promising on orders with insufficient inventory
     * @delegation Uses RecipeService for comprehensive execution validation
     * @usage Order acceptance validation and production planning
     */
    public function canExecute(float $requiredQuantity): bool
    {
        return app(RecipeService::class)->canExecuteRecipe($this, $requiredQuantity);
    }
    
    /**
     * Mark the lot as depleted with timestamp.
     * 
     * Manually flags the recipe's lot as depleted, preventing new crop creation
     * even if trace amounts remain in inventory. Used when lot quality degrades
     * or remaining quantity is insufficient for production.
     * 
     * @return void
     * @business_context Prevents use of poor quality or insufficient seed lots
     * @delegation Uses RecipeService for consistent lot depletion handling
     * @usage Manual inventory management and quality control workflows
     */
    public function markLotDepleted(): void
    {
        app(RecipeService::class)->markLotDepleted($this);
    }

    /**
     * Check if this recipe requires soaking before planting.
     * 
     * Determines if seeds need pre-planting soak treatment to improve
     * germination rates. Some varieties require soaking to soften hard
     * seed coats and ensure consistent sprouting.
     * 
     * @return bool True if recipe requires seed soaking (soak_hours > 0)
     * @business_context Soaking improves germination for hard-seeded varieties
     * @agricultural_context Common for beans, peas, and some brassicas
     * @usage Crop preparation workflows and timeline planning
     */
    public function requiresSoaking(): bool
    {
        return $this->seed_soak_hours > 0;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Recipe $recipe) {
            // Update dependent fields including auto-generating name
            app(RecipeService::class)->updateDependentFields($recipe);
        });
    }

    // Name generation logic moved to RecipeService

    /**
     * Configure the activity log options for this model.
     * 
     * Defines which recipe fields are tracked for audit and agricultural
     * compliance purposes. Logs changes to growing parameters, timing,
     * and inventory relationships for quality control and troubleshooting.
     * 
     * @return LogOptions Configured logging options for recipe changes
     * @business_context Recipe changes directly impact crop success rates
     * @compliance Required for agricultural quality control and traceability
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'common_name',
                'cultivar_name',
                'lot_number',
                'lot_depleted_at',
                'germination_days', 
                'blackout_days', 
                'days_to_maturity',
                'light_days',
                'expected_yield_grams',
                'seed_density_grams_per_tray',
                'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
