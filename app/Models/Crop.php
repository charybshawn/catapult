<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use App\Traits\Logging\ExtendedLogsActivity;
use App\Services\CropLifecycleService;
use App\Services\CropTimeCalculator;
use App\Services\InventoryManagementService;
use App\Services\CropValidationService;
use App\Models\SeedEntry;
use App\Models\Harvest;
use Illuminate\Support\Facades\Log;
use App\Traits\HasTimestamps;

class Crop extends Model
{
    use HasFactory, ExtendedLogsActivity;
    
    /**
     * Flag to prevent recursive model events during bulk operations
     */
    private static bool $bulkOperation = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'crop_batch_id',
        'recipe_id',
        'order_id',
        'crop_plan_id',
        'tray_number',
        'current_stage_id',
        'germination_at',
        'blackout_at',
        'light_at',
        'harvested_at',
        'watering_suspended_at',
        'soaking_at',
        'requires_soaking',
        'notes',
    ];
    
    /**
     * The attributes that should be appended to arrays.
     *
     * @var array
     */
    protected $appends = ['variety_name'];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'germination_at' => 'datetime',
        'blackout_at' => 'datetime',
        'light_at' => 'datetime',
        'harvested_at' => 'datetime',
        'watering_suspended_at' => 'datetime',
        'soaking_at' => 'datetime',
        'requires_soaking' => 'boolean',
        'tray_number' => 'string',
    ];
    
    /**
     * Get the crop batch for this crop.
     */
    public function cropBatch(): BelongsTo
    {
        return $this->belongsTo(CropBatch::class);
    }
    
    /**
     * Get the recipe for this crop.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
    
    /**
     * Get the order for this crop.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the crop plan for this crop.
     */
    public function cropPlan(): BelongsTo
    {
        return $this->belongsTo(CropPlan::class);
    }
    
    /**
     * Get the current stage for this crop.
     */
    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(CropStage::class, 'current_stage_id');
    }
    
    /**
     * Get the batch this crop belongs to.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CropBatch::class, 'crop_batch_id');
    }
    
    /**
     * Get the harvests for this crop.
     */
    public function harvests(): BelongsToMany
    {
        return $this->belongsToMany(Harvest::class, 'crop_harvest')
            ->withPivot([
                'harvested_weight_grams',
                'percentage_harvested',
                'notes'
            ])
            ->withTimestamps();
    }
    
    /**
     * Enable bulk operation mode to prevent recursive events
     */
    public static function enableBulkOperation(): void
    {
        self::$bulkOperation = true;
    }
    
    /**
     * Disable bulk operation mode to re-enable events
     */
    public static function disableBulkOperation(): void
    {
        self::$bulkOperation = false;
    }
    
    /**
     * Get the variety name for this crop.
     */
    public function getVarietyNameAttribute(): ?string
    {
        // Ensure recipe relationship is loaded to avoid lazy loading
        if (!$this->relationLoaded('recipe')) {
            $this->load('recipe');
        }
        
        if ($this->recipe) {
            return $this->recipe->cultivar_name;
        }
        
        return null;
    }
    
    /**
     * Get the current_stage attribute for backward compatibility.
     * Maps the current_stage_id to the stage code.
     */
    public function getCurrentStageCodeAttribute(): string
    {
        // If we have a current_stage_id, try to get from the relationship
        if ($this->current_stage_id && $this->relationLoaded('currentStage')) {
            $stage = $this->getRelationValue('currentStage');
            return $stage ? $stage->code : 'germination';
        }
        
        // Otherwise, use the default mapping
        $stageMap = [
            1 => 'soaking',
            2 => 'germination',
            3 => 'blackout',
            4 => 'light',
            5 => 'harvested'
        ];
        
        return $stageMap[$this->current_stage_id ?? 2] ?? 'germination';
    }
    
    /**
     * Get the current_stage attribute for backward compatibility.
     * Maps the current_stage_id to the stage code.
     */
    public function getCurrentStageAttribute(): string
    {
        return $this->getCurrentStageCodeAttribute();
    }
    
    /**
     * Set the current_stage attribute for backward compatibility.
     * Maps the stage code to current_stage_id.
     */
    public function setCurrentStageCodeAttribute(string $value): void
    {
        $stage = CropStage::findByCode($value);
        if ($stage) {
            $this->current_stage_id = $stage->id;
        }
    }
    
    /**
     * Set the current_stage attribute for backward compatibility.
     * Maps the stage code to current_stage_id.
     */
    public function setCurrentStageAttribute(string $value): void
    {
        $stage = CropStage::findByCode($value);
        if ($stage) {
            $this->current_stage_id = $stage->id;
        }
    }
    
    /**
     * For compatibility with the dashboard template - tray is actually just a string field.
     */
    public function tray()
    {
        // This is a workaround to provide a tray "object" with tray_number property
        return new class($this->tray_number) {
            public $tray_number;
            
            public function __construct($tray_number) {
                $this->tray_number = $tray_number;
            }
        };
    }
    
    /**
     * Check if watering is suspended.
     */
    public function isWateringSuspended(): bool
    {
        return $this->watering_suspended_at !== null;
    }
    
    /**
     * Check if the crop is currently in the soaking stage.
     * 
     * @return bool
     */
    public function isActivelySoaking(): bool
    {
        // Must require soaking and have started soaking
        if (!$this->requires_soaking || $this->soaking_at === null) {
            return false;
        }
        
        // If no germination date set, still soaking
        if ($this->germination_at === null) {
            return true;
        }
        
        // If germination date is in the future, still soaking
        return $this->germination_at->isFuture();
    }
    
    /**
     * Alias for isActivelySoaking() to maintain compatibility with table actions.
     * 
     * @return bool
     */
    public function isInSoaking(): bool
    {
        return $this->isActivelySoaking();
    }
    
    /**
     * Get the current stage name for display purposes.
     * 
     * @return string
     */
    public function getCurrentStageNameAttribute(): string
    {
        // Load the currentStage relationship if not already loaded
        if (!$this->relationLoaded('currentStage')) {
            $this->load('currentStage');
        }
        
        return $this->currentStage?->name ?? 'Unknown';
    }
    
    /**
     * Get the expected harvest date for display purposes.
     * 
     * @return \Carbon\Carbon|null
     */
    public function getExpectedHarvestAtAttribute(): ?\Carbon\Carbon
    {
        return $this->expectedHarvestDate();
    }
    
    /**
     * Calculate the remaining soaking time based on recipe seed_soak_hours.
     * 
     * @return int|null Minutes remaining, or null if not soaking or no recipe
     */
    public function getSoakingTimeRemaining(): ?int
    {
        if (!$this->isActivelySoaking() || !$this->recipe || !$this->recipe->seed_soak_hours) {
            return null;
        }
        
        $soakingDurationMinutes = $this->recipe->seed_soak_hours * 60;
        $elapsedMinutes = $this->soaking_at->diffInMinutes(Carbon::now());
        $remainingMinutes = $soakingDurationMinutes - $elapsedMinutes;
        
        return max(0, (int)$remainingMinutes);
    }
    
    /**
     * Get the total soaking duration from the recipe.
     * 
     * @return int|null Total soaking duration in minutes, or null if no recipe or no soaking required
     */
    public function getSoakingDuration(): ?int
    {
        if (!$this->recipe || !$this->recipe->seed_soak_hours) {
            return null;
        }
        
        return $this->recipe->seed_soak_hours * 60;
    }
    
    // Business logic methods removed - use CropLifecycleService directly
    
    /**
     * Determine the next logical stage in the growth cycle.
     *
     * @return string|null The name of the next stage, or null if harvested or invalid.
     */
    public function getNextStage(): ?CropStage
    {
        if (!$this->currentStage) {
            return null;
        }

        // Load recipe if not already loaded
        if (!$this->relationLoaded('recipe')) {
            $this->load('recipe');
        }

        // Use recipe-aware next stage logic to skip stages with 0 days
        return $this->currentStage->getNextViableStage($this->recipe);
    }
    
    /**
     * Determine the previous stage in the growth cycle.
     *
     * @return string|null The name of the previous stage, or null if at first stage.
     */
    public function getPreviousStage(): ?CropStage
    {
        return $this->currentStage?->getPreviousStage();
    }
    
    // Calculation methods removed - use CropLifecycleService directly
    
    /**
     * Get the attributes that should be logged.
     */
    protected function getLogAttributes(): array
    {
        return [
            'recipe_id', 
            'order_id', 
            'tray_number',
            'current_stage_id',
            'germination_at',
            'blackout_at',
            'light_at',
            'harvested_at',
                'watering_suspended_at',
            'soaking_at',
            'requires_soaking'
        ];
    }

    /**
     * Get the relationships that should be logged with this model.
     */
    public function getLoggedRelationships(): array
    {
        return ['recipe', 'order', 'currentStage', 'cropPlan'];
    }

    /**
     * Get specific attributes to include from related models.
     */
    public function getRelationshipAttributesToLog(): array
    {
        return [
            'recipe' => ['id', 'name', 'code', 'germination_days', 'blackout_days', 'light_days'],
            'order' => ['id', 'customer_id', 'delivery_date', 'status'],
            'currentStage' => ['id', 'name', 'code', 'days_duration'],
            'cropPlan' => ['id', 'name', 'start_date', 'end_date'],
        ];
    }
    
    
    /**
     * Boot method to add lifecycle hooks
     */
    protected static function booted()
    {
        // Initialize new crops with default values
        static::creating(function (Crop $crop) {
            /** @var CropValidationService $validationService */
            $validationService = app(CropValidationService::class);
            $validationService->initializeNewCrop($crop);
        });

        // Note: Time calculations are now handled by CropObserver for consistency
        // This prevents duplicate calculation systems from conflicting
        
        // Temporarily disabled - causes format() error
        // static::created(function ($crop) {
        //     /** @var CropValidationService $validationService */
        //     $validationService = app(CropValidationService::class);
        //     $validationService->handleCropCreated($crop);
        // });
        
        static::saving(function ($crop) {
            // Validate timestamp sequence for manual edits
            if (!$crop->exists || $crop->isDirty(['germination_at', 'blackout_at', 'light_at', 'harvested_at'])) {
                /** @var CropValidationService $validationService */
                $validationService = app(CropValidationService::class);
                $validationService->validateTimestampSequence($crop);
            }
        });
        
        static::updating(function ($crop) {
            /** @var CropValidationService $validationService */
            $validationService = app(CropValidationService::class);
            $validationService->adjustStageTimestamps($crop);
        });
        
        static::updated(function ($crop) {
            app(CropValidationService::class)->handleCropUpdated($crop);
        });
    }
    
    // Stage management methods removed - use CropLifecycleService directly
    
    /**
     * Calculate the time remaining until the next stage.
     * 
     * @return string|null Formatted time string or status message
     */
    public function timeToNextStage(): ?string
    {
        // Calculate dynamically using CropTimeCalculator
        return app(CropTimeCalculator::class)->getTimeToNextStageDisplay($this);
    }
    
    
    // Time status methods removed - use CropTimeCalculator directly

    /**
     * Check if we're in bulk operation mode
     * 
     * @return bool
     */
    public static function isInBulkOperation(): bool
    {
        return self::$bulkOperation;
    }

    /**
     * Check if the crop is ready to harvest.
     * A crop is ready to harvest when it's in the "light" stage.
     * 
     * @return bool
     */
    public function isReadyToHarvest(): bool
    {
        // The current_stage_code attribute returns a string code, not the object
        return $this->current_stage_code === 'light';
    }

    /**
     * Calculate the expected harvest date for this crop.
     * 
     * @return \Carbon\Carbon|null
     */
    public function expectedHarvestDate(): ?\Carbon\Carbon
    {
        $taskManagementService = app(\App\Services\CropTaskManagementService::class);
        return $taskManagementService->calculateExpectedHarvestDate($this);
    }

    /**
     * Advance this crop to the next stage
     * Delegates to CropStageTransitionService to maintain consistency
     * 
     * @param \Carbon\Carbon|null $timestamp When the transition occurred (defaults to now)
     * @return void
     */
    public function advanceStage(?\Carbon\Carbon $timestamp = null): void
    {
        $transitionService = app(\App\Services\CropStageTransitionService::class);
        $transitionService->advanceStage($this, $timestamp ?? \Carbon\Carbon::now());
    }

}
