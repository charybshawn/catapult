<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Crop extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
        'order_id',
        'tray_number',
        'planted_at',
        'current_stage',
        'planting_at',
        'germination_at',
        'blackout_at',
        'light_at',
        'harvested_at',
        'harvest_weight_grams',
        'watering_suspended_at',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'planted_at' => 'datetime',
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
     * Get the seed variety for this crop through the recipe.
     */
    public function seedVariety()
    {
        if ($this->recipe) {
            return $this->recipe->seedVariety;
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
        $this->watering_suspended_at = now();
        $this->save();
    }
    
    /**
     * Resume watering.
     */
    public function resumeWatering(): void
    {
        $this->watering_suspended_at = null;
        $this->save();
    }
    
    /**
     * Advance to the next stage.
     */
    public function advanceStage(): void
    {
        $currentStage = $this->current_stage;
        $now = now();
        
        // Determine next stage, skipping blackout if duration is 0
        $nextStage = match ($currentStage) {
            'germination' => ($this->recipe && $this->recipe->blackout_days > 0) ? 'blackout' : 'light',
            'blackout' => 'light',
            'light' => 'harvested',
            default => $this->current_stage,
        };
        
        // Record timestamp for the new stage
        $stageTimestampField = "{$nextStage}_at";
        $this->$stageTimestampField = $now;
        
        // Update current stage
        $this->current_stage = $nextStage;
        
        $this->save();
    }
    
    /**
     * Calculate the expected harvest date.
     */
    public function expectedHarvestDate(): ?Carbon
    {
        if (!$this->planted_at || !$this->recipe) {
            return null;
        }
        
        // Get current time
        $now = now();
        
        // Calculate based on current stage
        switch ($this->current_stage) {
            case 'germination':
                // Get actual or calculated germination start time
                $germinationStart = $this->germination_at ?? $this->planted_at;
                
                // Calculate when germination should end
                $germinationEnd = $this->planted_at->copy()->addDays($this->recipe->germination_days);
                
                // Calculate when blackout should end
                $blackoutDuration = $this->recipe->blackout_days;
                $blackoutEnd = $germinationEnd->copy()->addDays($blackoutDuration);
                
                // Calculate when light should end (harvest date)
                $lightDuration = $this->recipe->light_days;
                $lightEnd = $blackoutEnd->copy()->addDays($lightDuration);
                
                return $lightEnd;
                
            case 'blackout':
                // Get actual or calculated blackout start time
                $blackoutStart = $this->blackout_at;
                if (!$blackoutStart) {
                    // Calculate based on germination duration
                    $blackoutStart = $this->planted_at->copy()->addDays($this->recipe->germination_days);
                }
                
                // Calculate when blackout should end
                $blackoutDuration = $this->recipe->blackout_days;
                $blackoutEnd = $blackoutStart->copy()->addDays($blackoutDuration);
                
                // Calculate when light should end (harvest date)
                $lightDuration = $this->recipe->light_days;
                $lightEnd = $blackoutEnd->copy()->addDays($lightDuration);
                
                return $lightEnd;
                
            case 'light':
                // Get actual or calculated light start time
                $lightStart = $this->light_at;
                if (!$lightStart) {
                    // Calculate based on germination and blackout durations
                    $lightStart = $this->planted_at->copy()
                        ->addDays($this->recipe->germination_days)
                        ->addDays($this->recipe->blackout_days);
                }
                
                // Calculate when light should end (harvest date)
                $lightDuration = $this->recipe->light_days;
                $lightEnd = $lightStart->copy()->addDays($lightDuration);
                
                return $lightEnd;
                
            default:
                return null;
        }
    }
    
    /**
     * Calculate days in current stage.
     */
    public function daysInCurrentStage(): int
    {
        $stageField = "{$this->current_stage}_at";
        
        if (!$this->$stageField) {
            return 0;
        }
        
        // Use hours for more precise calculations
        $hoursInStage = $this->$stageField->diffInHours(now());
        return intval($hoursInStage / 24); // Convert hours to days
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
                'planted_at',
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
     * Set the initial timestamps when creating a new crop.
     */
    protected static function booted()
    {
        static::creating(function ($crop) {
            // Set planted_at if not provided
            if (!$crop->planted_at) {
                $crop->planted_at = now();
            }
            
            // Set planting_at timestamp for record-keeping
            if (!$crop->planting_at) {
                $crop->planting_at = $crop->planted_at;
            }
            
            // Set germination_at and current_stage to germination automatically
            // Skip the planting stage entirely
            if (!$crop->germination_at) {
                $crop->germination_at = $crop->planted_at;
            }
            
            // Always start at germination stage
            $crop->current_stage = 'germination';
        });
        
        static::created(function ($crop) {
            // Schedule stage transition tasks
            app(\App\Services\CropTaskService::class)->scheduleAllStageTasks($crop);
        });
        
        static::updating(function ($crop) {
            // If planted_at has changed, recalculate stage dates
            if ($crop->isDirty('planted_at') && $crop->recipe) {
                // Get original planted_at
                $originalPlantedAt = $crop->getOriginal('planted_at');
                
                // Get the new planted_at
                $newPlantedAt = $crop->planted_at;
                
                // Calculate time difference in seconds
                $originalDateTime = new \Carbon\Carbon($originalPlantedAt);
                $timeDiff = $originalDateTime->diffInSeconds($newPlantedAt, false);
                
                // Adjust all stage timestamps by the same amount
                foreach (['germination_at', 'blackout_at', 'light_at'] as $stageField) {
                    if ($crop->$stageField) {
                        $stageTime = new \Carbon\Carbon($crop->$stageField);
                        $crop->$stageField = $stageTime->addSeconds($timeDiff);
                    }
                }
            }
        });
        
        static::updated(function ($crop) {
            // If the stage has changed or planted_at has changed, recalculate tasks
            if ($crop->isDirty('current_stage') || $crop->isDirty('planted_at')) {
                app(\App\Services\CropTaskService::class)->scheduleAllStageTasks($crop);
            }
        });
    }
    
    /**
     * Reset to a specific stage and clear future stage timestamps.
     */
    public function resetToStage(string $newStage): void
    {
        $currentStage = $this->current_stage;
        $now = now();
        
        // Set the new stage
        $this->current_stage = $newStage;
        
        // Update the timestamp for the current stage
        $stageTimestampField = "{$newStage}_at";
        
        // Always update the timestamp for the new stage to now
        $this->$stageTimestampField = $now;
        
        // Clear all timestamps for stages that come after the current stage
        $stageOrder = ['germination', 'blackout', 'light', 'harvested'];
        $currentStageIndex = array_search($newStage, $stageOrder);
        
        // If we found the stage in our ordered list
        if ($currentStageIndex !== false) {
            // Clear timestamps for all stages after the current one
            for ($i = $currentStageIndex + 1; $i < count($stageOrder); $i++) {
                $fieldName = "{$stageOrder[$i]}_at";
                $this->$fieldName = null;
            }
            
            // Also clear harvest weight if we're no longer at the harvested stage
            if ($newStage !== 'harvested') {
                $this->harvest_weight_grams = null;
            }
        }
        
        $this->save();
        
        // Recalculate expected stage timestamps for future tasks
        // This will be handled by the updated hook that detects stage changes
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
            return 'Ready to advance';
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
            return 'Ready to advance';
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
}
