<?php

namespace App\Models;

use App\Services\CropTimeCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Collections\CropBatchCollection;

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
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new CropBatchCollection($models);
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
     * Get the recipe for this batch.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the crops in this batch.
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'crop_batch_id');
    }

    /**
     * Get the current stage ID attribute from the first crop.
     */
    public function getCurrentStageIdAttribute(): ?int
    {
        $firstCrop = $this->getFirstCrop();
        return $firstCrop ? $firstCrop->current_stage_id : null;
    }

    /**
     * Scope to eager load all necessary relationships for optimal performance.
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
     * Get all computed attributes as an array to avoid N+1 queries.
     * Call this after eager loading relationships.
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
                'planting_at' => null,
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
            $stagesCache = \App\Models\CropStage::all()->keyBy('id');
        }
        $stage = $stagesCache->get($firstCrop->current_stage_id);
        
        $this->computedAttributesCache = [
            'planting_at' => $firstCrop->planting_at ? Carbon::parse($firstCrop->planting_at) : null,
            'tray_numbers' => $this->crops->pluck('tray_number')->sort()->values()->toArray(),
            'tray_count' => $this->crops_count ?? $this->crops->count(),
            'current_stage_id' => $firstCrop->current_stage_id,
            'current_stage_name' => $stage ? $stage->name : null,
            'stage_age_display' => $calculator->formatTimeDisplay($calculator->calculateStageAge($firstCrop)),
            'time_to_next_stage_display' => $calculator->formatTimeDisplay($calculator->calculateTimeToNextStage($firstCrop)),
            'total_age_display' => $calculator->formatTimeDisplay($calculator->calculateTotalAge($firstCrop)),
            'expected_harvest_at' => $firstCrop->recipe && $firstCrop->recipe->days_to_maturity 
                ? Carbon::parse($firstCrop->planting_at)->addDays($firstCrop->recipe->days_to_maturity)
                : null,
        ];
        
        return $this->computedAttributesCache;
    }

    /**
     * Get the planting date from the first crop.
     */
    public function getPlantingAtAttribute(): ?Carbon
    {
        if ($this->computedAttributesCache !== null && isset($this->computedAttributesCache['planting_at'])) {
            return $this->computedAttributesCache['planting_at'];
        }
        
        $firstCrop = $this->getFirstCrop();
        return $firstCrop?->planting_at ? Carbon::parse($firstCrop->planting_at) : null;
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
            $stagesCache = \App\Models\CropStage::all()->keyBy('id');
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
        $timeToNextStage = $calculator->calculateTimeToNextStage($firstCrop);
        return $calculator->formatTimeDisplay($timeToNextStage);
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
        
        if (!$firstCrop || !$firstCrop->planting_at) {
            return null;
        }

        if (!$firstCrop->relationLoaded('recipe')) {
            $firstCrop->load('recipe');
        }

        if (!$firstCrop->recipe || !$firstCrop->recipe->days_to_maturity) {
            return null;
        }

        return Carbon::parse($firstCrop->planting_at)->addDays($firstCrop->recipe->days_to_maturity);
    }

    /**
     * Get the count of crops in this batch.
     */
    public function getCropCountAttribute(): int
    {
        return $this->crops()->count();
    }


    /**
     * Check if this batch is in soaking stage.
     */
    public function isInSoaking(): bool
    {
        $firstCrop = $this->crops()->with('currentStage')->first();
        return $firstCrop?->currentStage?->code === 'soaking';
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