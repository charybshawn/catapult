<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryReservationStatus extends Model
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
        'auto_release_hours',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'allows_modifications' => 'boolean',
        'auto_release_hours' => 'integer',
    ];

    /**
     * Get inventory reservations with this status
     */
    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'status_id');
    }

    /**
     * Find a reservation status by its code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get all active reservation statuses for dropdowns
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get only active reservation statuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Business Logic Methods

    /**
     * Check if this is a pending reservation status
     */
    public function isPending(): bool
    {
        return $this->code === 'pending';
    }

    /**
     * Check if this is a confirmed reservation status
     */
    public function isConfirmed(): bool
    {
        return $this->code === 'confirmed';
    }

    /**
     * Check if this is a fulfilled reservation status
     */
    public function isFulfilled(): bool
    {
        return $this->code === 'fulfilled';
    }

    /**
     * Check if this is a cancelled reservation status
     */
    public function isCancelled(): bool
    {
        return $this->code === 'cancelled';
    }

    /**
     * Check if reservation is active (pending or confirmed)
     */
    public function isActive(): bool
    {
        return in_array($this->code, ['pending', 'confirmed']);
    }

    /**
     * Check if reservation holds inventory
     */
    public function holdsInventory(): bool
    {
        return in_array($this->code, ['pending', 'confirmed']);
    }

    /**
     * Check if reservation can be modified
     */
    public function canBeModified(): bool
    {
        return $this->allows_modifications && !$this->is_final;
    }

    /**
     * Check if reservation can be confirmed
     */
    public function canBeConfirmed(): bool
    {
        return $this->code === 'pending';
    }

    /**
     * Check if reservation can be fulfilled
     */
    public function canBeFulfilled(): bool
    {
        return $this->code === 'confirmed';
    }

    /**
     * Check if reservation can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->code, ['pending', 'confirmed']);
    }

    /**
     * Check if reservation should auto-release
     */
    public function shouldAutoRelease(): bool
    {
        return $this->auto_release_hours > 0;
    }

    /**
     * Get the next logical status for workflow progression
     */
    public function getNextStatus(): ?self
    {
        return match($this->code) {
            'pending' => static::findByCode('confirmed'),
            'confirmed' => static::findByCode('fulfilled'),
            default => null // fulfilled/cancelled are final
        };
    }

    /**
     * Get status color for UI display
     */
    public function getDisplayColor(): string
    {
        return $this->color ?? match($this->code) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'fulfilled' => 'success',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }
}