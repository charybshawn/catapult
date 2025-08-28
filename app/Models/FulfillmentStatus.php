<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fulfillment Status Management for Agricultural Order Processing
 *
 * Represents the various stages of order fulfillment in agricultural microgreens
 * production from initial order placement through harvest completion and delivery.
 * Provides workflow control and business rule enforcement throughout the agricultural
 * production and fulfillment lifecycle.
 *
 * @property int $id Primary key identifier
 * @property string $code Unique system code for status identification
 * @property string $name Human-readable status name for display
 * @property string|null $description Detailed status explanation and business rules
 * @property string|null $color Display color for status visualization in UI
 * @property bool $is_active Whether status is currently available for use
 * @property int|null $sort_order Display order for status sequences
 * @property bool $is_final Whether status represents completed fulfillment
 * @property bool $allows_modifications Whether orders can be modified in this status
 *
 * @relationship orders HasMany Orders currently in this fulfillment status
 *
 * @business_rule Final statuses prevent further order modifications
 * @business_rule Active status controls availability in UI dropdowns
 * @business_rule Sort order determines status progression workflow
 * @business_rule Modification rules enforce agricultural production constraints
 *
 * @agricultural_context Fulfillment statuses track agricultural production stages:
 * - pending: Order received, planning agricultural production
 * - planned: Crop plans created, seeds allocated for growing
 * - growing: Crops planted and in agricultural production cycle  
 * - harvesting: Microgreens ready for harvest from growing trays
 * - packaging: Agricultural products being prepared for delivery
 * - ready: Orders complete and ready for customer pickup/delivery
 * - delivered: Orders successfully delivered to customers
 * - cancelled: Orders cancelled before or during agricultural production
 *
 * Each status enforces specific business rules around agricultural timing,
 * resource allocation, and customer communication requirements.
 *
 * @usage_example
 * // Get status for growing phase
 * $growingStatus = FulfillmentStatus::getByCode('growing');
 *
 * // Check if order can be modified
 * if ($order->fulfillmentStatus->allowsModifications()) {
 *     // Allow order modifications during agricultural planning
 * }
 *
 * // Get all final statuses for reporting
 * $finalStatuses = FulfillmentStatus::final()->get();
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class FulfillmentStatus extends Model
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
        'is_final',
        'allows_modifications',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'allows_modifications' => 'boolean',
    ];

    /**
     * Get orders associated with this fulfillment status.
     *
     * Relationship to all orders currently in this fulfillment stage.
     * Essential for tracking agricultural production workflow progress
     * and generating status-based reporting and analytics.
     *
     * @return HasMany<Order> Orders in this fulfillment status
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get active fulfillment statuses for dropdown options.
     *
     * Returns array of active fulfillment statuses formatted for Filament
     * select components and form dropdowns. Ordered by sort_order to
     * reflect agricultural production workflow sequence.
     *
     * @return array<int, string> Status options keyed by ID
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get fulfillment status by unique code identifier.
     *
     * Retrieves fulfillment status using system code for programmatic access.
     * Used throughout agricultural workflow automation to reference specific
     * status stages without relying on database IDs.
     *
     * @param string $code Unique status code (e.g., 'growing', 'harvesting')
     * @return self|null Fulfillment status or null if not found
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope query to only active fulfillment statuses.
     *
     * Query scope for filtering to currently available fulfillment statuses
     * used in agricultural production workflows. Excludes disabled statuses
     * that may be retained for historical order tracking.
     *
     * @param Builder $query Query builder instance
     * @return Builder Filtered query for active statuses only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to only final fulfillment statuses.
     *
     * Query scope for filtering to completion statuses that represent
     * finished agricultural production and delivery. Used for order
     * completion reporting and workflow analytics.
     *
     * @param Builder $query Query builder instance
     * @return Builder Filtered query for final statuses only
     */
    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('is_final', true);
    }

    /**
     * Check if orders can be modified in this fulfillment status.
     *
     * Determines whether order modifications are permitted based on
     * agricultural production stage. Early stages (pending, planned)
     * allow changes while later stages (harvesting, delivered) prevent
     * modifications that would disrupt agricultural workflows.
     *
     * @return bool True if order modifications are allowed
     */
    public function allowsModifications(): bool
    {
        return $this->allows_modifications;
    }

    /**
     * Check if this is a final completion status.
     *
     * Determines whether this status represents completed agricultural
     * production and fulfillment. Final statuses indicate orders have
     * progressed through the complete production cycle and cannot be
     * returned to earlier workflow stages.
     *
     * @return bool True if status represents fulfillment completion
     */
    public function isFinal(): bool
    {
        return $this->is_final;
    }
}