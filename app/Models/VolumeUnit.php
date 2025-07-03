<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolumeUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
        'conversion_factor',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conversion_factor' => 'decimal:8',
    ];

    /**
     * Get active volume units for dropdowns.
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Get volume units by code.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope to get only active units.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Convert volume from this unit to milliliters.
     */
    public function toMilliliters(float $value): float
    {
        return $value * $this->conversion_factor;
    }

    /**
     * Convert volume from milliliters to this unit.
     */
    public function fromMilliliters(float $ml): float
    {
        return $ml / $this->conversion_factor;
    }

    /**
     * Get the display string for this unit.
     */
    public function getDisplayAttribute(): string
    {
        return "{$this->name} ({$this->symbol})";
    }
}