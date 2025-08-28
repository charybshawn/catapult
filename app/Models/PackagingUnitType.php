<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents measurement unit types for agricultural product packaging,
 * defining whether packaging is measured by count, weight, volume, or other
 * metrics. Critical for pricing calculations and quantity management.
 *
 * @business_domain Agricultural Packaging Measurement Standards
 * @workflow_context Used in packaging configuration, pricing, and order processing
 * @agricultural_process Determines how agricultural products are measured and sold
 *
 * Database Table: packaging_unit_types
 * @property int $id Primary identifier for unit type
 * @property string $code Unique unit type code (count, weight, volume)
 * @property string $name Display name for unit type
 * @property string|null $description Unit type description and usage notes
 * @property string|null $color UI color for unit type visualization
 * @property bool $is_active Whether this unit type is available
 * @property int $sort_order Display order for unit type listing
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship packagingTypes HasMany relationship to PackagingType records
 *
 * @business_rule Weight-based units allow fractional quantities for bulk sales
 * @business_rule Count-based units require whole number quantities
 * @business_rule Unit types determine pricing and measurement precision
 *
 * @agricultural_examples Count (clamshells, trays), Weight (bulk bags), Volume (jars)
 * @pricing_impact Different unit types enable different pricing strategies
 */
class PackagingUnitType extends Model
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
     * Get all packaging types that use this unit measurement type.
     * Links unit types to specific agricultural packaging configurations.
     *
     * @return HasMany PackagingType collection
     * @agricultural_context Groups packaging by measurement methodology
     * @business_usage Used in packaging organization and pricing logic
     * @example Weight unit type -> [Bulk Bags, Large Containers] packaging
     */
    public function packagingTypes(): HasMany
    {
        return $this->hasMany(PackagingType::class, 'unit_type_id');
    }

    /**
     * Get active unit types formatted for select field options.
     * Provides measurement type selection for agricultural packaging setup.
     *
     * @return array Unit type options [id => name] for form selects
     * @agricultural_usage Used in packaging unit type selection interfaces
     * @business_logic Orders by sort_order then name for consistent display
     * @active_filter Only returns unit types available for use
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
     * Get query builder for active packaging unit types.
     * Provides base query for available agricultural measurement types.
     *
     * @return \Illuminate\Database\Eloquent\Builder Query for active unit types
     * @agricultural_filter Excludes inactive/deprecated measurement types
     * @business_usage Used in unit type listing and selection workflows
     * @sort_logic Orders by custom sort_order then alphabetically
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find packaging unit type by unique code identifier.
     * Enables programmatic access to specific agricultural measurement types.
     *
     * @param string $code Unit type code (count, weight, volume)
     * @return self|null Matching unit type or null
     * @agricultural_usage Used in automated packaging configuration and validation
     * @business_logic Provides consistent unit type identification across workflows
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is a count-based measurement unit type.
     * Identifies discrete item counting for agricultural packaging.
     *
     * @return bool True if count-based unit type
     * @agricultural_context Count units for individual containers (clamshells, trays)
     * @business_rule Count-based packaging requires whole number quantities
     * @pricing_logic Count units enable per-container pricing strategies
     */
    public function isCount(): bool
    {
        return $this->code === 'count';
    }

    /**
     * Check if this is a weight-based measurement unit type.
     * Identifies mass-based measurement for agricultural bulk products.
     *
     * @return bool True if weight-based unit type
     * @agricultural_context Weight units for bulk agricultural products
     * @business_rule Weight-based packaging allows fractional quantities
     * @pricing_logic Weight units enable per-gram or per-pound pricing
     */
    public function isWeight(): bool
    {
        return $this->code === 'weight';
    }

    /**
     * Check if this unit type requires precise measurement equipment.
     * Determines operational requirements for agricultural packaging.
     *
     * @return bool True if precise measurement required
     * @agricultural_operations Weight-based units need scales for accuracy
     * @business_impact Affects packaging workflow and equipment needs
     * @quality_control Precise measurement ensures consistent product quality
     */
    public function requiresPreciseMeasurement(): bool
    {
        return $this->isWeight();
    }

    /**
     * Check if this unit type supports fractional quantity orders.
     * Determines order quantity validation for agricultural products.
     *
     * @return bool True if fractional quantities allowed
     * @agricultural_sales Weight-based units support partial quantities (2.5kg)
     * @business_rule Count-based units require whole numbers (3 containers)
     * @order_processing Affects quantity input validation and pricing calculations
     */
    public function allowsFractionalQuantities(): bool
    {
        return $this->isWeight();
    }

    /**
     * Get typical measurement units available for this unit type.
     * Provides standard measurement options for agricultural packaging.
     *
     * @return array List of typical measurement units
     * @agricultural_standards Shows appropriate units for each measurement type
     * @business_usage Used in unit selection and measurement configuration
     * @unit_examples Count: pieces/containers, Weight: grams/pounds
     */
    public function getTypicalUnits(): array
    {
        return match ($this->code) {
            'count' => ['unit', 'piece', 'container'],
            'weight' => ['gram', 'kilogram', 'ounce', 'pound'],
            default => ['unit'],
        };
    }
}