<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get active currencies for dropdowns.
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('code', 'code')
            ->toArray();
    }

    /**
     * Get currencies by code.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the display string for this currency.
     */
    public function getDisplayAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Format an amount with this currency symbol.
     */
    public function formatAmount(float $amount, int $decimals = 2): string
    {
        return $this->symbol . number_format($amount, $decimals);
    }
}