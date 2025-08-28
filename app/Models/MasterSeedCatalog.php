<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Master seed catalog entry representing a fundamental seed type for agricultural
 * production. This model serves as the parent classification for all cultivar varieties
 * and provides the foundation for seed management and agricultural planning.
 *
 * @business_domain Agricultural Seed Classification & Management
 * @workflow_context Used in seed ordering, cultivar management, and production planning
 * @agricultural_process Central repository for seed types with variety-specific cultivars
 *
 * Database Table: master_seed_catalog
 * @property int $id Primary identifier for seed catalog entry
 * @property string $common_name Primary seed type name (e.g., 'Radish', 'Sunflower')
 * @property int|null $cultivar_id Optional default/primary cultivar reference
 * @property string|null $category Agricultural category classification
 * @property array|null $aliases Alternative names and synonyms
 * @property string|null $growing_notes Agricultural cultivation guidance
 * @property string|null $description Detailed seed type description
 * @property bool $is_active Whether this seed type is available for use
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship cultivar BelongsTo relationship to default/primary cultivar
 * @relationship cultivars HasMany relationship to all available cultivars
 * @relationship activeCultivars HasMany relationship to active cultivars only
 * @relationship primaryCultivar HasOne relationship to first/primary cultivar
 * @relationship consumables HasMany relationship to seed inventory items
 * @relationship seedEntries HasMany relationship to seed entry records
 *
 * @business_rule Seed catalog entries can have multiple cultivar varieties
 * @business_rule Only active seed types are available for new operations
 * @business_rule Growing notes provide cultivation guidance for all cultivars
 *
 * @agricultural_hierarchy Top-level seed classification (Radish > Red Rambo cultivar)
 * @agricultural_usage Foundation for variety selection and agricultural planning
 */
class MasterSeedCatalog extends Model
{
    protected $table = 'master_seed_catalog';
    
    protected $fillable = [
        'common_name',
        'cultivar_id',
        'category',
        'aliases',
        'growing_notes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default/primary cultivar for this seed type.
     * Provides quick access to the preferred variety for agricultural operations.
     *
     * @return BelongsTo MasterCultivar relationship
     * @agricultural_context Links to preferred cultivar for this seed type
     * @business_usage Used when no specific cultivar is selected
     * @nullable Can be null if no default cultivar is set
     */
    public function cultivar(): BelongsTo
    {
        return $this->belongsTo(MasterCultivar::class, 'cultivar_id');
    }

    /**
     * Get all cultivar varieties available for this seed type.
     * Provides complete variety selection for agricultural planning.
     *
     * @return HasMany MasterCultivar collection
     * @agricultural_hierarchy Shows all variety options under this seed type
     * @business_usage Used in variety selection and cultivation planning
     * @example Radish seed type -> [Red Rambo, Cherry Belle, Daikon] cultivars
     */
    public function cultivars(): HasMany
    {
        return $this->hasMany(MasterCultivar::class, 'master_seed_catalog_id');
    }

    /**
     * Get only active cultivar varieties for this seed type.
     * Filters to available varieties for current agricultural operations.
     *
     * @return HasMany Active MasterCultivar collection
     * @agricultural_filter Shows only cultivars available for new plantings
     * @business_usage Used in crop planning and variety selection interfaces
     * @performance_note Applies database-level filtering for efficiency
     */
    public function activeCultivars(): HasMany
    {
        return $this->hasMany(MasterCultivar::class, 'master_seed_catalog_id')->where('is_active', true);
    }
    
    /**
     * Get the first/primary cultivar for this seed type.
     * Provides fallback variety selection for agricultural workflows.
     *
     * @return HasOne First MasterCultivar relationship
     * @agricultural_context Selects first available cultivar as default
     * @business_usage Used when automatic variety selection is needed
     * @fallback_logic Returns first cultivar if no specific default set
     */
    public function primaryCultivar(): HasOne
    {
        return $this->hasOne(MasterCultivar::class, 'master_seed_catalog_id');
    }

    /**
     * Get seed inventory items for this catalog entry.
     * Links to physical seed stock and inventory management.
     *
     * @return HasMany Consumable seed inventory items
     * @agricultural_context Connects seed types to physical inventory
     * @business_usage Used in seed ordering and inventory tracking
     * @inventory_management Enables stock level monitoring for seed types
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }

    /**
     * Get seed entry records for data import and catalog management.
     * Links to seed catalog data import and management workflows.
     *
     * @return HasMany SeedEntry import records
     * @agricultural_context Connects to seed data import processes
     * @business_usage Used in catalog maintenance and data synchronization
     * @data_management Enables tracking of catalog updates and imports
     */
    public function seedEntries(): HasMany
    {
        return $this->hasMany(SeedEntry::class);
    }

    /**
     * Get the cultivar name from the related default cultivar.
     * Provides quick access to default variety name for display.
     *
     * @return string|null Default cultivar name or null
     * @agricultural_context Shows preferred variety name for this seed type
     * @business_usage Used in quick displays and default selections
     * @nullable Returns null if no default cultivar is configured
     */
    public function getCultivarNameAttribute(): ?string
    {
        return $this->cultivar?->cultivar_name;
    }
}
