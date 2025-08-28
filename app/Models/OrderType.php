<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Order Type Management for Agricultural Business Models
 *
 * Represents different types of orders in the microgreens agricultural business
 * that determine pricing structures, fulfillment workflows, and customer
 * relationship management strategies. Order types drive agricultural production
 * planning and resource allocation optimization.
 *
 * @property int $id Primary key identifier
 * @property string $code Unique system code for type identification
 * @property string $name Human-readable type name for display
 * @property string|null $description Detailed type explanation and business rules
 * @property string|null $color Display color for type visualization in UI
 * @property bool $is_active Whether type is currently available for use
 * @property int|null $sort_order Display order for type prioritization
 *
 * @relationship orders HasMany Orders of this type
 *
 * @business_rule Active type controls availability in UI dropdowns
 * @business_rule Sort order determines type priority in workflows
 * @business_rule Order types drive pricing and fulfillment workflows
 *
 * @agricultural_context Order types support different agricultural business models:
 * - standard: Individual one-time orders with standard agricultural processing
 * - subscription: Recurring orders with predictable agricultural production cycles
 * - b2b: Business-to-business orders with volume pricing and specialized workflows
 *
 * Each type impacts agricultural production planning, resource allocation,
 * customer communication, and financial processing to optimize business
 * operations and customer satisfaction across different market segments.
 *
 * @usage_example
 * // Check if order requires subscription processing
 * if ($order->orderType->isSubscription()) {
 *     // Set up recurring agricultural production planning
 * }
 *
 * // Get all B2B orders for wholesale planning
 * $b2bOrders = Order::whereHas('orderType', function($q) {
 *     $q->where('code', 'b2b');
 * })->get();
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class OrderType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get orders of this type.
     *
     * Relationship to all orders using this type for agricultural business
     * model analysis and production planning. Essential for analyzing type
     * usage patterns and optimizing agricultural workflow efficiency.
     *
     * @return HasMany<Order> Orders of this type
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get active order types formatted for dropdown options.
     *
     * Returns array of active order types for Filament select components
     * and form dropdowns. Ordered by sort_order then name to reflect
     * agricultural business model priority sequences.
     *
     * @return array<int, string> Order type options keyed by ID
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
     * Find order type by unique code identifier.
     *
     * Retrieves order type using system code for programmatic access
     * in agricultural business workflows. Used for automatic type
     * assignment and workflow automation.
     *
     * @param string $code Unique order type code
     * @return self|null Order type or null if not found
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if type is for standard one-time orders.
     *
     * Determines if orders use standard agricultural production processes
     * with individual pricing and fulfillment. Used for regular customer
     * orders without special business arrangements.
     *
     * @return bool True if type is standard
     */
    public function isStandard(): bool
    {
        return $this->code === 'standard';
    }

    /**
     * Check if type is for recurring subscription orders.
     *
     * Determines if orders require recurring agricultural production cycles
     * with predictable scheduling and customer billing. Enables automated
     * production planning and customer relationship management.
     *
     * @return bool True if type is subscription
     */
    public function isSubscription(): bool
    {
        return $this->code === 'subscription';
    }

    /**
     * Check if type is for business-to-business orders.
     *
     * Determines if orders require B2B agricultural processing with volume
     * pricing, specialized packaging, and business-focused workflows.
     * Used for wholesale and commercial customer relationships.
     *
     * @return bool True if type is B2B
     */
    public function isB2B(): bool
    {
        return $this->code === 'b2b';
    }
}