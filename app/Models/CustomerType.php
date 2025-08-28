<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural customer classification model for sales channel management.
 * 
 * Classifies customers into categories that determine pricing structures,
 * sales terms, and business processes for agricultural product sales.
 * Enables targeted marketing and appropriate pricing strategies.
 * 
 * @property int $id Primary key identifier
 * @property string $code Unique type code for programmatic identification (retail, wholesale, farmers_market)
 * @property string $name Human-readable type name for display
 * @property string|null $description Detailed type description and business context
 * @property bool $is_active Type availability for operational use
 * @property int $sort_order Display ordering for consistent UI presentation
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customer> $customers
 * @property-read int|null $customers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * 
 * @agricultural_context Enables differentiated pricing for retail, wholesale, and farmers market sales
 * @business_rules Types determine pricing tiers, minimum orders, and sales terms
 * @sales_channels Supports multiple agricultural sales channels and pricing strategies
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class CustomerType extends Model
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
     * Get all customers classified under this type.
     * 
     * Retrieves customers that belong to this classification for targeted
     * sales management and pricing strategy implementation.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Customer>
     * @agricultural_context Returns customers with same pricing and sales terms
     * @business_usage Used for customer segmentation and sales analysis
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get all users associated with this customer type.
     * 
     * Retrieves system users that are classified under this customer type
     * for access control and pricing determination.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\User>
     * @agricultural_context Returns users with same access and pricing privileges
     * @business_usage Used for user classification and system access control
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get options for select fields (active types only).
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
     * Get all active customer types.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find customer type by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this customer type qualifies for wholesale pricing.
     * 
     * Determines if customers of this type receive wholesale pricing
     * benefits, including volume discounts and reduced margins.
     * 
     * @return bool True if qualifies for wholesale pricing
     * @agricultural_context Wholesale and farmers market customers get volume pricing
     * @business_logic Used for pricing calculations and discount application
     */
    public function qualifiesForWholesalePricing(): bool
    {
        return in_array($this->code, ['wholesale', 'farmers_market']);
    }

    /**
     * Check if this is a retail customer type.
     * 
     * Determines if this type represents direct-to-consumer retail sales
     * with standard retail pricing and terms.
     * 
     * @return bool True if this is retail customer type
     * @agricultural_context Retail customers pay full retail prices with standard terms
     * @business_logic Used for pricing determination and sales channel routing
     */
    public function isRetail(): bool
    {
        return $this->code === 'retail';
    }

    /**
     * Check if this is a wholesale customer type.
     * 
     * Determines if this type represents bulk wholesale customers
     * with volume pricing and extended payment terms.
     * 
     * @return bool True if this is wholesale customer type
     * @agricultural_context Wholesale customers buy in volume with reduced margins
     * @business_logic Used for pricing calculations and minimum order enforcement
     */
    public function isWholesale(): bool
    {
        return $this->code === 'wholesale';
    }

    /**
     * Check if this is a farmers market customer type.
     * 
     * Determines if this type represents farmers market vendors who
     * purchase for resale at local markets.
     * 
     * @return bool True if this is farmers market customer type
     * @agricultural_context Farmers market vendors get special pricing for resale
     * @business_logic Used for pricing determination and vendor support programs
     */
    public function isFarmersMarket(): bool
    {
        return $this->code === 'farmers_market';
    }
}