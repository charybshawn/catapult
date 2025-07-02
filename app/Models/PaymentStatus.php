<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
        'is_final',
        'allows_modifications',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'allows_modifications' => 'boolean',
    ];

    /**
     * Get payments with this status
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'status_id');
    }

    /**
     * Find a payment status by its code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get all active payment statuses for dropdowns
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get only active payment statuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Business Logic Methods

    /**
     * Check if this is a pending payment status
     */
    public function isPending(): bool
    {
        return $this->code === 'pending';
    }

    /**
     * Check if this is a completed payment status
     */
    public function isCompleted(): bool
    {
        return $this->code === 'completed';
    }

    /**
     * Check if this is a failed payment status
     */
    public function isFailed(): bool
    {
        return $this->code === 'failed';
    }

    /**
     * Check if this is a refunded payment status
     */
    public function isRefunded(): bool
    {
        return $this->code === 'refunded';
    }

    /**
     * Check if payment is successful (completed or refunded)
     */
    public function isSuccessful(): bool
    {
        return in_array($this->code, ['completed', 'refunded']);
    }

    /**
     * Check if payment needs attention (failed or pending for too long)
     */
    public function needsAttention(): bool
    {
        return in_array($this->code, ['failed', 'pending']);
    }

    /**
     * Check if payment can be modified
     */
    public function canBeModified(): bool
    {
        return $this->allows_modifications && !$this->is_final;
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->code === 'completed';
    }

    /**
     * Get the next logical status for workflow progression
     */
    public function getNextStatus(): ?self
    {
        return match($this->code) {
            'pending' => static::findByCode('completed'),
            'failed' => static::findByCode('pending'), // Allow retry
            default => null // completed/refunded are final
        };
    }

    /**
     * Get status color for UI display
     */
    public function getDisplayColor(): string
    {
        return $this->color ?? match($this->code) {
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'gray'
        };
    }
}