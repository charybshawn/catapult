<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeCard extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'duration_minutes',
        'work_date',
        'status',
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

    protected static function booted()
    {
        static::creating(function ($timeCard) {
            if (!$timeCard->work_date) {
                $timeCard->work_date = now()->toDateString();
            }
        });

        static::saving(function ($timeCard) {
            if ($timeCard->clock_in && $timeCard->clock_out) {
                $timeCard->duration_minutes = $timeCard->clock_in->diffInMinutes($timeCard->clock_out);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $query->where('status', 'active');
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
        $this->update([
            'clock_out' => now(),
            'status' => 'completed',
        ]);
    }

    /**
     * Check if this time card has exceeded the maximum shift length
     */
    public function checkMaxShiftExceeded(): bool
    {
        if (!$this->clock_in || $this->status !== 'active') {
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
    public function resolveReview(string $resolvedBy, string $notes = null): void
    {
        $this->update([
            'requires_review' => false,
            'review_notes' => ($this->review_notes ?? '') . "\n\nResolved by {$resolvedBy} at " . now()->format('Y-m-d H:i:s') . 
                             ($notes ? ": {$notes}" : ''),
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
}
