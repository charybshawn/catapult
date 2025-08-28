<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural consumable measurement units system for precise inventory and operational tracking.
 * 
 * Provides standardized units of measurement for agricultural supplies including weight,
 * volume, and count units. Supports unit conversions and categorization for accurate
 * inventory management and cost calculation in agricultural operations.
 * 
 * @property int $id Primary key identifier
 * @property string $code Unique unit code for programmatic identification
 * @property string $name Human-readable unit name for display
 * @property string|null $symbol Unit symbol abbreviation (g, kg, L, etc.)
 * @property string|null $description Detailed unit description and usage context
 * @property string $category Unit category grouping (weight, volume, count)
 * @property float|null $conversion_factor Conversion multiplier to base unit within category
 * @property string|null $base_unit Base unit identifier for conversion calculations
 * @property bool $is_active Unit availability status for operational use
 * @property int $sort_order Display ordering for consistent UI presentation
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Consumable> $consumables
 * @property-read int|null $consumables_count
 * @property-read string $display_name Unit name with symbol for UI display
 * 
 * @agricultural_context Enables precise measurement of seeds (grams), soil (liters), packaging (pieces)
 * @business_rules Conversion only allowed within same category, deactivated units preserved for historical data
 * @usage_pattern Used for inventory tracking, cost calculations, and supplier order management
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class ConsumableUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
        'category',
        'conversion_factor',
        'base_unit',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:10',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all consumables measured in this unit.
     * 
     * Retrieves agricultural supplies that use this unit of measurement,
     * enabling tracking of inventory quantities and cost calculations.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Consumable>
     * @agricultural_context Returns supplies measured in specific units (seeds in grams, soil in liters)
     * @business_usage Used for inventory valuation, cost analysis, and supplier order calculations
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }

    /**
     * Get options for select fields (active units only).
     * 
     * Returns formatted array of active measurement units suitable for form dropdowns
     * and UI selection components. Ordered by priority and name for consistency.
     * 
     * @return array<int, string> Array with unit IDs as keys and names as values
     * @agricultural_context Provides UI options for measuring agricultural supplies
     * @ui_usage Used in Filament forms for consumable unit selection
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
     * Get unit options organized by measurement category.
     * 
     * Returns active units grouped by category (weight, volume, count) for
     * structured UI presentation and logical unit selection.
     * 
     * @return array<string, array<int, string>> Nested array with categories containing unit options
     * @agricultural_context Organizes units by type: weight (g,kg), volume (L,mL), count (pieces,boxes)
     * @ui_usage Used for categorized dropdowns in consumable measurement selection
     */
    public static function optionsByCategory(): array
    {
        return static::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(function ($units) {
                return $units->pluck('name', 'id')->toArray();
            })
            ->toArray();
    }

    /**
     * Get all active measurement units query builder.
     * 
     * Returns query builder for retrieving only active units, ordered by category,
     * sort priority, and name for consistent operational use.
     * 
     * @return \Illuminate\Database\Eloquent\Builder Query builder for active units
     * @agricultural_context Filters to operational measurement units only
     * @usage_pattern Commonly used for UI listings and measurement calculations
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find measurement unit by unique code identifier.
     * 
     * Locates specific unit using programmatic code for system integration
     * and consistent unit identification across agricultural operations.
     * 
     * @param string $code Unique unit code identifier
     * @return static|null Unit instance or null if not found
     * @agricultural_context Enables programmatic unit identification for automated calculations
     * @usage_pattern Used for unit conversion, system integrations, and data imports
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get active units filtered by measurement category.
     * 
     * Returns query builder for units within specific category (weight, volume, count)
     * for targeted unit selection in agricultural operations.
     * 
     * @param string $category Unit category (weight, volume, count)
     * @return \Illuminate\Database\Eloquent\Builder Query builder for category units
     * @agricultural_context Filters units by measurement type for appropriate selection
     * @usage_pattern Used when specific measurement category is required for operations
     */
    public static function byCategory(string $category)
    {
        return static::where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Convert amount from this unit to another unit within same category.
     * 
     * Performs unit conversion using conversion factors via base unit method.
     * Only allows conversions within same measurement category for accuracy.
     * 
     * @param float $amount Quantity to convert in current unit
     * @param \App\Models\ConsumableUnit $targetUnit Target unit for conversion
     * @return float|null Converted amount or null if conversion impossible
     * @agricultural_context Enables conversions like grams to kilograms, milliliters to liters
     * @business_logic Used for inventory calculations, cost analysis, and supplier orders
     * @throws null Returns null for cross-category conversions or missing conversion factors
     */
    public function convertTo(float $amount, ConsumableUnit $targetUnit): ?float
    {
        // Can only convert within the same category
        if ($this->category !== $targetUnit->category) {
            return null;
        }

        // If both units have conversion factors, convert via base unit
        if ($this->conversion_factor && $targetUnit->conversion_factor) {
            $baseAmount = $amount * $this->conversion_factor;
            return $baseAmount / $targetUnit->conversion_factor;
        }

        return null;
    }

    /**
     * Convert amount to category base unit for standardized calculations.
     * 
     * Converts measurement to base unit within category using conversion factor
     * for standardized inventory and cost calculations.
     * 
     * @param float $amount Quantity to convert to base unit
     * @return float|null Amount in base unit or null if no conversion factor
     * @agricultural_context Standardizes measurements for accurate agricultural calculations
     * @business_logic Used for inventory valuation and comparative cost analysis
     */
    public function toBaseUnit(float $amount): ?float
    {
        if (!$this->conversion_factor) {
            return null;
        }

        return $amount * $this->conversion_factor;
    }

    /**
     * Check if this is a weight measurement unit.
     * 
     * Determines if this unit measures weight/mass for agricultural supplies
     * like seeds, soil amendments, and bulk materials.
     * 
     * @return bool True if this is weight category unit
     * @agricultural_context Weight units include grams, kilograms for seeds and soil
     * @business_logic Used for weight-based cost calculations and shipping estimates
     */
    public function isWeight(): bool
    {
        return $this->category === 'weight';
    }

    /**
     * Check if this is a volume measurement unit.
     * 
     * Determines if this unit measures volume for liquid agricultural supplies
     * and bulk materials measured by volume.
     * 
     * @return bool True if this is volume category unit
     * @agricultural_context Volume units include liters, milliliters for liquid fertilizers
     * @business_logic Used for volume-based inventory and application rate calculations
     */
    public function isVolume(): bool
    {
        return $this->category === 'volume';
    }

    /**
     * Check if this is a count measurement unit.
     * 
     * Determines if this unit measures discrete countable items like
     * packages, containers, and individual pieces.
     * 
     * @return bool True if this is count category unit
     * @agricultural_context Count units include pieces, boxes, packages for discrete items
     * @business_logic Used for packaging calculations and discrete item inventory
     */
    public function isCount(): bool
    {
        return $this->category === 'count';
    }

    /**
     * Get formatted display name with unit symbol for UI presentation.
     * 
     * Returns unit name with symbol in parentheses for clear identification
     * in user interfaces and reports.
     * 
     * @return string Formatted unit name with symbol or name only if no symbol
     * @agricultural_context Examples: "Grams (g)", "Liters (L)", "Pieces"
     * @ui_usage Used in forms, tables, and reports for clear unit identification
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->symbol ? "{$this->name} ({$this->symbol})" : $this->name;
    }
}