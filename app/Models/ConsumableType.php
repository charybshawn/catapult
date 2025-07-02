<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsumableType extends Model
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
     * Get the consumables for this type.
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }

    /**
     * Get options for select fields (active types only).
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
     * Get all active consumable types.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find consumable type by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is a packaging type.
     */
    public function isPackaging(): bool
    {
        return $this->code === 'packaging';
    }

    /**
     * Check if this is a soil type.
     */
    public function isSoil(): bool
    {
        return $this->code === 'soil';
    }

    /**
     * Check if this is a seed type.
     */
    public function isSeed(): bool
    {
        return $this->code === 'seed';
    }

    /**
     * Check if this is a label type.
     */
    public function isLabel(): bool
    {
        return $this->code === 'label';
    }

    /**
     * Check if this is other type.
     */
    public function isOther(): bool
    {
        return $this->code === 'other';
    }
}