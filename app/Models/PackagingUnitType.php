<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagingUnitType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the packaging types for this unit type.
     */
    public function packagingTypes(): HasMany
    {
        return $this->hasMany(PackagingType::class, 'unit_type_id');
    }

    /**
     * Get options for select fields (active unit types only).
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
     * Get all active packaging unit types.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find packaging unit type by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is a count-based unit type.
     */
    public function isCount(): bool
    {
        return $this->code === 'count';
    }

    /**
     * Check if this is a weight-based unit type.
     */
    public function isWeight(): bool
    {
        return $this->code === 'weight';
    }

    /**
     * Check if this unit type requires precise measurement.
     */
    public function requiresPreciseMeasurement(): bool
    {
        return $this->isWeight();
    }

    /**
     * Check if this unit type allows fractional quantities.
     */
    public function allowsFractionalQuantities(): bool
    {
        return $this->isWeight();
    }

    /**
     * Get the typical measurement units for this unit type.
     */
    public function getTypicalUnits(): array
    {
        return match ($this->code) {
            'count' => ['unit', 'piece', 'container'],
            'weight' => ['gram', 'kilogram', 'ounce', 'pound'],
            default => ['unit'],
        };
    }
}