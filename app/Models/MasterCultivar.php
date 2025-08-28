<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a specific cultivar (variety) of a master seed catalog entry,
 * providing detailed agricultural variety classification for microgreens production.
 * This model enables precise variety tracking and agricultural performance analysis.
 *
 * @business_domain Agricultural Variety Management & Seed Classification
 * @workflow_context Used in seed selection, cultivation planning, and harvest tracking
 * @agricultural_process Links specific cultivars to seed catalog for variety-based operations
 *
 * Database Table: master_cultivars
 * @property int $id Primary identifier for cultivar record
 * @property int $master_seed_catalog_id Reference to parent seed catalog entry
 * @property string $cultivar_name Specific variety name (e.g., 'Red Rambo', 'Black Oil')
 * @property array|null $aliases Alternative names for this cultivar
 * @property string|null $description Agricultural description and characteristics
 * @property bool $is_active Whether this cultivar is available for use
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship masterSeedCatalog BelongsTo relationship to parent seed catalog entry
 * @relationship harvests HasMany relationship to harvest records for yield tracking
 *
 * @business_rule Cultivars are specific varieties within a broader seed type
 * @business_rule Only active cultivars are available for new crop planning
 * @business_rule Aliases support multiple naming conventions from different suppliers
 *
 * @agricultural_context Example: Radish (seed type) -> Red Rambo (cultivar)
 * @agricultural_usage Enables variety-specific growing instructions and yield tracking
 */
class MasterCultivar extends Model
{
    protected $fillable = [
        'master_seed_catalog_id',
        'cultivar_name',
        'aliases',
        'description',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the master seed catalog entry this cultivar belongs to.
     * Provides parent-child relationship for agricultural variety hierarchy.
     *
     * @return BelongsTo MasterSeedCatalog relationship
     * @agricultural_hierarchy Links specific cultivar to broader seed type
     * @business_usage Used for seed ordering and variety management
     * @example Radish seed catalog -> Red Rambo cultivar
     */
    public function masterSeedCatalog(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }

    /**
     * Get the full display name combining seed type and cultivar name.
     * Provides complete agricultural variety identification for display.
     *
     * @return string Full variety name in format "Common Name (Cultivar)"
     * @agricultural_format Combines seed type with specific variety name
     * @business_usage Used in product displays, harvest records, and reporting
     * @example "Radish (Red Rambo)" or "Sunflower (Black Oil)"
     * @fallback_handling Returns cultivar name only if seed catalog missing
     */
    public function getFullNameAttribute(): string
    {
        if ($this->masterSeedCatalog) {
            return $this->masterSeedCatalog->common_name . ' (' . $this->cultivar_name . ')';
        }
        
        // Fallback if no master seed catalog is linked
        return $this->cultivar_name;
    }

    /**
     * Get all harvest records for this specific cultivar.
     * Enables agricultural performance tracking and yield analysis.
     *
     * @return HasMany Harvest records relationship
     * @agricultural_tracking Links cultivar to actual production results
     * @business_usage Used in variety performance analysis and planning
     * @analytics_context Enables yield comparison between cultivars
     */
    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

}
