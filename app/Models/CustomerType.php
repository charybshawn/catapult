<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the customers for this customer type.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the users for this customer type.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
     * Get all active customer types.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find customer type by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this customer type qualifies for wholesale pricing.
     */
    public function qualifiesForWholesalePricing(): bool
    {
        return in_array($this->code, ['wholesale', 'farmers_market']);
    }

    /**
     * Check if this is a retail customer type.
     */
    public function isRetail(): bool
    {
        return $this->code === 'retail';
    }

    /**
     * Check if this is a wholesale customer type.
     */
    public function isWholesale(): bool
    {
        return $this->code === 'wholesale';
    }

    /**
     * Check if this is a farmers market customer type.
     */
    public function isFarmersMarket(): bool
    {
        return $this->code === 'farmers_market';
    }
}