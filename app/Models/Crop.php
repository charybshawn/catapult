<?php

namespace App\Models;

use App\Services\CropTaskManagementService;
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

/**
 * Agricultural Crop Production Model for Catapult Microgreens System
 *
 * Represents individual crop batches in the microgreens agricultural workflow,
 * tracking the complete lifecycle from seed soaking through germination, blackout,
 * light exposure, to final harvest. Each crop represents a specific variety
 * grown for a customer order following precise agricultural timing and procedures.
 *
 * @property int $id Primary key identifier
 * @property int|null $crop_batch_id Batch grouping for multiple related crops
 * @property int|null $recipe_id Growing recipe with agricultural parameters
 * @property int|null $order_id Customer order this crop fulfills
 * @property int|null $crop_plan_id Production planning schedule reference
 * @property string|null $tray_number Physical tray identifier for tracking
 * @property int|null $tray_count Number of trays for this crop batch
 * @property int|null $current_stage_id Current agricultural growth stage
 * @property Carbon|null $germination_at When germination phase started
 * @property Carbon|null $blackout_at When blackout phase started
 * @property Carbon|null $light_at When light exposure phase started
 * @property Carbon|null $harvested_at When crop was harvested
 * @property Carbon|null $watering_suspended_at When watering was suspended
 * @property Carbon|null $soaking_at When seed soaking began
 * @property bool $requires_soaking Whether variety requires pre-germination soaking
 * @property string|null $notes Agricultural notes and observations
 *
 * @property-read string $variety_name Variety name from recipe relationship
 * @property-read string $current_stage Current stage code for compatibility
 * @property-read string $current_stage_name Display name for current stage
 * @property-read Carbon|null $expected_harvest_at Calculated harvest date
 *
 * @relationship cropBatch BelongsTo CropBatch for grouped crop management
 * @relationship recipe BelongsTo Recipe agricultural growing parameters
 * @relationship order BelongsTo Order customer order fulfillment
 * @relationship cropPlan BelongsTo CropPlan production planning schedule
 * @relationship currentStage BelongsTo CropStage current growth phase
 * @relationship harvests BelongsToMany Harvest records with yield data
 *
 * @business_rule Crops progress through required agricultural stages: soaking → germination → blackout → light → harvested
 * @business_rule Stage transitions must follow chronological order (each timestamp ≥ previous)
 * @business_rule Soaking stage is optional and variety-dependent
 * @business_rule Stage timing follows recipe parameters for variety-specific growing requirements
 *
 * @agricultural_context Each crop represents a physical growing batch following precise
 * microgreens agricultural methodology. Timing is critical - germination must occur in darkness,
 * blackout period allows proper root development, light exposure triggers chlorophyll production,
 * and harvest timing determines product quality and shelf life.
 *
 * @usage_example
 * // Create crop for order
 * $crop = Crop::create([
 *     'recipe_id' => $peaShootRecipe->id,
 *     'order_id' => $order->id,
 *     'tray_number' => 'T-001',
 *     'requires_soaking' => true
 * ]);
 *
 * // Advance through agricultural stages
 * $crop->soaking_at = now();
 * $crop->germination_at = now()->addHours(8);
 * $crop->blackout_at = now()->addDays(3);
 * $crop->light_at = now()->addDays(6);
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 2.0.0
 */
class Crop extends Model
{
    use HasFactory, ExtendedLogsActivity;
    
    /**
     * Flag to prevent recursive model events during bulk operations.
     *
     * Used to disable model event triggers during bulk agricultural operations
     * like batch crop creation or stage transitions to prevent performance
     * issues and recursive validation loops.
     *
     * @var bool
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
        'tray_count',
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
    protected $appends = ['variety_name', 'current_stage'];
    
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
     *
     * Relationship to crop batch grouping system for managing multiple
     * related crops as a cohesive agricultural unit. Batches enable
     * synchronized stage transitions and coordinated harvest scheduling
     * across multiple trays of the same variety.
     *
     * @return BelongsTo<CropBatch> Crop batch grouping
     */
    public function cropBatch(): BelongsTo
    {
        return $this->belongsTo(CropBatch::class);
    }
    
