<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightUnit extends Model
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
     * Get active weight units for dropdowns.
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Get weight units by code.
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
     * Convert weight from this unit to grams.
     */
    public function toGrams(float $value): float
    {
        return $value * $this->conversion_factor;
    }

    /**
     * Convert weight from grams to this unit.
     */
    public function fromGrams(float $grams): float
    {
        return $grams / $this->conversion_factor;
    }

    /**
     * Get the display string for this unit.
     */
    public function getDisplayAttribute(): string
    {
        return "{$this->name} ({$this->symbol})";
    }
}