<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use App\Traits\Logging\ExtendedLogsActivity;
use App\Models\Activity;

class TimeCard extends Model
{
    use ExtendedLogsActivity;
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'duration_minutes',
        'work_date',
        'time_card_status_id',
        'notes',
        'ip_address',
        'user_agent',
        'max_shift_exceeded',
        'max_shift_exceeded_at',
        'requires_review',
        'flags',
        'review_notes',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'work_date' => 'date',
        'max_shift_exceeded' => 'boolean',
        'max_shift_exceeded_at' => 'datetime',
        'requires_review' => 'boolean',
        'flags' => 'array',
    ];

    /**
     * Activity log configuration
     */
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'timecard';
    protected static $submitEmptyLogs = false;

    /**
     * Get custom activity description
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $userName = $this->user?->name ?? 'Unknown User';
        
        return match($eventName) {
            'created' => "Time card created for {$userName}",
            'updated' => "Time card updated for {$userName}",
            'deleted' => "Time card deleted for {$userName}",
            'clock_in' => "{$userName} clocked in",
            'clock_out' => "{$userName} clocked out",
            'flagged' => "Time card flagged for review - {$userName}",
            'reviewed' => "Time card reviewed for {$userName}",
            default => "Time card {$eventName} for {$userName}",
        };
    }

    protected static function booted()
    {
        static::creating(function ($timeCard) {
            if (!$timeCard->work_date) {
                $timeCard->work_date = now()->toDateString();
            }
        });

        static::saving(function ($timeCard) {
            if ($timeCard->clock_in && $timeCard->clock_out) {
                $timeCard->duration_minutes = $timeCard->clock_out->diffInMinutes($timeCard->clock_in);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskTypes(): BelongsToMany
    {
        return $this->belongsToMany(TaskType::class, 'time_card_tasks')
            ->withPivot('is_custom', 'task_name')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TimeCardTask::class);
    }

    /**
     * Get the status for this time card.
     */
    public function timeCardStatus(): BelongsTo
    {
        return $this->belongsTo(TimeCardStatus::class);
    }

    /**
     * Add tasks to this time card
     * @param array $tasks Array of task names
     */
    public function addTasks(array $tasks): void
    {
        foreach ($tasks as $taskName) {
            // Check if this is an existing task type
            $taskType = TaskType::where('name', $taskName)->first();
            
            $this->tasks()->create([
                'task_name' => $taskName,
                'task_type_id' => $taskType?->id,
                'is_custom' => !$taskType,
            ]);
        }
    }

    /**
     * Get all task names as an array
     */
    public function getTaskNamesAttribute(): array
    {
        return $this->tasks()->pluck('task_name')->toArray();
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_minutes) {
            return '--:--';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getElapsedTimeAttribute(): string
    {
        if (!$this->clock_in) {
            return '00:00:00';
        }

        $end = $this->clock_out ?? now();
        $diff = $this->clock_in->diff($end);

        return sprintf('%02d:%02d:%02d', $diff->h + ($diff->days * 24), $diff->i, $diff->s);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('clock_in')
                    ->whereNull('clock_out');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('work_date', today());
    }

    public static function getActiveForUser($userId)
    {
        return static::active()
            ->forUser($userId)
            ->latest('clock_in')
            ->first();
    }

    public function clockOut()
    {
        $completedStatus = TimeCardStatus::where('code', 'completed')->first();
        $this->update([
            'clock_out' => now(),
            'time_card_status_id' => $completedStatus?->id,
        ]);

        // Log custom activity
        $this->logActivity('clock_out', [
            'duration_minutes' => $this->duration_minutes,
            'work_date' => $this->work_date,
        ]);
    }

    /**
     * Check if this time card has exceeded the maximum shift length
     */
    public function checkMaxShiftExceeded(): bool
    {
        if (!$this->clock_in || $this->timeCardStatus?->code !== 'active') {
            return false;
        }

        $hoursWorked = $this->clock_in->diffInHours(now());
        return $hoursWorked >= 8;
    }

    /**
     * Flag this time card as exceeding max shift and requiring review
     */
    public function flagForReview(string $reason = 'Exceeded 8-hour maximum shift'): void
    {
        if ($this->max_shift_exceeded) {
            return; // Already flagged
        }

        $flags = $this->flags ?? [];
        $flags[] = $reason;

        $this->update([
            'max_shift_exceeded' => true,
            'max_shift_exceeded_at' => now(),
            'requires_review' => true,
            'flags' => $flags,
            'review_notes' => $reason . ' at ' . now()->format('Y-m-d H:i:s'),
        ]);

        // Log flagging activity
        $this->logActivity('flagged', [
            'reason' => $reason,
            'hours_worked' => $this->clock_in->diffInHours(now()),
        ]);
    }

    /**
     * Check if time card is currently over 8 hours and needs flagging
     */
    public function checkAndFlagIfNeeded(): bool
    {
        if ($this->checkMaxShiftExceeded() && !$this->max_shift_exceeded) {
            $this->flagForReview('Exceeded 8-hour maximum shift');
            return true;
        }
        return false;
    }

    /**
     * Resolve the review flag (for managers)
     */
    public function resolveReview(string $resolvedBy, ?string $notes = null): void
    {
        $this->update([
            'requires_review' => false,
            'review_notes' => ($this->review_notes ?? '') . "\n\nResolved by {$resolvedBy} at " . now()->format('Y-m-d H:i:s') . 
                             ($notes ? ": {$notes}" : ''),
        ]);

        // Log review activity
        $this->logActivity('reviewed', [
            'resolved_by' => $resolvedBy,
            'notes' => $notes,
        ]);
    }

    /**
     * Scope for time cards requiring review
     */
    public function scopeRequiresReview($query)
    {
        return $query->where('requires_review', true);
    }

    /**
     * Scope for time cards that exceeded max shift
     */
    public function scopeMaxShiftExceeded($query)
    {
        return $query->where('max_shift_exceeded', true);
    }

    /**
     * Scope to exclude time cards with deleted users
     */
    public function scopeWithValidUsers($query)
    {
        return $query->whereHas('user');
    }

    /**
     * Scope to find orphaned time cards (without valid users)
     */
    public function scopeOrphaned($query)
    {
        return $query->whereDoesntHave('user');
    }

    /**
     * Get all activities related to this time card
     */
    public function getActivities()
    {
        return Activity::where(function ($query) {
            $query->where('subject_type', static::class)
                  ->where('subject_id', $this->id);
        })->orWhere(function ($query) {
            $query->where('causer_type', User::class)
                  ->where('causer_id', $this->user_id)
                  ->whereBetween('created_at', [$this->clock_in, $this->clock_out ?? now()]);
        })->orderBy('created_at')->get();
    }

    /**
     * Generate a work report from activity data
     */
    public function generateWorkReport(): array
    {
        $activities = $this->getActivities();
        
        return [
            'time_card' => [
                'id' => $this->id,
                'user' => $this->user->name,
                'work_date' => $this->work_date->format('Y-m-d'),
                'clock_in' => $this->clock_in->format('H:i:s'),
                'clock_out' => $this->clock_out?->format('H:i:s'),
                'duration' => $this->duration_formatted,
                'status' => $this->timeCardStatus?->name,
                'tasks' => $this->task_names,
            ],
            'activities_summary' => [
                'total' => $activities->count(),
                'by_type' => $activities->groupBy('log_name')->map->count(),
                'by_model' => $activities->groupBy('subject_type')->map->count(),
            ],
            'timeline' => $activities->map(function ($activity) {
                return [
                    'time' => $activity->created_at->format('H:i:s'),
                    'description' => $activity->description,
                    'type' => $activity->log_name,
                    'properties' => $activity->properties,
                ];
            }),
            'flags' => $this->flags ?? [],
            'requires_review' => $this->requires_review,
            'review_notes' => $this->review_notes,
        ];
    }

    /**
     * Link an activity to this time card
     */
    public function linkActivity(Activity $activity): void
    {
        $activity->update([
            'properties' => array_merge($activity->properties ?? [], [
                'time_card_id' => $this->id,
                'work_date' => $this->work_date->format('Y-m-d'),
            ]),
        ]);
    }
}
