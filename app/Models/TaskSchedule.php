<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskSchedule extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'resource_type',
        'task_name',
        'frequency',
        'time_of_day',
        'day_of_week',
        'day_of_month',
        'conditions',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conditions' => 'json',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'time_of_day' => 'datetime',
    ];
    
    /**
     * Update the next run time based on the frequency.
     */
    public function updateNextRunTime(): void
    {
        $now = Carbon::now();
        
        switch ($this->frequency) {
            case 'hourly':
                $this->next_run_at = $now->addHour()->startOfHour();
                break;
            case 'daily':
                $this->next_run_at = $now->addDay()->setTimeFromTimeString($this->time_of_day ?? '00:00:00');
                break;
            case 'weekly':
                $nextRun = $now->copy()->addWeek()->startOfWeek();
                if ($this->day_of_week !== null) {
                    $nextRun = $nextRun->addDays($this->day_of_week);
                }
                $nextRun->setTimeFromTimeString($this->time_of_day ?? '00:00:00');
                $this->next_run_at = $nextRun;
                break;
            case 'monthly':
                $nextRun = $now->copy()->addMonth()->startOfMonth();
                if ($this->day_of_month !== null) {
                    // Ensure the day is valid for the month
                    $maxDay = $nextRun->copy()->endOfMonth()->day;
                    $day = min($this->day_of_month, $maxDay);
                    $nextRun = $nextRun->setDay($day);
                }
                $nextRun->setTimeFromTimeString($this->time_of_day ?? '00:00:00');
                $this->next_run_at = $nextRun;
                break;
            default:
                $this->next_run_at = $now->addDay();
        }
        
        $this->save();
    }
    
    /**
     * Check if the task is due to run.
     */
    public function isDue(): bool
    {
        return $this->is_active && Carbon::now()->gte($this->next_run_at);
    }
    
    /**
     * Mark the task as run and update the next run time.
     */
    public function markAsRun(): void
    {
        $this->last_run_at = Carbon::now();
        $this->updateNextRunTime();
    }
}