    /**
     * Get the agricultural growing recipe for this crop.
     *
     * Relationship to recipe containing variety-specific agricultural parameters:
     * germination time, blackout duration, light exposure period, watering
     * schedules, and harvest timing. Recipe drives the entire agricultural
     * production workflow for this crop.
     *
     * @return BelongsTo<Recipe> Agricultural growing parameters and timing
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
    
    /**
     * Get the customer order for this crop.
     *
     * Relationship to the customer order this crop is grown to fulfill.
     * Links agricultural production directly to customer delivery requirements
     * and enables harvest timing coordination with delivery schedules.
     *
     * @return BelongsTo<Order> Customer order fulfilled by this crop
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the production plan for this crop.
     *
     * Relationship to agricultural production planning schedule that coordinates
     * planting timing with harvest dates and delivery requirements. Plans
     * account for variety-specific growing periods and production capacity.
     *
     * @return BelongsTo<CropPlan> Agricultural production planning schedule
     */
    public function cropPlan(): BelongsTo
    {
        return $this->belongsTo(CropPlan::class);
    }
    
    /**
     * Get the current agricultural growth stage for this crop.
     *
     * Relationship to the current stage in the agricultural lifecycle
     * (soaking, germination, blackout, light, harvested). Stage determines
     * required agricultural activities, environmental conditions, and timing
     * for next stage transition.
     *
     * @return BelongsTo<CropStage> Current agricultural growth stage
     */
    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(CropStage::class, 'current_stage_id');
    }
    
    /**
     * Get the harvest records for this crop.
     *
     * Many-to-many relationship to harvest events with pivot data for
     * harvested weight, percentage harvested, and harvest-specific notes.
     * Supports partial harvests and multiple harvest sessions from single
     * crop for agricultural yield optimization.
     *
     * @return BelongsToMany<Harvest> Harvest records with yield data
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
     * Enable bulk operation mode to prevent recursive events.
     *
     * Disables model event triggers during bulk agricultural operations
     * like batch crop creation or synchronized stage transitions.
     * Essential for performance optimization during high-volume operations.
     *
     * @return void
     */
    public static function enableBulkOperation(): void
    {
        self::$bulkOperation = true;
    }
    
    /**
     * Disable bulk operation mode to re-enable events.
     *
     * Re-enables model event triggers after bulk agricultural operations
     * complete. Ensures normal validation and lifecycle management
     * resume for individual crop operations.
     *
     * @return void
     */
    public static function disableBulkOperation(): void
    {
        self::$bulkOperation = false;
    }
    
    /**
     * Get the variety name for this crop.
     *
     * Accessor that retrieves variety name from the associated recipe's
     * cultivar information. Uses eager loading optimization to prevent
     * N+1 queries in agricultural crop listing and management interfaces.
     *
     * @return string|null Variety name or null if no recipe assigned
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
     *
     * Maps the current_stage_id to stage code for legacy compatibility.
     * Provides string-based stage codes (soaking, germination, blackout, light, harvested)
     * while maintaining support for the new stage relationship system.
     *
     * @return string Current agricultural stage code
     */
    public function getCurrentStageAttribute(): string
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
     * Set the current_stage attribute for backward compatibility.
     *
     * Maps stage code to current_stage_id for legacy compatibility.
     * Enables setting agricultural stages using string codes while
     * maintaining the underlying relational stage system.
     *
     * @param string $value Agricultural stage code to set
     * @return void
     */
    public function setCurrentStageAttribute(string $value): void
    {
        $stage = CropStage::findByCode($value);
        if ($stage) {
            $this->current_stage_id = $stage->id;
        }
    }
    
    /**
     * Get tray object for dashboard template compatibility.
     *
     * Creates anonymous object wrapper around tray_number string field
     * to maintain compatibility with dashboard templates expecting
     * tray object with tray_number property.
     *
     * @return object Anonymous object with tray_number property
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
     *
     * Determines if agricultural watering has been suspended for this crop,
     * typically during problems, disease management, or harvest preparation.
     * Used for agricultural task management and crop care protocols.
     *
     * @return bool True if watering is currently suspended
     */
    public function isWateringSuspended(): bool
    {
        return $this->watering_suspended_at !== null;
    }
    
    /**
     * Check if the crop is currently in the soaking stage.
     *
     * Determines if crop is actively soaking seeds based on:
     * - Variety requires soaking (requires_soaking = true)
     * - Soaking has started (soaking_at is set)
     * - Germination hasn't started or is scheduled for future
     *
     * Essential for agricultural task management and stage transition timing.
     *
     * @return bool True if actively soaking seeds
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
     * Provides alternative method name for backward compatibility with
     * existing agricultural workflow actions and user interfaces.
     *
     * @return bool True if actively soaking seeds
     */
    public function isInSoaking(): bool
    {
        return $this->isActivelySoaking();
    }
    
    /**
     * Get the current stage name for display purposes.
     *
     * Provides human-readable stage name for agricultural workflow interfaces
     * and customer communications. Uses eager loading optimization to prevent
     * N+1 queries when displaying multiple crops.
     *
     * @return string Current stage display name
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
     * Accessor for calculated harvest date based on agricultural timing
     * and recipe parameters. Used for harvest planning, customer
     * communication, and agricultural workflow coordination.
     *
     * @return Carbon|null Expected harvest date or null if cannot be determined
     */
    public function getExpectedHarvestAtAttribute(): ?Carbon
    {
        return $this->expectedHarvestDate();
    }
    
    /**
     * Calculate the remaining soaking time based on recipe seed_soak_hours.
     *
     * Computes remaining time in soaking phase based on variety-specific
     * soaking requirements from recipe. Essential for timing germination
     * phase transition and agricultural task scheduling.
     *
     * @return int|null Minutes remaining in soaking phase, or null if not applicable
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
     * Retrieves variety-specific soaking duration from agricultural recipe.
     * Used for calculating soaking progress, scheduling germination transitions,
     * and agricultural task management across different varieties.
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
     * Determine the next logical stage in the agricultural growth cycle.
     *
     * Retrieves the next stage in the agricultural progression sequence
     * based on current stage and variety-specific requirements. Used for
     * stage transition validation and agricultural workflow planning.
     *
     * @return CropStage|null Next agricultural stage or null if at final stage
     */
    public function getNextStage(): ?CropStage
    {
        return $this->currentStage?->getNextStage();
    }
    
    /**
     * Determine the previous stage in the agricultural growth cycle.
     *
     * Retrieves the previous stage in the agricultural progression sequence.
     * Used for stage rollback operations and agricultural workflow debugging.
     *
     * @return CropStage|null Previous agricultural stage or null if at first stage
     */
    public function getPreviousStage(): ?CropStage
    {
        return $this->currentStage?->getPreviousStage();
    }
    
    // Calculation methods removed - use CropLifecycleService directly
    
    /**
     * Get the attributes that should be logged.
     *
     * Defines critical agricultural attributes for activity logging including
     * stage transitions, timing data, and production metadata. Essential for
     * agricultural audit trails and crop lifecycle tracking.
     *
     * @return array<string> Attributes to include in activity logs
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
     *
     * Defines which related models should be included in activity logging
     * when this crop is modified. Provides comprehensive agricultural
     * audit trails linking crops to recipes, orders, and planning data.
     *
     * @return array<string> Array of relationship method names to log
     */
    public function getLoggedRelationships(): array
    {
        return ['recipe', 'order', 'currentStage', 'cropPlan'];
    }

    /**
     * Get specific attributes to include from related models.
     *
     * Specifies which attributes from related models should be captured
     * in activity logs. Optimizes log storage while maintaining sufficient
     * agricultural context for crop lifecycle analysis and troubleshooting.
     *
     * @return array<string, array<string>> Relationship => [attributes] mapping
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
     * Configure model event listeners for agricultural crop lifecycle.
     *
     * Implements critical agricultural business logic and automated workflows:
     * - Initializes new crops with proper default values and stage settings
     * - Validates agricultural timing and stage progression rules
     * - Updates time calculations for stage transition planning
     * - Handles crop batch coordination and stage synchronization
     * - Manages agricultural task scheduling and notifications
     *
     * Delegates complex logic to specialized services for maintainability.
     *
     * @return void
     */
    protected static function booted()
    {
        // Initialize new crops with default values
        static::creating(function (Crop $crop) {
            /** @var CropValidationService $validationService */
            $validationService = app(CropValidationService::class);
            $validationService->initializeNewCrop($crop);
        });
        
        // Add event listeners to recalculate time_to_next_stage values
        static::saving(function (Crop $crop) {
            // Calculate and update the time_to_next_stage values whenever the model is saved
            if (!$crop->exists || $crop->isDirty(['current_stage_id', 'germination_at', 'blackout_at', 'light_at', 'harvested_at'])) {
                /** @var CropTimeCalculator $timeCalculator */
                $timeCalculator = app(CropTimeCalculator::class);
                $timeCalculator->updateTimeCalculations($crop);
            }
        });
        
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
     * Calculate the time remaining until the next agricultural stage.
     *
     * Provides formatted time display for next stage transition based on
     * recipe timing and current stage progress. Attempts to use optimized
     * batch view data first, falls back to dynamic calculation.
     *
     * Essential for agricultural task scheduling and workflow management.
     *
     * @return string|null Formatted time string or status message
     */
    public function timeToNextStage(): ?string
    {
        // Get from the batch view if available
        if ($this->crop_batch_id) {
            $batchView = CropBatchListView::where('id', $this->crop_batch_id)->first();
            if ($batchView) {
                return $batchView->time_to_next_stage_display;
            }
        }
        
        // Otherwise calculate dynamically
        return app(CropTimeCalculator::class)->getTimeToNextStageDisplay($this);
    }
    
    
    // Time status methods removed - use CropTimeCalculator directly

    /**
     * Check if we're in bulk operation mode.
     *
     * Determines if model events are currently disabled for bulk agricultural
     * operations. Used by services to adjust behavior during high-volume
     * operations like batch crop creation or synchronized stage transitions.
     *
     * @return bool True if bulk operations are active
     */
    public static function isInBulkOperation(): bool
    {
        return self::$bulkOperation;
    }

    /**
     * Check if the crop is ready to harvest.
     *
     * Determines harvest readiness based on agricultural stage progression.
     * A crop is ready to harvest when it has completed the light exposure
     * phase and achieved proper chlorophyll development for microgreens quality.
     *
     * @return bool True if crop is in 'light' stage and ready for harvest
     */
    public function isReadyToHarvest(): bool
    {
        // The current_stage attribute returns a string code, not the object
        return $this->current_stage === 'light';
    }

    /**
     * Calculate the expected harvest date for this crop.
     *
     * Computes projected harvest date based on agricultural timing calculations
     * from recipe parameters, current stage progression, and environmental factors.
     * Delegates to CropTaskManagementService for complex agricultural scheduling logic.
     *
     * Essential for delivery planning, customer communication, and agricultural
     * workflow coordination across multiple crops and orders.
     *
     * @return Carbon|null Expected harvest date or null if cannot be calculated
     */
    public function expectedHarvestDate(): ?Carbon
    {
        $taskManagementService = app(CropTaskManagementService::class);
        return $taskManagementService->calculateExpectedHarvestDate($this);
    }

}
