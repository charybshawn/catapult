<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Services\CropLifecycleService;
use App\Services\CropTimeCalculator;
use App\Models\SeedEntry;

class Crop extends Model
{
    use HasFactory, LogsActivity;
    
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
        'recipe_id',
        'order_id',
        'crop_plan_id',
        'tray_number',
        'current_stage',
        'planting_at',
        'germination_at',
        'blackout_at',
        'light_at',
        'harvested_at',
        'harvest_weight_grams',
        'watering_suspended_at',
        'notes',
        'time_to_next_stage_minutes',
        'time_to_next_stage_display',
        'stage_age_minutes',
        'stage_age_display',
        'total_age_minutes',
        'total_age_display',
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
        'planting_at' => 'datetime',
        'germination_at' => 'datetime',
        'blackout_at' => 'datetime',
        'light_at' => 'datetime',
        'harvested_at' => 'datetime',
        'watering_suspended_at' => 'datetime',
        'harvest_weight_grams' => 'float',
        'tray_number' => 'string',
    ];
    
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
     * Get the seed cultivar for this crop through the recipe.
     */
    public function seedEntry(): ?SeedEntry
    {
        // Ensure recipe relationship is loaded to avoid lazy loading
        if (!$this->relationLoaded('recipe')) {
            $this->load('recipe.seedEntry');
        }
        
        if ($this->recipe) {
            return $this->recipe->seedEntry;
        }
        return null;
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
            $this->load('recipe.seedEntry');
        }
        
        if ($this->recipe && $this->recipe->seedEntry) {
            return $this->recipe->seedEntry->cultivar_name;
        }
        
        return null;
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
     * Suspend watering.
     */
    public function suspendWatering(): void
    {
        app(CropLifecycleService::class)->suspendWatering($this);
    }
    
    /**
     * Resume watering.
     */
    public function resumeWatering(): void
    {
        app(CropLifecycleService::class)->resumeWatering($this);
    }
    
    /**
     * Advance to the next stage.
     */
    public function advanceStage(): void
    {
        app(CropLifecycleService::class)->advanceStage($this);
    }
    
    /**
     * Determine the next logical stage in the growth cycle.
     *
     * @return string|null The name of the next stage, or null if harvested or invalid.
     */
    public function getNextStage(): ?string
    {
        $order = ['germination', 'blackout', 'light', 'harvested'];
        $currentIndex = array_search($this->current_stage, $order);

        // Handle cases where blackout might be skipped
        if ($this->current_stage === 'germination' && $this->recipe && $this->recipe->blackout_days <= 0) {
            return 'light'; // Skip blackout if duration is 0
        }

        if ($currentIndex === false || $currentIndex >= count($order) - 1) {
            return null; // Already harvested or invalid stage
        }
        
        return $order[$currentIndex + 1];
    }
    
    /**
     * Calculate the expected harvest date.
     */
    public function expectedHarvestDate(): ?Carbon
    {
        return app(CropLifecycleService::class)->calculateExpectedHarvestDate($this);
    }
    
    /**
     * Calculate days in current stage.
     */
    public function daysInCurrentStage(): int
    {
        return app(CropLifecycleService::class)->calculateDaysInCurrentStage($this);
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'recipe_id', 
                'order_id', 
                'tray_number',
                'current_stage',
                'planting_at',
                'germination_at',
                'blackout_at',
                'light_at',
                'harvested_at',
                'harvest_weight_grams',
                'watering_suspended_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    /**
     * Boot method to add lifecycle hooks
     */
    protected static function booted()
    {
        // Add our existing boot logic
        static::creating(function (Crop $crop) {
            // Set planting_at if not provided
            if (!$crop->planting_at) {
                $crop->planting_at = now();
            }
            
            // Set germination_at and current_stage to germination automatically
            if ($crop->planting_at && !$crop->germination_at) {
                $crop->germination_at = $crop->planting_at;
            }
            
            // Always start at germination stage if not set
            if (!$crop->current_stage) {
                $crop->current_stage = 'germination';
            }
            
            // Initialize computed time fields with safe values
            if (!isset($crop->time_to_next_stage_minutes)) {
                $crop->time_to_next_stage_minutes = 0;
            }
            if (!isset($crop->time_to_next_stage_display)) {
                $crop->time_to_next_stage_display = 'Unknown';
            }
            if (!isset($crop->stage_age_minutes)) {
                $crop->stage_age_minutes = 0;
            }
            if (!isset($crop->stage_age_display)) {
                $crop->stage_age_display = '0m';
            }
            if (!isset($crop->total_age_minutes)) {
                $crop->total_age_minutes = 0;
            }
            if (!isset($crop->total_age_display)) {
                $crop->total_age_display = '0m';
            }
        });
        
        // Add event listeners to recalculate time_to_next_stage values
        static::saving(function (Crop $crop) {
            // Calculate and update the time_to_next_stage values whenever the model is saved
            $crop->updateTimeToNextStageValues();
        });
        
        static::created(function ($crop) {
            // Automatically deduct seed from inventory when crop is created
            if (!self::$bulkOperation && $crop->recipe && $crop->recipe->seedConsumable && $crop->recipe->seed_density_grams_per_tray) {
                try {
                    $seedConsumable = $crop->recipe->seedConsumable;
                    $requiredAmount = $crop->recipe->seed_density_grams_per_tray;
                    
                    // Convert required amount to the same unit as the seed consumable for comparison
                    $inventoryService = app(\App\Services\InventoryService::class);
                    $currentStock = $inventoryService->getCurrentStock($seedConsumable);
                    
                    // Check if we have enough seed (convert units if needed for comparison)
                    $requiredInSeedUnits = match($seedConsumable->quantity_unit) {
                        'kg' => $requiredAmount / 1000, // Convert grams to kg
                        'g' => $requiredAmount, // Already in grams
                        default => $requiredAmount // For other units, assume direct comparison
                    };
                    
                    if ($currentStock >= $requiredInSeedUnits) {
                        // Deduct the seed amount specified in the recipe for this tray
                        $seedConsumable->deduct(
                            $requiredAmount,
                            'g' // Recipe seed density is always in grams
                        );
                        
                        \Illuminate\Support\Facades\Log::info('Seed automatically deducted for new crop', [
                            'crop_id' => $crop->id,
                            'recipe_id' => $crop->recipe_id,
                            'seed_consumable_id' => $seedConsumable->id,
                            'amount_deducted' => $requiredAmount,
                            'unit' => 'g',
                            'remaining_stock' => $currentStock - $requiredInSeedUnits
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Insufficient seed stock for crop creation', [
                            'crop_id' => $crop->id,
                            'recipe_id' => $crop->recipe_id,
                            'seed_consumable_id' => $seedConsumable->id,
                            'required_amount' => $requiredAmount,
                            'current_stock' => $currentStock,
                            'seed_unit' => $seedConsumable->quantity_unit
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Error deducting seed inventory for new crop', [
                        'crop_id' => $crop->id,
                        'recipe_id' => $crop->recipe_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Schedule stage transition tasks (skip during testing to avoid memory issues)
            if (config('app.env') !== 'testing' && !self::$bulkOperation) {
                try {
                    app(\App\Services\CropTaskService::class)->scheduleAllStageTasks($crop);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Error scheduling crop tasks', [
                        'crop_id' => $crop->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
        
        static::saving(function ($crop) {
            // Validate timestamp sequence for manual edits (both new crops and timestamp updates)
            if (!$crop->exists || $crop->isDirty(['planting_at', 'germination_at', 'blackout_at', 'light_at', 'harvested_at'])) {
                try {
                    static::validateTimestampSequence($crop);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            }
        });
        
        static::updating(function ($crop) {
            // If planting_at has changed, recalculate stage dates
            if ($crop->isDirty('planting_at') && $crop->recipe) {
                try {
                    // Get original planting_at
                    $originalPlantingAt = $crop->getOriginal('planting_at');
                    
                    // Get the new planting_at
                    $newPlantingAt = $crop->planting_at;
                    
                    // Calculate time difference in minutes
                    $originalDateTime = new \Carbon\Carbon($originalPlantingAt);
                    $timeDiff = $originalDateTime->diffInMinutes($newPlantingAt, false);
                    
                    // Adjust all stage timestamps by the same amount
                    foreach (['germination_at', 'blackout_at', 'light_at'] as $stageField) {
                        if ($crop->$stageField) {
                            $stageTime = new \Carbon\Carbon($crop->$stageField);
                            $crop->$stageField = $stageTime->addMinutes($timeDiff);
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Error updating crop stage dates', [
                        'crop_id' => $crop->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
        
        static::updated(function ($crop) {
            // If the stage has changed or planting_at has changed, recalculate tasks
            if (($crop->isDirty('current_stage') || $crop->isDirty('planting_at')) && 
                config('app.env') !== 'testing') {
                try {
                    app(\App\Services\CropTaskService::class)->scheduleAllStageTasks($crop);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Error rescheduling crop tasks', [
                        'crop_id' => $crop->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }
    
    /**
     * Reset to a specific stage and clear future stage timestamps.
     */
    public function resetToStage(string $newStage): void
    {
        app(CropLifecycleService::class)->resetToStage($this, $newStage);
    }
    
    /**
     * Calculate the time remaining until the next stage.
     * 
     * @return string|null Formatted time string or status message
     */
    public function timeToNextStage(): ?string
    {
        // Skip if already harvested
        if ($this->current_stage === 'harvested') {
            return '-';
        }
        
        if (!$this->recipe) {
            return 'No recipe';
        }
        
        // Get the timestamp for the current stage
        $stageField = "{$this->current_stage}_at";
        $stageStartTime = $this->$stageField;
        
        if (!$stageStartTime) {
            return 'Unknown';
        }
        
        // For blackout stage with 0 duration, require at least 1 hour
        if ($this->current_stage === 'blackout' && $this->recipe->blackout_days === 0) {
            $minBlackoutTime = $stageStartTime->copy()->addHour();
            $now = now();
            
            if ($now->lt($minBlackoutTime)) {
                $diff = $now->diff($minBlackoutTime);
                if ($diff->h > 0) {
                    return "{$diff->h}h {$diff->i}m";
                } else {
                    return "{$diff->i}m";
                }
            }
            
            // Calculate overdue time
            $overTime = $now->diff($minBlackoutTime);
            $hours = $overTime->h;
            $minutes = $overTime->i;
            $overflowText = "{$hours}h {$minutes}m";
            
            return "Ready to advance|{$overflowText}";
        }
        
        // Get the duration for the current stage from the recipe
        $stageDuration = match ($this->current_stage) {
            'germination' => $this->recipe->germination_days,
            'blackout' => $this->recipe->blackout_days,
            'light' => $this->recipe->light_days,
            default => 0,
        };
        
        // Calculate the expected end date for this stage
        $stageEndDate = $stageStartTime->copy()->addDays($stageDuration);
        
        $now = now();
        if ($now->gt($stageEndDate)) {
            // Calculate overdue time
            $overTime = $now->diff($stageEndDate);
            $days = (int)$overTime->format('%a');
            $hours = $overTime->h;
            $minutes = $overTime->i;
            
            // Format the overtime
            $overflowText = '';
            if ($days > 0) {
                $overflowText = "{$days}d {$hours}h";
            } elseif ($hours > 0) {
                $overflowText = "{$hours}h {$minutes}m";
            } else {
                $overflowText = "{$minutes}m";
            }
            
            return "Ready to advance|{$overflowText}";
        }
        
        // Calculate the time difference to stage end
        $diff = $now->diff($stageEndDate);
        $days = (int)$diff->format('%a');
        $hours = $diff->h;
        $minutes = $diff->i;
        
        // Format based on remaining time
        if ($days > 0) {
            return "{$days}d {$hours}h";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }
    
    /**
     * Calculate and update the time to next stage values
     */
    protected function updateTimeToNextStageValues(): void
    {
        app(CropTimeCalculator::class)->updateTimeCalculations($this);
    }
    
    /**
     * Get the formatted stage age status text
     * 
     * @return string The formatted time in current stage
     */
    public function getStageAgeStatus(): string
    {
        return app(CropTimeCalculator::class)->getStageAgeStatus($this);
    }

    public function getTotalAgeStatus(): string
    {
        return app(CropTimeCalculator::class)->getTotalAgeStatus($this);
    }

    /**
     * Validate that timestamps are in chronological order
     * 
     * @param Crop $crop
     * @throws \Exception
     */
    protected static function validateTimestampSequence(Crop $crop): void
    {
        $timestamps = [];
        
        // Build array of non-null timestamps with their labels
        if ($crop->planting_at) {
            $timestamps['planting_at'] = $crop->planting_at;
        }
        if ($crop->germination_at) {
            $timestamps['germination_at'] = $crop->germination_at;
        }
        if ($crop->blackout_at) {
            $timestamps['blackout_at'] = $crop->blackout_at;
        }
        if ($crop->light_at) {
            $timestamps['light_at'] = $crop->light_at;
        }
        if ($crop->harvested_at) {
            $timestamps['harvested_at'] = $crop->harvested_at;
        }
        
        // Skip validation if we have fewer than 2 timestamps
        if (count($timestamps) < 2) {
            return;
        }
        
        // Convert to Carbon instances for comparison
        $carbonTimestamps = array_map(function($timestamp) {
            return $timestamp instanceof \Carbon\Carbon ? $timestamp : \Carbon\Carbon::parse($timestamp);
        }, $timestamps);
        
        // Check if timestamps are in order (allow same timestamp for flexibility)
        $previousTimestamp = null;
        $previousLabel = null;
        
        foreach ($carbonTimestamps as $label => $timestamp) {
            if ($previousTimestamp && $timestamp->lt($previousTimestamp)) {
                $readableLabel = str_replace('_at', '', str_replace('_', ' ', $label));
                $readablePrevious = str_replace('_at', '', str_replace('_', ' ', $previousLabel));
                throw new \Exception("Growth stage timestamps must be in chronological order. {$readableLabel} cannot be before {$readablePrevious}.");
            }
            $previousTimestamp = $timestamp;
            $previousLabel = $label;
        }
    }

}
