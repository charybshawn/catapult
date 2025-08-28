<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Order Classification Management for Agricultural Production Planning
 *
 * Represents different classifications of orders in the microgreens agricultural
 * business that drive production scheduling and resource allocation strategies.
 * Classifications determine how orders are prioritized, scheduled, and integrated
 * into the overall agricultural production workflow.
 *
 * @property int $id Primary key identifier
 * @property string $code Unique system code for classification identification
 * @property string $name Human-readable classification name for display
 * @property string|null $description Detailed classification explanation and use cases
 * @property string|null $color Display color for classification visualization
 * @property bool $is_active Whether classification is currently available for use
 * @property int|null $sort_order Display order for classification prioritization
 *
 * @relationship orders HasMany Orders assigned to this classification
 *
 * @business_rule Active classification controls availability in UI dropdowns
 * @business_rule Sort order determines classification priority in workflows
 * @business_rule Classifications drive agricultural production scheduling logic
 *
 * @agricultural_context Order classifications support agricultural production optimization:
 * - scheduled: Regular production orders following planned agricultural cycles
 * - ondemand: Rush orders requiring immediate agricultural resource allocation
 * - overflow: Additional capacity orders when agricultural production exceeds demand
 * - priority: High-priority orders that override normal agricultural scheduling
 *
 * Classifications directly impact crop planning, resource allocation, and harvest
 * timing to ensure optimal agricultural workflow efficiency and customer satisfaction.
 *
 * @usage_example
 * // Get classification for rush orders
 * $onDemand = OrderClassification::findByCode('ondemand');
 *
 * // Check if order requires priority agricultural processing
 * if ($order->classification->isPriority()) {
 *     // Adjust crop plans for priority agricultural production
 * }
 *
 * // Get all scheduled orders for production planning
 * $scheduledOrders = Order::whereHas('classification', function($q) {
 *     $q->where('code', 'scheduled');
 * })->get();
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class OrderClassification extends Model
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
     * Get orders assigned to this classification.
     *
     * Relationship to all orders using this classification for agricultural
     * production planning and scheduling. Essential for analyzing classification
     * usage patterns and optimizing agricultural workflow efficiency.
     *
     * @return HasMany<Order> Orders with this classification
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get active classifications formatted for dropdown options.
     *
     * Returns array of active order classifications for Filament select
     * components and form dropdowns. Ordered by sort_order then name
     * to reflect agricultural production priority sequences.
     *
     * @return array<int, string> Classification options keyed by ID
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
     * Find classification by unique code identifier.
     *
     * Retrieves order classification using system code for programmatic
     * access in agricultural production workflows. Used for automatic
     * classification assignment and workflow automation.
     *
     * @param string $code Unique classification code
     * @return self|null Order classification or null if not found
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if classification is for scheduled regular production.
     *
     * Determines if orders follow standard agricultural production cycles
     * with predictable timing and resource allocation. Used for regular
     * production planning and resource optimization.
     *
     * @return bool True if classification is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->code === 'scheduled';
    }

    /**
     * Check if classification is for on-demand rush orders.
     *
     * Determines if orders require immediate agricultural resource allocation
     * outside normal production cycles. Triggers priority scheduling and
     * resource reallocation for customer satisfaction.
     *
     * @return bool True if classification is on-demand
     */
    public function isOnDemand(): bool
    {
        return $this->code === 'ondemand';
    }

    /**
     * Check if classification is for overflow capacity orders.
     *
     * Determines if orders utilize excess agricultural production capacity
     * when crops exceed planned demand. Helps optimize agricultural
     * resource utilization and reduce waste.
     *
     * @return bool True if classification is overflow
     */
    public function isOverflow(): bool
    {
        return $this->code === 'overflow';
    }

    /**
     * Check if classification is for priority orders.
     *
     * Determines if orders require priority agricultural processing that
     * overrides normal scheduling. Used for VIP customers or urgent
     * business requirements that impact production workflows.
     *
     * @return bool True if classification is priority
     */
    public function isPriority(): bool
    {
        return $this->code === 'priority';
    }
}