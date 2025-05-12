<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

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
     */
    public function crop()
    {
        return $this->belongsTo(Crop::class, 'conditions->crop_id');
    }
    
    /**
     * Get readable name for the alert type.
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
        
        return ucfirst(str_replace('_', ' ', $this->task_name));
    }
    
    /**
     * Get time until the alert is due.
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