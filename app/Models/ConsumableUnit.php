<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsumableUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
        'category',
        'conversion_factor',
        'base_unit',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:10',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the consumables using this unit.
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }

    /**
     * Get options for select fields (active units only).
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
     * Get options grouped by category.
     */
    public static function optionsByCategory(): array
    {
        return static::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(function ($units) {
                return $units->pluck('name', 'id')->toArray();
            })
            ->toArray();
    }

    /**
     * Get all active units.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find unit by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get units by category.
     */
    public static function byCategory(string $category)
    {
        return static::where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Convert amount from this unit to another unit.
     */
    public function convertTo(float $amount, ConsumableUnit $targetUnit): ?float
    {
        // Can only convert within the same category
        if ($this->category !== $targetUnit->category) {
            return null;
        }

        // If both units have conversion factors, convert via base unit
        if ($this->conversion_factor && $targetUnit->conversion_factor) {
            $baseAmount = $amount * $this->conversion_factor;
            return $baseAmount / $targetUnit->conversion_factor;
        }

        return null;
    }

    /**
     * Convert amount to base unit.
     */
    public function toBaseUnit(float $amount): ?float
    {
        if (!$this->conversion_factor) {
            return null;
        }

        return $amount * $this->conversion_factor;
    }

    /**
     * Check if this is a weight unit.
     */
    public function isWeight(): bool
    {
        return $this->category === 'weight';
    }

    /**
     * Check if this is a volume unit.
     */
    public function isVolume(): bool
    {
        return $this->category === 'volume';
    }

    /**
     * Check if this is a count unit.
     */
    public function isCount(): bool
    {
        return $this->category === 'count';
    }

    /**
     * Get display name with symbol.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->symbol ? "{$this->name} ({$this->symbol})" : $this->name;
    }
}