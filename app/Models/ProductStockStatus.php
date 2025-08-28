<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents inventory status classifications for agricultural products,
 * tracking stock levels, availability, and product lifecycle states for
 * microgreens and agricultural product management systems.
 *
 * @business_domain Agricultural Inventory Management & Stock Control
 * @workflow_context Used in inventory tracking, order processing, and product availability
 * @agricultural_process Manages stock status for perishable agricultural products
 *
 * Database Table: product_stock_statuses
 * @property int $id Primary identifier for stock status
 * @property string $code Unique status code (in_stock, low_stock, out_of_stock, discontinued)
 * @property string $name Display name for stock status
 * @property string|null $description Status description and usage guidance
 * @property string|null $color UI color for status visualization
 * @property bool $is_active Whether this status is available for use
 * @property int $sort_order Display order for status listing
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship products HasMany relationship to Product records with this status
 *
 * @business_rule Stock statuses control product availability and ordering
 * @business_rule Discontinued products are hidden from customer interfaces
 * @business_rule Low stock triggers reordering workflows for agricultural products
 *
 * @agricultural_context Perishable products require dynamic stock status management
 * @inventory_automation Status changes trigger notifications and workflow actions
 */
class ProductStockStatus extends Model
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
     * Get all agricultural products with this stock status.
     * Links stock status to specific microgreens and agricultural products.
     *
     * @return HasMany Product collection with this stock status
     * @agricultural_context Groups products by availability and stock level
     * @business_usage Used in inventory reports and stock level analysis
     * @inventory_tracking Enables monitoring of products by availability status
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'stock_status_id');
    }

    /**
     * Get active stock statuses formatted for select field options.
     * Provides status selection for agricultural product inventory management.
     *
     * @return array Status options [id => name] for form selects
     * @agricultural_usage Used in product stock status selection interfaces
     * @business_logic Orders by sort_order then name for consistent display
     * @active_filter Only returns statuses available for use
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
     * Get query builder for active stock statuses.
     * Provides base query for available agricultural inventory statuses.
     *
     * @return \Illuminate\Database\Eloquent\Builder Query for active statuses
     * @agricultural_filter Excludes inactive/deprecated stock statuses
     * @business_usage Used in status listing and inventory management workflows
     * @sort_logic Orders by custom sort_order then alphabetically
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find stock status by unique code identifier.
     * Enables programmatic access to specific agricultural inventory statuses.
     *
     * @param string $code Status code (in_stock, low_stock, out_of_stock, discontinued)
     * @return self|null Matching stock status or null
     * @agricultural_usage Used in automated inventory status updates and validation
     * @business_logic Provides consistent status identification across workflows
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this represents in-stock availability status.
     * Identifies products fully available for agricultural order fulfillment.
     *
     * @return bool True if in-stock status
     * @agricultural_context Products available for immediate harvest and shipping
     * @business_rule In-stock products can be ordered without restrictions
     * @inventory_logic Full availability with no supply constraints
     */
    public function isInStock(): bool
    {
        return $this->code === 'in_stock';
    }

    /**
     * Check if this represents low stock warning status.
     * Identifies products approaching stock depletion for agricultural planning.
     *
     * @return bool True if low stock status
     * @agricultural_context Limited quantity available, requires restocking planning
     * @business_rule Low stock triggers reordering and production planning
     * @inventory_alert Warns of potential stockouts for agricultural products
     */
    public function isLowStock(): bool
    {
        return $this->code === 'low_stock';
    }

    /**
     * Check if this represents out of stock unavailability.
     * Identifies products currently unavailable for agricultural order fulfillment.
     *
     * @return bool True if out of stock status
     * @agricultural_context No inventory available, production planning required
     * @business_rule Out of stock products cannot be ordered until restocked
     * @inventory_blocking Prevents new orders until stock replenishment
     */
    public function isOutOfStock(): bool
    {
        return $this->code === 'out_of_stock';
    }

    /**
     * Check if this represents discontinued product status.
     * Identifies agricultural products no longer in production or available.
     *
     * @return bool True if discontinued status
     * @agricultural_context Product removed from cultivation and sales
     * @business_rule Discontinued products are hidden from customer interfaces
     * @lifecycle_management Indicates end-of-life for agricultural products
     */
    public function isDiscontinued(): bool
    {
        return $this->code === 'discontinued';
    }

    /**
     * Check if this status indicates any available inventory.
     * Determines if agricultural products can be ordered in any quantity.
     *
     * @return bool True if inventory is available for ordering
     * @agricultural_logic Both in-stock and low-stock allow ordering
     * @business_rule Available inventory enables order processing
     * @inventory_validation Used in order validation and product availability checks
     */
    public function hasInventory(): bool
    {
        return in_array($this->code, ['in_stock', 'low_stock']);
    }

    /**
     * Check if products with this status should be visible to customers.
     * Determines customer-facing visibility for agricultural product listings.
     *
     * @return bool True if visible to customers
     * @agricultural_sales Controls which products appear in customer interfaces
     * @business_rule Discontinued products are hidden from customer view
     * @customer_experience Maintains clean product catalogs without unavailable items
     */
    public function isVisibleToCustomers(): bool
    {
        return !$this->isDiscontinued();
    }
}