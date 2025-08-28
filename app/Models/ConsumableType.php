<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural consumable type classification system for organizing operational supplies.
 * 
 * Provides categorization for different types of consumables used in agricultural operations,
 * including seeds, soil, packaging, labels, and other operational materials. Enables
 * systematic organization of inventory and supplier management.
 * 
 * @property int $id Primary key identifier
 * @property string $code Unique type code for programmatic identification (seed, soil, packaging, etc.)
 * @property string $name Human-readable type name for display
 * @property string|null $description Detailed description of consumable type purpose
 * @property string|null $color UI color code for visual type identification
 * @property bool $is_active Type availability status for operational use
 * @property int $sort_order Display ordering for consistent UI presentation
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Consumable> $consumables
 * @property-read int|null $consumables_count
 * 
 * @agricultural_context Organizes supplies for microgreens production: seeds, growing medium, packaging materials
 * @business_rules Types can be deactivated but not deleted if associated consumables exist
 * @usage_pattern Used for inventory categorization, supplier organization, and operational reporting
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class ConsumableType extends Model
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
     * Get all consumables associated with this type.
     * 
     * Retrieves agricultural supplies organized under this type category,
     * including seeds, soil amendments, packaging materials, and operational supplies.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Consumable>
     * @agricultural_context Returns supplies like seed varieties, soil mixes, containers, labels
     * @business_usage Used for inventory management, supplier organization, and cost tracking
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }

    /**
     * Get options for select fields (active types only).
     * 
     * Returns formatted array of active consumable types suitable for form dropdowns
     * and UI selection components. Ordered by sort priority and name for consistency.
     * 
     * @return array<int, string> Array with type IDs as keys and names as values
     * @agricultural_context Provides UI options for categorizing agricultural supplies
     * @ui_usage Used in Filament forms for consumable type selection
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
     * Get all active consumable types query builder.
     * 
     * Returns query builder for retrieving only active consumable types,
     * ordered by sort priority and name for consistent display.
     * 
     * @return \Illuminate\Database\Eloquent\Builder Query builder for active types
     * @agricultural_context Filters to operational consumable categories only
     * @usage_pattern Commonly used for UI listings and operational reports
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find consumable type by unique code identifier.
     * 
     * Locates specific consumable type using programmatic code for system integration
     * and consistent type identification across agricultural operations.
     * 
     * @param string $code Unique type code (seed, soil, packaging, label, other)
     * @return static|null Consumable type instance or null if not found
     * @agricultural_context Enables programmatic identification of agricultural supply categories
     * @usage_pattern Used for automated categorization and system integrations
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is a packaging type consumable.
     * 
     * Determines if this type represents packaging materials used in agricultural
     * product preparation and customer delivery.
     * 
     * @return bool True if this is packaging type
     * @agricultural_context Packaging includes containers, bags, labels for microgreens delivery
     * @business_logic Used for inventory segregation and cost allocation
     */
    public function isPackaging(): bool
    {
        return $this->code === 'packaging';
    }

    /**
     * Check if this is a soil type consumable.
     * 
     * Determines if this type represents growing medium and soil amendments
     * used in agricultural production.
     * 
     * @return bool True if this is soil type
     * @agricultural_context Soil includes potting mixes, amendments, growing substrates
     * @business_logic Used for production cost tracking and supplier management
     */
    public function isSoil(): bool
    {
        return $this->code === 'soil';
    }

    /**
     * Check if this is a seed type consumable.
     * 
     * Determines if this type represents seeds and planting materials
     * used as primary inputs in agricultural production.
     * 
     * @return bool True if this is seed type
     * @agricultural_context Seeds include microgreens varieties, herbs, specialty crops
     * @business_logic Used for production planning and variety cost analysis
     */
    public function isSeed(): bool
    {
        return $this->code === 'seed';
    }

    /**
     * Check if this is a label type consumable.
     * 
     * Determines if this type represents labeling and identification materials
     * used in product packaging and traceability.
     * 
     * @return bool True if this is label type
     * @agricultural_context Labels include product tags, variety identification, compliance labels
     * @business_logic Used for packaging cost allocation and regulatory compliance
     */
    public function isLabel(): bool
    {
        return $this->code === 'label';
    }

    /**
     * Check if this is other miscellaneous type consumable.
     * 
     * Determines if this type represents miscellaneous supplies not fitting
     * standard agricultural categories.
     * 
     * @return bool True if this is other type
     * @agricultural_context Other includes tools, cleaning supplies, general operational materials
     * @business_logic Used for operational cost tracking and general inventory management
     */
    public function isOther(): bool
    {
        return $this->code === 'other';
    }
}