<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents categories for agricultural product packaging types, providing
 * hierarchical organization of packaging options for microgreens and related
 * agricultural products. Enables market segment targeting and operational efficiency.
 *
 * @business_domain Agricultural Packaging Classification & Organization
 * @workflow_context Used in product setup, packaging selection, and market targeting
 * @agricultural_process Organizes packaging by retail, wholesale, and bulk segments
 *
 * Database Table: packaging_type_categories
 * @property int $id Primary identifier for packaging category
 * @property string $code Unique category code (clamshell, bag, box, jar, tray, bulk)
 * @property string $name Display name for category
 * @property string|null $description Category description and usage notes
 * @property string|null $color UI color for category visualization
 * @property bool $is_active Whether this category is available
 * @property int $sort_order Display order for category listing
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship packagingTypes HasMany relationship to specific packaging types
 *
 * @business_rule Categories organize packaging by market segment and handling needs
 * @business_rule Retail-suitable categories support consumer-facing products
 * @business_rule Some categories require careful handling (glass jars, clamshells)
 *
 * @agricultural_segments Retail (clamshells, jars), Wholesale (boxes, trays), Bulk (bags)
 * @market_context Different categories serve different customer types and use cases
 */
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
     * Get all packaging types within this category.
     * Provides specific packaging options for agricultural product configuration.
     *
     * @return HasMany PackagingType collection
     * @agricultural_context Groups specific containers by category type
     * @business_usage Used in packaging selection and product setup workflows
     * @example Clamshell category -> [4oz Clamshell, 8oz Clamshell, 16oz Clamshell]
     */
    public function packagingTypes(): HasMany
    {
        return $this->hasMany(PackagingType::class, 'type_category_id');
    }

    /**
     * Get active categories formatted for select field options.
     * Provides category selection for agricultural packaging configuration.
     *
     * @return array Category options [id => name] for form selects
     * @agricultural_usage Used in packaging category selection interfaces
     * @business_logic Orders by sort_order then name for consistent display
     * @active_filter Only returns categories available for use
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
     * Get query builder for active packaging categories.
     * Provides base query for available agricultural packaging categories.
     *
     * @return \Illuminate\Database\Eloquent\Builder Query for active categories
     * @agricultural_filter Excludes inactive/deprecated packaging categories
     * @business_usage Used in category listing and selection workflows
     * @sort_logic Orders by custom sort_order then alphabetically
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find packaging category by unique code identifier.
     * Enables programmatic access to specific agricultural packaging categories.
     *
     * @param string $code Category code (clamshell, bag, box, jar, tray, bulk)
     * @return self|null Matching category or null
     * @agricultural_usage Used in automated packaging selection and validation
     * @business_logic Provides consistent category identification across workflows
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is the clamshell packaging category.
     * Identifies retail-focused clear plastic container packaging.
     *
     * @return bool True if clamshell category
     * @agricultural_context Clamshells are primary retail packaging for microgreens
     * @market_segment Retail and direct-to-consumer sales
     * @handling_notes Requires careful handling to prevent crushing
     */
    public function isClamshell(): bool
    {
        return $this->code === 'clamshell';
    }

    /**
     * Check if this is the bag packaging category.
     * Identifies flexible packaging suitable for bulk agricultural products.
     *
     * @return bool True if bag category
     * @agricultural_context Bags are efficient for bulk and wholesale markets
     * @market_segment Wholesale and food service customers
     * @storage_benefits Space-efficient and cost-effective packaging
     */
    public function isBag(): bool
    {
        return $this->code === 'bag';
    }

    /**
     * Check if this is the box packaging category.
     * Identifies rigid container packaging for agricultural product protection.
     *
     * @return bool True if box category
     * @agricultural_context Boxes provide protection for delicate microgreens
     * @market_segment Premium retail and restaurant markets
     * @shipping_benefits Stackable and protective for transportation
     */
    public function isBox(): bool
    {
        return $this->code === 'box';
    }

    /**
     * Check if this is the jar packaging category.
     * Identifies glass or rigid container packaging for premium products.
     *
     * @return bool True if jar category
     * @agricultural_context Jars are premium packaging for specialty microgreens
     * @market_segment High-end retail and gourmet markets
     * @handling_requirements Requires careful handling due to breakage risk
     */
    public function isJar(): bool
    {
        return $this->code === 'jar';
    }

    /**
     * Check if this is the tray packaging category.
     * Identifies flat container packaging for agricultural products.
     *
     * @return bool True if tray category
     * @agricultural_context Trays support both retail and wholesale markets
     * @market_segment Versatile packaging for multiple customer types
     * @operational_benefit Easy to stack and transport efficiently
     */
    public function isTray(): bool
    {
        return $this->code === 'tray';
    }

    /**
     * Check if this is the bulk packaging category.
     * Identifies weight-based packaging for large quantity agricultural sales.
     *
     * @return bool True if bulk category
     * @agricultural_context Bulk packaging enables weight-based pricing
     * @market_segment Food service, restaurants, and large-volume customers
     * @pricing_model Allows decimal quantities and weight-based calculations
     */
    public function isBulk(): bool
    {
        return $this->code === 'bulk';
    }

    /**
     * Check if this category is suitable for retail display.
     * Identifies packaging categories appropriate for consumer-facing sales.
     *
     * @return bool True if retail-suitable
     * @agricultural_market Determines which packaging works for direct sales
     * @business_logic Retail requires attractive, protective, displayable packaging
     * @customer_context Consumer-friendly packaging with visual appeal
     */
    public function isRetailSuitable(): bool
    {
        return in_array($this->code, ['clamshell', 'box', 'jar', 'tray']);
    }

    /**
     * Check if this category requires careful handling during operations.
     * Identifies packaging that needs special care to prevent damage.
     *
     * @return bool True if careful handling required
     * @agricultural_logistics Determines handling procedures for packaging
     * @business_impact Affects labor costs and operational procedures
     * @damage_prevention Identifies fragile packaging needing extra care
     */
    public function requiresCarefulHandling(): bool
    {
        return in_array($this->code, ['clamshell', 'jar']);
    }
}