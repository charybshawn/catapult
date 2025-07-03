<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CropStatus extends Model
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
     * Get active crop statuses for dropdowns.
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get crop statuses by code.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope to get only active statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only final statuses.
     */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Check if this status allows modifications.
     */
    public function allowsModifications(): bool
    {
        return $this->allows_modifications;
    }

    /**
     * Check if this is a final status.
     */
    public function isFinal(): bool
    {
        return $this->is_final;
    }
}