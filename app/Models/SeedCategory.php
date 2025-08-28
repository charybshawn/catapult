<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents classification categories for agricultural seeds, organizing
 * seed types by botanical families, growing characteristics, and market
 * segments for microgreens production and seed catalog management.
 *
 * @business_domain Agricultural Seed Classification & Organization
 * @workflow_context Used in seed catalog management, variety organization, and agricultural planning
 * @agricultural_process Organizes seeds by botanical and commercial characteristics
 *
 * Database Table: seed_categories
 * @property int $id Primary identifier for seed category
 * @property string $code Unique category code for programmatic access
 * @property string $name Display name for seed category
 * @property string|null $description Category description and characteristics
 * @property string|null $color UI color for category visualization
 * @property bool $is_active Whether this category is available for use
 * @property int|null $sort_order Display order for category listing
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @business_rule Categories organize seeds by botanical families and growing patterns
 * @business_rule Only active categories are available for seed classification
 * @business_rule Sort order determines display sequence in interfaces
 *
 * @agricultural_examples Microgreens, Herbs, Leafy Greens, Brassicas, Legumes
 * @cultivation_context Different categories may have different growing requirements
 */
class SeedCategory extends Model
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
    ];

    /**
     * Get active seed categories formatted for dropdown selection.
     * Provides category options for agricultural seed classification.
     *
     * @return array Category options [name => name] for form selects
     * @agricultural_usage Used in seed catalog and variety classification interfaces
     * @business_logic Orders by sort_order for consistent agricultural organization
     * @active_filter Only returns categories available for current use
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'name')
            ->toArray();
    }

    /**
     * Find seed category by unique code identifier.
     * Enables programmatic access to specific agricultural seed categories.
     *
     * @param string $code Category code for lookup
     * @return self|null Matching seed category or null
     * @agricultural_usage Used in automated seed classification and data import
     * @business_logic Provides consistent category identification across workflows
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Query scope for active seed categories only.
     * Filters to categories available for agricultural operations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder instance
     * @return \Illuminate\Database\Eloquent\Builder Modified query for active categories
     * @agricultural_filter Excludes inactive/deprecated seed categories
     * @business_usage Used in seed catalog management and category selection
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}