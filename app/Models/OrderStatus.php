<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'is_final',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the orders for this status.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get options for select fields (active statuses only).
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all active order statuses.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find order status by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    // Status check methods
    public function isPending(): bool { return $this->code === 'pending'; }
    public function isConfirmed(): bool { return $this->code === 'confirmed'; }
    public function isInProduction(): bool { return $this->code === 'in_production'; }
    public function isReadyForHarvest(): bool { return $this->code === 'ready_for_harvest'; }
    public function isHarvested(): bool { return $this->code === 'harvested'; }
    public function isPacked(): bool { return $this->code === 'packed'; }
    public function isDelivered(): bool { return $this->code === 'delivered'; }
    public function isCancelled(): bool { return $this->code === 'cancelled'; }
    public function isDraft(): bool { return $this->code === 'draft'; }
    public function isTemplate(): bool { return $this->code === 'template'; }

    /**
     * Check if this is a completed status (delivered or cancelled).
     */
    public function isCompleted(): bool
    {
        return in_array($this->code, ['delivered', 'cancelled']);
    }

    /**
     * Check if this status allows modifications.
     */
    public function allowsModifications(): bool
    {
        return !$this->is_final && !in_array($this->code, ['delivered', 'cancelled']);
    }
}