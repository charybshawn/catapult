<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents packaging containers and formats for agricultural microgreens products,
 * defining volume capacities, unit types, and cost structures for product packaging.
 * Central to product pricing, inventory management, and order fulfillment workflows.
 *
 * @business_domain Agricultural Product Packaging & Fulfillment
 * @workflow_context Used in product configuration, order processing, and pricing
 * @agricultural_process Defines how microgreens are packaged for different markets
 *
 * Database Table: packaging_types
 * @property int $id Primary identifier for packaging type
 * @property string $name Packaging type name (e.g., '4oz Clamshell', 'Bulk Bag')
 * @property int $type_category_id Reference to packaging category classification
 * @property int $unit_type_id Reference to unit measurement type
 * @property float $capacity_volume Container volume capacity
 * @property string $volume_unit Volume measurement unit (oz, g, lb)
 * @property string|null $description Packaging description and specifications
 * @property bool $is_active Whether this packaging type is available
 * @property float $cost_per_unit Cost per packaging unit for pricing calculations
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship typeCategory BelongsTo relationship to PackagingTypeCategory
 * @relationship unitType BelongsTo relationship to PackagingUnitType
 * @relationship orderPackagings HasMany relationship to order packaging records
 * @relationship priceVariations HasMany relationship to product price variations
 *
 * @business_rule Bulk packaging allows decimal quantities (weight-based)
 * @business_rule Container packaging uses integer quantities (unit-based)
 * @business_rule Display name includes volume information for customer clarity
 *
 * @agricultural_usage Supports retail, wholesale, and bulk market segments
 * @pricing_context Cost per unit enables accurate product pricing calculations
 */
class PackagingType extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type_category_id',
        'unit_type_id',
        'capacity_volume',
        'volume_unit',
        'description',
        'is_active',
        'cost_per_unit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity_volume' => 'float',
        'is_active' => 'boolean',
        'cost_per_unit' => 'float',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'display_name',
    ];

    /**
     * Get the customer-facing display name with volume information.
     * Provides clear packaging identification for agricultural product selection.
     *
     * @return string Formatted display name with volume details
     * @agricultural_context Shows container size for microgreens products
     * @business_usage Used in product displays, order forms, and customer interfaces
     * @special_handling Bulk and live tray products have custom display formats
     * @example "4oz Clamshell - 4oz" or "Bulk (by weight)"
     */
    public function getDisplayNameAttribute(): string
    {
        // Special handling for bulk and live tray products
        if ($this->name === 'Bulk') {
            return 'Bulk (by weight)';
        }
        
        if ($this->name === 'Live Tray') {
            return 'Live Tray';
        }
        
        return "{$this->name} - {$this->capacity_volume}{$this->volume_unit}";
    }

    /**
     * Get the packaging category classification for this type.
     * Links to broader packaging category for agricultural product organization.
     *
     * @return BelongsTo PackagingTypeCategory relationship
     * @agricultural_context Groups packaging by category (clamshells, bags, bulk)
     * @business_usage Used in packaging organization and product categorization
     * @category_examples Retail containers, wholesale packaging, bulk options
     */
    public function typeCategory(): BelongsTo
    {
        return $this->belongsTo(PackagingTypeCategory::class, 'type_category_id');
    }

    /**
     * Get the unit measurement type for this packaging.
     * Defines measurement standards for agricultural product packaging.
     *
     * @return BelongsTo PackagingUnitType relationship
     * @agricultural_context Determines weight vs. volume vs. count measurements
     * @business_usage Used in quantity calculations and pricing logic
     * @measurement_types Weight-based (grams), volume-based (ounces), count-based (units)
     */
    public function unitType(): BelongsTo
    {
        return $this->belongsTo(PackagingUnitType::class, 'unit_type_id');
    }

    /**
     * Get all order packaging records using this packaging type.
     * Links to actual order fulfillment and packaging requirements.
     *
     * @return HasMany OrderPackaging records
     * @agricultural_context Tracks actual packaging usage in fulfilled orders
     * @business_usage Used in packaging cost analysis and inventory planning
     * @fulfillment_tracking Enables monitoring of packaging consumption
     */
    public function orderPackagings(): HasMany
    {
        return $this->hasMany(OrderPackaging::class);
    }

    /**
     * Get all price variations that use this packaging type.
     * Links packaging to product pricing for different market segments.
     *
     * @return HasMany PriceVariation records
     * @agricultural_pricing Connects packaging to product pricing strategies
     * @business_usage Used in pricing management and market segment analysis
     * @pricing_context Different packaging enables different price points
     */
    public function priceVariations(): HasMany
    {
        return $this->hasMany(PriceVariation::class);
    }

    /**
     * Check if this packaging allows decimal quantities (weight-based sales).
     * Determines order quantity input validation for agricultural products.
     *
     * @return bool True if decimal quantities are allowed
     * @agricultural_logic Weight-based packaging (bulk) allows fractional amounts
     * @business_rule Container packaging typically uses whole unit quantities
     * @example Bulk bags allow 2.5kg, clamshells require whole unit counts
     */
    public function allowsDecimalQuantity(): bool
    {
        return $this->unitType && $this->unitType->isWeight();
    }

    /**
     * Get the appropriate quantity measurement unit for orders.
     * Provides correct unit label for agricultural product ordering.
     *
     * @return string Quantity unit ('grams' or 'units')
     * @agricultural_context Weight-based packaging uses grams, others use units
     * @business_usage Used in order forms and quantity validation
     * @unit_logic Weight packaging = grams, container packaging = units
     */
    public function getQuantityUnit(): string
    {
        if ($this->allowsDecimalQuantity()) {
            return 'grams';
        }
        
        return 'units';
    }

    /**
     * Configure activity logging for packaging type changes.
     * Tracks modifications to critical agricultural packaging data.
     *
     * @return LogOptions Activity logging configuration
     * @audit_purpose Maintains history of packaging changes for cost analysis
     * @logged_fields Tracks name, capacity, units, description, status, and costs
     * @business_usage Used for packaging cost tracking and change auditing
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'capacity_volume', 'volume_unit', 'description', 'is_active', 'cost_per_unit'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
