<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents agricultural supplier classification types for microgreens
 * production supply chain management, categorizing suppliers by the materials
 * and services they provide to support cultivation operations.
 *
 * @business_domain Agricultural Supply Chain Classification & Organization
 * @workflow_context Used in supplier management, procurement planning, and vendor selection
 * @agricultural_process Organizes suppliers by their role in production workflows
 *
 * Database Table: supplier_types
 * @property int $id Primary identifier for supplier type
 * @property string $code Unique type code (seed, soil, packaging, consumable, other)
 * @property string $name Display name for supplier type
 * @property string|null $description Type description and usage guidance
 * @property bool $is_active Whether this type is available for use
 * @property int $sort_order Display order for type listing
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship suppliers HasMany relationship to Supplier records of this type
 *
 * @business_rule Supplier types determine available products and business workflows
 * @business_rule Only active types are available for supplier classification
 * @business_rule Each type has specific validation and relationship rules
 *
 * @agricultural_categories Seed (varieties), Soil (growing media), Packaging (containers),
 *                          Consumable (supplies), Other (equipment/services)
 * @supply_chain_context Different types serve different roles in production workflow
 */
class SupplierType extends Model
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
     * Get all suppliers classified under this supplier type.
     * Links type classification to actual agricultural supplier records.
     *
     * @return HasMany Supplier collection of this type
     * @agricultural_context Groups suppliers by their role in production workflow
     * @business_usage Used in supplier filtering and type-specific operations
     * @supply_chain_organization Enables category-based supplier management
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Get active supplier types formatted for select field options.
     * Provides type selection for agricultural supplier classification.
     *
     * @return array Type options [id => name] for form selects
     * @agricultural_usage Used in supplier type selection interfaces
     * @business_logic Orders by sort_order then name for consistent display
     * @active_filter Only returns types available for current use
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
     * Get query builder for active supplier types.
     * Provides base query for available agricultural supplier classifications.
     *
     * @return \Illuminate\Database\Eloquent\Builder Query for active types
     * @agricultural_filter Excludes inactive/deprecated supplier types
     * @business_usage Used in type listing and supplier management workflows
     * @sort_logic Orders by custom sort_order then alphabetically
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find supplier type by unique code identifier.
     * Enables programmatic access to specific agricultural supplier types.
     *
     * @param string $code Type code (seed, soil, packaging, consumable, other)
     * @return self|null Matching supplier type or null
     * @agricultural_usage Used in automated supplier classification and validation
     * @business_logic Provides consistent type identification across workflows
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is the soil/growing media supplier type.
     * Identifies suppliers providing growing media for agricultural cultivation.
     *
     * @return bool True if soil supplier type
     * @agricultural_context Soil suppliers provide coconut coir, peat moss, growing blends
     * @cultivation_impact Growing media quality directly affects germination and yields
     * @business_workflow Used in recipe soil selection and inventory management
     */
    public function isSoil(): bool
    {
        return $this->code === 'soil';
    }

    /**
     * Check if this is the seed supplier type.
     * Identifies suppliers providing seed varieties for microgreens production.
     *
     * @return bool True if seed supplier type
     * @agricultural_context Seed suppliers provide microgreens varieties and cultivars
     * @production_foundation Seeds are the primary input for agricultural production
     * @business_workflow Used in seed catalog management and variety sourcing
     */
    public function isSeed(): bool
    {
        return $this->code === 'seed';
    }

    /**
     * Check if this is the consumable supplier type.
     * Identifies suppliers providing operational materials and supplies.
     *
     * @return bool True if consumable supplier type
     * @agricultural_context Consumable suppliers provide nutrients, tools, sanitizers
     * @operational_support Consumables support daily agricultural operations
     * @business_workflow Used in operational supply management and maintenance
     */
    public function isConsumable(): bool
    {
        return $this->code === 'consumable';
    }

    /**
     * Check if this is the packaging supplier type.
     * Identifies suppliers providing containers and packaging materials.
     *
     * @return bool True if packaging supplier type
     * @agricultural_context Packaging suppliers provide containers, labels, bags
     * @market_presentation Packaging affects product presentation and customer appeal
     * @business_workflow Used in packaging inventory and product fulfillment
     */
    public function isPackaging(): bool
    {
        return $this->code === 'packaging';
    }

    /**
     * Check if this is the other/miscellaneous supplier type.
     * Identifies suppliers providing specialized equipment or services.
     *
     * @return bool True if other supplier type
     * @agricultural_context Other suppliers provide equipment, services, specialty items
     * @flexibility_category Catch-all for suppliers not fitting standard categories
     * @business_workflow Used in general vendor management and specialized procurement
     */
    public function isOther(): bool
    {
        return $this->code === 'other';
    }
}