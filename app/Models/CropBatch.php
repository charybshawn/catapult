<?php

namespace App\Models;

use App\Services\CropTimeCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural crop batch management model for synchronized production operations.
 * 
 * Manages groups of crops grown together using the same recipe and timing,
 * enabling efficient batch-based agricultural operations including synchronized
 * stage transitions, watering schedules, and harvest coordination.
 * 
 * @property int $id Primary key identifier
 * @property int $recipe_id Recipe used for this batch production
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read \App\Models\Recipe $recipe Production recipe for this batch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Crop> $crops
 * @property-read int|null $crops_count
 * @property-read int|null $current_stage_id Current growth stage from first crop
 * @property-read \Illuminate\Support\Carbon|null $germination_at Germination date from first crop
 * @property-read array $tray_numbers Sorted array of tray numbers in batch
 * @property-read int $tray_count Number of trays (crops) in batch
 * @property-read string|null $current_stage_name Current stage name for display
 * @property-read string|null $stage_age_display Formatted stage age
 * @property-read string|null $time_to_next_stage_display Time until next stage
 * @property-read string|null $total_age_display Total batch age
 * @property-read \Illuminate\Support\Carbon|null $expected_harvest_at Expected harvest date
 * @property-read int $crop_count Total number of crops in batch
 * 
 * @agricultural_context Enables synchronized production of microgreens and related crops
 * @business_rules All crops in batch share same recipe and follow synchronized timing
 * @performance_optimization Uses computed attribute caching to prevent N+1 queries
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class CropBatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crop_batches';
    
    /**
     * Cache for computed attributes to prevent N+1 queries
     */
    protected $computedAttributesCache = null;
    
    /**
     * Get a cached attribute or compute it if not cached
     */
    protected function getCachedAttribute(string $key, $default = null)
    {
        if ($this->computedAttributesCache === null) {
            $this->getComputedAttributes();
        }
        
        return $this->computedAttributesCache[$key] ?? $default;
    }
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        // Removed all appends to prevent N+1 queries
        // Attributes will be accessed directly when needed
    ];

    /**
     * Get the production recipe associated with this batch.
     * 
     * Retrieves the recipe that defines growing parameters, stage transitions,
     * and timing for all crops within this synchronized batch.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Recipe>
     * @agricultural_context Recipe defines variety, growing medium, timing for batch production
     * @business_usage Used for stage calculations, harvest planning, and production standardization
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get all crops belonging to this synchronized batch.
     * 
     * Retrieves individual crop instances that are managed together in this batch,
     * enabling coordinated agricultural operations and monitoring.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Crop>
     * @agricultural_context Returns individual tray crops managed as synchronized group
     * @business_usage Used for batch operations, stage transitions, and harvest coordination
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'crop_batch_id');
    }

    /**
     * Get the current stage ID from the first crop in the batch.
     * 
     * Returns the current growth stage identifier from the representative first crop,
     * as all crops in a batch maintain synchronized stages.
     * 
     * @return int|null Current stage ID or null if no crops exist
     * @agricultural_context All batch crops maintain same stage through synchronized transitions
     * @business_logic Uses first crop as representative since all crops synchronized
     */
    public function getCurrentStageIdAttribute(): ?int
    {
        $firstCrop = $this->getFirstCrop();
        return $firstCrop ? $firstCrop->current_stage_id : null;
    }

    /**
     * Scope to eager load all necessary relationships for optimal performance.
     * 
     * Loads complete batch information including crops, recipes, and catalog data
     * with optimized queries to prevent N+1 performance issues.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder instance
     * @return \Illuminate\Database\Eloquent\Builder Modified query with eager loading
     * @performance_optimization Prevents N+1 queries for batch listing operations
     * @agricultural_context Loads variety information and production recipes
     */
    public function scopeWithFullDetails(Builder $query): Builder
    {
        return $query->with([
            'crops' => function ($query) {
                $query->with(['recipe', 'recipe.masterCultivar', 'recipe.masterSeedCatalog'])
                      ->orderBy('tray_number');
            },
            'recipe',
            'recipe.masterCultivar',
            'recipe.masterSeedCatalog',
        ])
        ->withCount('crops');
    }
    
    /**
     * Compute and cache all batch attributes for optimal performance.
     * 
     * Calculates timing, stage, and production attributes once and caches results
     * to prevent repeated computations and N+1 query issues.
     * 
     * @return array Computed attributes for batch display and operations
     * @performance_optimization Caches expensive calculations to prevent repeated queries
     * @agricultural_context Computes stage timing, harvest dates, and batch metrics
     * @business_logic Uses first crop as representative for synchronized batch data
     */
    public function getComputedAttributes(): array
    {
        // Return cache if already computed
        if ($this->computedAttributesCache !== null) {
            return $this->computedAttributesCache;
        }
        
        $firstCrop = $this->crops->first();
        
        if (!$firstCrop) {
            $this->computedAttributesCache = [
                'germination_at' => null,
                'tray_numbers' => [],
                'tray_count' => 0,
                'current_stage_id' => null,
                'current_stage_name' => null,
                'stage_age_display' => null,
                'time_to_next_stage_display' => null,
                'total_age_display' => null,
                'expected_harvest_at' => null,
            ];
            return $this->computedAttributesCache;
        }
        
        $calculator = new CropTimeCalculator();
        
        // Use cached stages if available
        static $stagesCache = null;
        if ($stagesCache === null) {
            $stagesCache = CropStage::all()->keyBy('id');
        }
        $stage = $stagesCache->get($firstCrop->current_stage_id);
        
        // Calculate expected harvest date using germination_at as the primary date
        $expectedHarvestAt = null;
        if ($firstCrop->recipe && $firstCrop->recipe->days_to_maturity && $firstCrop->germination_at) {
            $expectedHarvestAt = Carbon::parse($firstCrop->germination_at)->addDays($firstCrop->recipe->days_to_maturity);
        }
        
        $this->computedAttributesCache = [
            'germination_at' => $firstCrop->germination_at ? Carbon::parse($firstCrop->germination_at) : null,
            'tray_numbers' => $this->crops->pluck('tray_number')->sort()->values()->toArray(),
            'tray_count' => $this->crops_count ?? $this->crops->count(),
            'current_stage_id' => $firstCrop->current_stage_id,
            'current_stage_name' => $stage ? $stage->name : null,
            'stage_age_display' => $calculator->getStageAgeDisplay($firstCrop),
            'time_to_next_stage_display' => $calculator->getTimeToNextStageDisplay($firstCrop),
            'total_age_display' => $calculator->getTotalAgeDisplay($firstCrop),
            'expected_harvest_at' => $expectedHarvestAt,
        ];
        
        return $this->computedAttributesCache;
    }

    /**
     * Get the germination date from the first crop.
     */
    public function getGerminationAtAttribute(): ?Carbon
    {
        if ($this->computedAttributesCache !== null && isset($this->computedAttributesCache['germination_at'])) {
            return $this->computedAttributesCache['germination_at'];
        }
        
        $firstCrop = $this->getFirstCrop();
        return $firstCrop?->germination_at ? Carbon::parse($firstCrop->germination_at) : null;
    }

    /**
     * Get sorted array of tray numbers from all crops.
     */
    public function getTrayNumbersAttribute(): array
    {
        return $this->getCachedAttribute('tray_numbers', []);
    }

    /**
     * Get the count of trays (crops) in this batch.
     */
    public function getTrayCountAttribute(): int
    {
        if ($this->relationLoaded('crops')) {
            return $this->crops->count();
        }
        return $this->crops()->count();
    }

    /**
     * Get the current stage name from the first crop.
     */
    public function getCurrentStageNameAttribute(): ?string
    {
        $firstCrop = $this->getFirstCrop();
        
        if (!$firstCrop || !$firstCrop->current_stage_id) {
            return null;
        }

        // Use cached stages to avoid queries
        static $stagesCache = null;
        if ($stagesCache === null) {
            $stagesCache = CropStage::all()->keyBy('id');
        }
        
        $stage = $stagesCache->get($firstCrop->current_stage_id);
        return $stage ? $stage->name : null;
    }

    /**
     * Get the stage age display from the first crop.
     */
    public function getStageAgeDisplayAttribute(): ?string
    {
        $firstCrop = $this->getFirstCrop();
        
        if (!$firstCrop) {
            return null;
        }

        $calculator = new CropTimeCalculator();
        $stageAge = $calculator->calculateStageAge($firstCrop);
        return $calculator->formatTimeDisplay($stageAge);
    }

    /**
     * Get the time to next stage display from the first crop.
     */
    public function getTimeToNextStageDisplayAttribute(): ?string
    {
        $firstCrop = $this->getFirstCrop();
        
        if (!$firstCrop) {
            return null;
        }

        $calculator = new CropTimeCalculator();
        return $calculator->getTimeToNextStageDisplay($firstCrop);
    }

    /**
     * Get the total age display from the first crop.
     */
    public function getTotalAgeDisplayAttribute(): ?string
    {
        $firstCrop = $this->getFirstCrop();
        
        if (!$firstCrop) {
            return null;
        }

        $calculator = new CropTimeCalculator();
        $totalAge = $calculator->calculateTotalAge($firstCrop);
        return $calculator->formatTimeDisplay($totalAge);
    }

    /**
     * Get the expected harvest date from the first crop.
     */
    public function getExpectedHarvestAtAttribute(): ?Carbon
    {
        $firstCrop = $this->getFirstCrop();
        
        if (!$firstCrop) {
            return null;
        }

        if (!$firstCrop->relationLoaded('recipe')) {
            $firstCrop->load('recipe');
        }

        if (!$firstCrop->recipe || !$firstCrop->recipe->days_to_maturity) {
            return null;
        }

        // Use planting_at if available, otherwise fall back to germination_at
        $startDate = $firstCrop->planting_at ?: $firstCrop->germination_at;
        
        if (!$startDate) {
            return null;
        }

        return Carbon::parse($startDate)->addDays($firstCrop->recipe->days_to_maturity);
    }

    /**
     * Get the count of crops in this batch.
     */
    public function getCropCountAttribute(): int
    {
        return $this->crops()->count();
    }


    /**
     * Check if this batch is currently in soaking stage.
     * 
     * Determines if the batch is in the soaking stage of production,
     * which requires different handling than other growth stages.
     * 
     * @return bool True if batch is in soaking stage
     * @agricultural_context Soaking stage requires special monitoring and timing
     * @business_logic All crops in batch maintain synchronized stages
     */
    public function isInSoaking(): bool
    {
        $firstCrop = $this->crops()->with('currentStage')->first();
        return $firstCrop?->getRelation('currentStage')?->code === 'soaking';
    }

    /**
     * Get the first crop from the batch, ensuring relationships are loaded.
     */
    private function getFirstCrop(): ?Crop
    {
        // If we have a cached first crop, return it
        if (isset($this->firstCropCache)) {
            return $this->firstCropCache;
        }
        
        // If crops are loaded, use them
        if ($this->relationLoaded('crops')) {
            $this->firstCropCache = $this->crops->first();
            return $this->firstCropCache;
        }
        
        // Otherwise load crops - but this should not happen if we eager load properly
        $this->load('crops.currentStage', 'crops.recipe');
        $this->firstCropCache = $this->crops->first();
        
        return $this->firstCropCache;
    }
    
    protected $firstCropCache = null;
}