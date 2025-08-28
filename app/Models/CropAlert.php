<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agricultural crop monitoring alert system for automated production management.
 * 
 * Extends TaskSchedule to provide specialized crop-specific alerts for stage transitions,
 * watering schedules, and harvest timing. Enables proactive management of agricultural
 * production through automated monitoring and notification systems.
 * 
 * @property int $id Primary key identifier inherited from task_schedules
 * @property string $task_name Alert type identifier (advance_to_X, suspend_watering, soaking_completion_warning)
 * @property array|null $conditions JSON conditions including crop_id for alert targeting
 * @property \Illuminate\Support\Carbon $next_run_at Scheduled alert execution time
 * @property string $resource_type Resource type (crops) filtered by global scope
 * @property-read \App\Models\Crop $crop Associated crop for this alert
 * @property-read string $alert_type Human-readable alert type description
 * @property-read string $time_until Time remaining until alert execution
 * 
 * @agricultural_context Monitors crop growth stages, watering schedules, and harvest readiness
 * @business_rules Alerts filtered to crop-related tasks only through global scope
 * @automation_pattern Used for automated stage transitions and production management
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class CropAlert extends TaskSchedule
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_schedules';
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Global scope to only get crop-related tasks
        static::addGlobalScope('crop_alerts', function (Builder $builder) {
            $builder->where('resource_type', 'crops')
                   ->whereNotNull('conditions');
        });
    }
    
    /**
     * Get the crop associated with this alert.
     * 
     * Retrieves the crop that this alert is monitoring using JSON path extraction
     * from the conditions field to establish the relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Crop>
     * @agricultural_context Returns the specific crop being monitored for alerts
     * @business_usage Used for alert context and crop-specific notifications
     */
    public function crop()
    {
        return $this->belongsTo(Crop::class, 'conditions->crop_id');
    }
    
    /**
     * Get human-readable name for the alert type.
     * 
     * Converts task_name codes into user-friendly descriptions for agricultural
     * operations, including stage transitions and watering management.
     * 
     * @return string Formatted alert type description
     * @agricultural_context Translates technical codes to operational descriptions
     * @alert_types Handles stage advances, watering suspension, and soaking completion
     * @ui_usage Used for displaying alert information in management interfaces
     */
    public function getAlertTypeAttribute(): string
    {
        if (str_starts_with($this->task_name, 'advance_to_')) {
            $stage = str_replace('advance_to_', '', $this->task_name);
            return 'Advance to ' . ucfirst($stage);
        }
        
        if ($this->task_name === 'suspend_watering') {
            return 'Suspend Watering';
        }
        
        if ($this->task_name === 'soaking_completion_warning') {
            return 'Soaking Completes Today';
        }
        
        return ucfirst(str_replace('_', ' ', $this->task_name));
    }
    
    /**
     * Get formatted time remaining until alert execution.
     * 
     * Calculates and formats the time remaining until this alert is due,
     * providing precise timing information for agricultural production management.
     * 
     * @return string Formatted time remaining or "Overdue" if past due
     * @agricultural_context Provides timing for crop stage transitions and watering schedules
     * @time_format Returns format like "2d 5h 30m" for days, hours, minutes
     * @ui_usage Used for displaying alert urgency in management dashboards
     */
    public function getTimeUntilAttribute(): string
    {
        $now = Carbon::now();
        $nextRun = $this->next_run_at;
        
        if ($nextRun->isPast()) {
            return 'Overdue';
        }
        
        // Get precise time measurements
        $diff = $now->diff($nextRun);
        $days = $diff->d;
        $hours = $diff->h;
        $minutes = $diff->i;
        
        // Format the time display
        $timeUntil = '';
        if ($days > 0) {
            $timeUntil .= $days . 'd ';
        }
        if ($hours > 0 || $days > 0) {
            $timeUntil .= $hours . 'h ';
        }
        $timeUntil .= $minutes . 'm';
        
        return trim($timeUntil);
    }
} 