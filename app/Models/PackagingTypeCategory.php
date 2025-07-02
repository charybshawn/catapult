<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagingTypeCategory extends Model
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
     * Get the packaging types for this category.
     */
    public function packagingTypes(): HasMany
    {
        return $this->hasMany(PackagingType::class, 'type_category_id');
    }

    /**
     * Get options for select fields (active categories only).
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
     * Get all active packaging type categories.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find packaging type category by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is a clamshell category.
     */
    public function isClamshell(): bool
    {
        return $this->code === 'clamshell';
    }

    /**
     * Check if this is a bag category.
     */
    public function isBag(): bool
    {
        return $this->code === 'bag';
    }

    /**
     * Check if this is a box category.
     */
    public function isBox(): bool
    {
        return $this->code === 'box';
    }

    /**
     * Check if this is a jar category.
     */
    public function isJar(): bool
    {
        return $this->code === 'jar';
    }

    /**
     * Check if this is a tray category.
     */
    public function isTray(): bool
    {
        return $this->code === 'tray';
    }

    /**
     * Check if this is a bulk category.
     */
    public function isBulk(): bool
    {
        return $this->code === 'bulk';
    }

    /**
     * Check if this is suitable for retail display.
     */
    public function isRetailSuitable(): bool
    {
        return in_array($this->code, ['clamshell', 'box', 'jar', 'tray']);
    }

    /**
     * Check if this requires careful handling.
     */
    public function requiresCarefulHandling(): bool
    {
        return in_array($this->code, ['clamshell', 'jar']);
    }
}