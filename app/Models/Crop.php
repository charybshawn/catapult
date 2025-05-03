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
        'time_to_next_stage_minutes',
        'time_to_next_stage_status',
        'stage_age_minutes',
        'stage_age_status',
        'total_age_minutes',
        'total_age_status',
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
     * Boot method to add lifecycle hooks
     */
    protected static function booted()
    {
        // Add our existing boot logic
        static::creating(function (Crop $crop) {
            // Set planted_at if not provided
            if (!$crop->planted_at) {
                $crop->planted_at = now();
            }
            
            // Set planting_at timestamp for record-keeping
            if (!$crop->planting_at) {
                $crop->planting_at = $crop->planted_at;
            }
            
            // Set germination_at and current_stage to germination automatically
            if ($crop->planted_at && !$crop->germination_at) {
                $crop->germination_at = $crop->planted_at;
            }
            
            // Always start at germination stage if not set
            if (!$crop->current_stage) {
                $crop->current_stage = 'germination';
            }
            
            // Initialize computed time fields with safe values
            if (!isset($crop->time_to_next_stage_minutes)) {
                $crop->time_to_next_stage_minutes = 0;
            }
            if (!isset($crop->time_to_next_stage_status)) {
                $crop->time_to_next_stage_status = 'Unknown';
            }
            if (!isset($crop->stage_age_minutes)) {
                $crop->stage_age_minutes = 0;
            }
            if (!isset($crop->stage_age_status)) {
                $crop->stage_age_status = '0m';
            }
            if (!isset($crop->total_age_minutes)) {
                $crop->total_age_minutes = 0;
            }
            if (!isset($crop->total_age_status)) {
                $crop->total_age_status = '0m';
            }
        });
        
        // Add event listeners to recalculate time_to_next_stage values
        static::saving(function (Crop $crop) {
            // Calculate and update the time_to_next_stage values whenever the model is saved
            $crop->updateTimeToNextStageValues();
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
    
    /**
     * Calculate and update the time to next stage values
     */
    protected function updateTimeToNextStageValues(): void
    {
        // Calculate and store time to next stage values
        $status = $this->timeToNextStage();
        $this->time_to_next_stage_status = $status;
        
        // Calculate minutes for sorting
        if ($status === 'Ready to advance') {
            // Highest priority (lowest minutes) for ready to advance
            $this->time_to_next_stage_minutes = 0;
        } elseif ($status === '-' || $status === 'No recipe' || $status === 'Unknown') {
            // Lowest priority (highest minutes) for special statuses
            // Use a large but safe integer value instead of PHP_INT_MAX
            $this->time_to_next_stage_minutes = 2147483647; // Max value for a signed 32-bit integer
        } else {
            // Extract time components
            $days = preg_match('/(\d+)d/', $status, $dayMatches) ? (int)$dayMatches[1] : 0;
            $hours = preg_match('/(\d+)h/', $status, $hourMatches) ? (int)$hourMatches[1] : 0;
            $minutes = preg_match('/(\d+)m/', $status, $minuteMatches) ? (int)$minuteMatches[1] : 0;
            
            // Calculate total minutes
            $this->time_to_next_stage_minutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
        }
        
        // Calculate and store stage age values
        $stageAgeStatus = $this->getStageAgeStatus();
        $this->stage_age_status = $stageAgeStatus;
        
        // Calculate stage age minutes for sorting
        if ($stageAgeStatus === '0m' || empty($stageAgeStatus)) {
            $this->stage_age_minutes = 0;
        } else {
            // Extract time components
            $days = preg_match('/(\d+)d/', $stageAgeStatus, $dayMatches) ? (int)$dayMatches[1] : 0;
            $hours = preg_match('/(\d+)h/', $stageAgeStatus, $hourMatches) ? (int)$hourMatches[1] : 0;
            $minutes = preg_match('/(\d+)m/', $stageAgeStatus, $minuteMatches) ? (int)$minuteMatches[1] : 0;
            
            // Calculate total minutes
            $totalMinutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
            
            // Ensure the value doesn't exceed integer limits
            $this->stage_age_minutes = min($totalMinutes, 2147483647);
        }
        
        // Calculate and store total age values
        $totalAgeStatus = $this->getTotalAgeStatus();
        $this->total_age_status = $totalAgeStatus;
        
        // Calculate total age minutes for sorting
        if ($totalAgeStatus === '0m' || empty($totalAgeStatus)) {
            $this->total_age_minutes = 0;
        } else {
            // Extract time components
            $days = preg_match('/(\d+)d/', $totalAgeStatus, $dayMatches) ? (int)$dayMatches[1] : 0;
            $hours = preg_match('/(\d+)h/', $totalAgeStatus, $hourMatches) ? (int)$hourMatches[1] : 0;
            $minutes = preg_match('/(\d+)m/', $totalAgeStatus, $minuteMatches) ? (int)$minuteMatches[1] : 0;
            
            // Calculate total minutes
            $totalMinutes = ($days * 24 * 60) + ($hours * 60) + $minutes;
            
            // Ensure the value doesn't exceed integer limits
            $this->total_age_minutes = min($totalMinutes, 2147483647);
        }
    }
    
    /**
     * Get the formatted stage age status text
     * 
     * @return string The formatted time in current stage
     */
    public function getStageAgeStatus(): string
    {
        $stageField = "{$this->current_stage}_at";
        if (!$this->$stageField) {
            return '0m';
        }
        
        $now = now();
        $stageStart = $this->$stageField;
        
        // Calculate total time difference
        $totalHours = $stageStart->diffInHours($now);
        $totalMinutes = $stageStart->diffInMinutes($now) % 60;
        $totalDays = floor($totalHours / 24);
        $remainingHours = $totalHours % 24;
        
        // Format based on total time
        if ($totalDays > 0) {
            return "{$totalDays}d {$remainingHours}h";
        } elseif ($remainingHours > 0) {
            return "{$remainingHours}h {$totalMinutes}m";
        } else {
            return "{$totalMinutes}m";
        }
    }
    
    /**
     * Get the formatted total age status text
     * 
     * @return string The formatted time since planting
     */
    public function getTotalAgeStatus(): string
    {
        if (!$this->planted_at) {
            return '0m';
        }
        
        $now = now();
        $plantedAt = $this->planted_at;
        
        // Calculate total time difference
        $totalHours = $plantedAt->diffInHours($now);
        $totalMinutes = $plantedAt->diffInMinutes($now) % 60;
        $totalDays = floor($totalHours / 24);
        $remainingHours = $totalHours % 24;
        
        // Format based on total time
        if ($totalDays > 0) {
            return "{$totalDays}d {$remainingHours}h";
        } elseif ($remainingHours > 0) {
            return "{$remainingHours}h {$totalMinutes}m";
        } else {
            return "{$totalMinutes}m";
        }
    }
}
