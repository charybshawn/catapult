<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural crop growth stage management model for production workflow orchestration.
 * 
 * Defines standardized growth stages for agricultural production including soaking,
 * germination, blackout, light, and harvest phases. Manages stage transitions,
 * environmental requirements, and timing for coordinated crop production workflows.
 * 
 * @property int $id Primary key identifier
 * @property string $code Unique stage code for programmatic identification (soaking, germination, etc.)
 * @property string $name Human-readable stage name for display
 * @property string|null $description Detailed stage description and requirements
 * @property string|null $color UI color code for visual stage identification
 * @property bool $is_active Stage availability status for production workflows
 * @property int $sort_order Stage sequence ordering in production workflow
 * @property int|null $typical_duration_days Standard duration in days for this stage
 * @property bool $requires_light Whether this stage requires light exposure
 * @property bool $requires_watering Whether this stage requires watering
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Crop> $crops
 * @property-read int|null $crops_count
 * 
 * @agricultural_context Manages microgreens production stages from seed soaking through harvest
 * @business_rules Stages follow sequential workflow with configurable timing and skipping
 * @workflow_management Enables automated stage transitions and environmental control
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class CropStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
        'typical_duration_days',
        'requires_light',
        'requires_watering',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'typical_duration_days' => 'integer',
        'requires_light' => 'boolean',
        'requires_watering' => 'boolean',
    ];

    /**
     * Get all crops currently in this growth stage.
     * 
     * Retrieves crops that are currently at this stage in their production
     * lifecycle for monitoring and management purposes.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Crop>
     * @agricultural_context Returns crops requiring stage-specific care and monitoring
     * @business_usage Used for stage-based reporting and operational management
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'current_stage_id');
    }

    /**
     * Get options for select fields (active stages only).
     * 
     * Returns formatted array of active crop stages suitable for form dropdowns
     * and UI selection components, ordered by workflow sequence.
     * 
     * @return array<int, string> Array with stage IDs as keys and names as values
     * @agricultural_context Provides UI options for crop stage transitions
     * @ui_usage Used in Filament forms for stage selection and transitions
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all active crop stages query builder.
     * 
     * Returns query builder for retrieving only active stages, ordered by
     * workflow sequence and name for consistent production operations.
     * 
     * @return \Illuminate\Database\Eloquent\Builder Query builder for active stages
     * @agricultural_context Filters to operational production stages only
     * @usage_pattern Commonly used for workflow management and stage transitions
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find crop stage by unique code identifier.
     * 
     * Locates specific stage using programmatic code for workflow automation
     * and consistent stage identification across agricultural operations.
     * 
     * @param string $code Unique stage code (soaking, germination, blackout, light, harvested)
     * @return static|null Stage instance or null if not found
     * @agricultural_context Enables programmatic stage identification for automated workflows
     * @usage_pattern Used for stage transitions, workflow automation, and system integrations
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is the soaking stage.
     * 
     * Determines if this stage represents the initial seed soaking phase
     * where seeds are prepared for germination.
     * 
     * @return bool True if this is soaking stage
     * @agricultural_context Soaking prepares seeds for germination and requires timing control
     * @business_logic First stage in production workflow requiring water but no light
     */
    public function isSoaking(): bool
    {
        return $this->code === 'soaking';
    }

    /**
     * Check if this is the germination stage.
     * 
     * Determines if this stage represents the germination phase where
     * seeds develop initial sprouts and root systems.
     * 
     * @return bool True if this is germination stage
     * @agricultural_context Germination requires controlled environment and moisture
     * @business_logic Early growth stage requiring careful environmental control
     */
    public function isGermination(): bool
    {
        return $this->code === 'germination';
    }

    /**
     * Check if this is the blackout stage.
     * 
     * Determines if this stage represents the blackout phase where
     * crops grow in darkness to develop stems and structure.
     * 
     * @return bool True if this is blackout stage
     * @agricultural_context Blackout promotes stem elongation in microgreens production
     * @business_logic Optional stage that can be skipped based on variety requirements
     */
    public function isBlackout(): bool
    {
        return $this->code === 'blackout';
    }

    /**
     * Check if this is the light stage.
     * 
     * Determines if this stage represents the light exposure phase where
     * crops develop chlorophyll and nutritional content under light.
     * 
     * @return bool True if this is light stage
     * @agricultural_context Light stage develops flavor, color, and nutritional density
     * @business_logic Final growth stage before harvest requiring light exposure
     */
    public function isLight(): bool
    {
        return $this->code === 'light';
    }

    /**
     * Check if this is the harvested stage.
     * 
     * Determines if this stage represents completion of production cycle
     * where crops are ready for or have been harvested.
     * 
     * @return bool True if this is harvested stage
     * @agricultural_context Harvested stage marks completion of production cycle
     * @business_logic Final stage indicating crops are ready for sale or processing
     */
    public function isHarvested(): bool
    {
        return $this->code === 'harvested';
    }

    /**
     * Check if this stage is a pre-harvest production stage.
     * 
     * Determines if this stage occurs before harvest, indicating crops
     * are still in active production and require ongoing care.
     * 
     * @return bool True if stage is before harvest
     * @agricultural_context Pre-harvest stages require active monitoring and care
     * @business_logic Used for production planning and resource allocation
     */
    public function isPreHarvest(): bool
    {
        return !$this->isHarvested();
    }

    /**
     * Check if this stage is the first stage in production workflow.
     * 
     * Determines if this is the initial stage where crops begin production,
     * with backward compatibility for systems with or without soaking stage.
     * 
     * @return bool True if this is first stage
     * @agricultural_context First stage determines production start timing
     * @business_logic Soaking preferred, falls back to germination for compatibility
     */
    public function isFirstStage(): bool
    {
        // Check for soaking first, then fall back to germination for backward compatibility
        if ($this->isSoaking()) {
            return true;
        }
        
        // If soaking stage doesn't exist, germination is the first stage
        $soakingStage = static::findByCode('soaking');
        if (!$soakingStage) {
            return $this->isGermination();
        }
        
        return false;
    }

    /**
     * Check if this stage is the final stage in production workflow.
     * 
     * Determines if this represents completion of production cycle
     * and no further stage transitions are possible.
     * 
     * @return bool True if this is final stage
     * @agricultural_context Final stage indicates production completion
     * @business_logic Used for harvest planning and inventory management
     */
    public function isFinalStage(): bool
    {
        return $this->isHarvested();
    }

    /**
     * Get the next stage in the production workflow sequence.
     * 
     * Retrieves the immediate next stage based on sort order for
     * standard workflow progression in agricultural production.
     * 
     * @return static|null Next stage instance or null if this is final stage
     * @agricultural_context Enables sequential workflow progression
     * @business_usage Used for automated stage transitions and planning
     */
    public function getNextStage(): ?self
    {
        return static::where('sort_order', '>', $this->sort_order)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Get the next viable stage based on recipe timing, skipping zero-duration stages.
     * 
     * Determines the next appropriate stage considering recipe-specific timing,
     * automatically skipping stages with zero duration or invalid configurations.
     * 
     * @param mixed $recipe Recipe instance with stage timing configurations
     * @return static|null Next viable stage or null if no valid progression exists
     * @agricultural_context Enables recipe-specific workflow customization
     * @business_logic Skips blackout stage if recipe specifies zero blackout days
     * @workflow_automation Used for intelligent stage progression based on variety requirements
     */
    public function getNextViableStage($recipe): ?self
    {
        $currentStage = $this;
        $nextStage = $this->getNextStage();
        
        // Handle soaking → germination transition
        if ($this->isSoaking()) {
            $germinationStage = static::findByCode('germination');
            if ($germinationStage) {
                return $germinationStage;
            }
        }
        
        while ($nextStage) {
            // Check if this stage should be skipped based on recipe
            $shouldSkip = false;
            
            if ($nextStage->code === 'blackout' && ($recipe->blackout_days ?? 0) <= 0) {
                $shouldSkip = true;
            }
            
            if (!$shouldSkip) {
                return $nextStage;
            }
            
            // This stage should be skipped, check the next one
            $nextStage = $nextStage->getNextStage();
        }
        
        return null;
    }

    /**
     * Get the previous stage in the workflow.
     */
    public function getPreviousStage(): ?self
    {
        return static::where('sort_order', '<', $this->sort_order)
            ->where('is_active', true)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * Check if this stage can transition to target stage.
     * 
     * Validates whether transition from current stage to target stage
     * follows proper workflow progression rules.
     * 
     * @param \App\Models\CropStage $targetStage Target stage for transition
     * @return bool True if transition is allowed
     * @agricultural_context Prevents invalid workflow progressions
     * @business_rule Stages generally progress in sequential sort order
     */
    public function canTransitionTo(CropStage $targetStage): bool
    {
        // Generally, stages should progress in order
        return $targetStage->sort_order > $this->sort_order;
    }

    /**
     * Get environmental requirements for this production stage.
     * 
     * Returns structured array of environmental conditions and timing
     * requirements for proper crop development in this stage.
     * 
     * @return array Environmental requirements including light, watering, and duration
     * @agricultural_context Defines conditions needed for optimal crop development
     * @business_usage Used for environmental control and production planning
     */
    public function getEnvironmentalRequirements(): array
    {
        return [
            'light' => $this->requires_light,
            'watering' => $this->requires_watering,
            'typical_duration_days' => $this->typical_duration_days,
        ];
    }
}