<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Order Packaging Management for Agricultural Product Fulfillment
 *
 * Represents packaging requirements and specifications for orders in the
 * microgreens agricultural business. Tracks packaging types, quantities,
 * and costs associated with agricultural product delivery and presentation.
 * Essential for fulfillment planning and packaging cost management.
 *
 * @property int $id Primary key identifier
 * @property int $order_id Parent order requiring packaging
 * @property int $packaging_type_id Specific packaging type specification
 * @property int $quantity Number of packaging units required
 * @property string|null $notes Additional packaging instructions or notes
 *
 * @property-read string $total_volume Calculated total packaging volume with units
 * @property-read float $total_cost Calculated total packaging cost
 *
 * @relationship order BelongsTo Parent order requiring packaging
 * @relationship packagingType BelongsTo Packaging specifications and costs
 *
 * @business_rule Packaging quantities must align with agricultural product volumes
 * @business_rule Packaging costs automatically calculated from type specifications
 * @business_rule Activity logging tracks all packaging modifications
 * @business_rule Notes support special packaging instructions for fulfillment
 *
 * @agricultural_context Order packaging drives agricultural fulfillment workflows:
 * - Packaging types determine agricultural product presentation requirements
 * - Volume calculations ensure adequate packaging for harvested microgreens
 * - Cost tracking enables accurate agricultural product pricing and profitability
 * - Fulfillment teams use specifications for agricultural product preparation
 *
 * Packaging specifications directly impact agricultural product quality,
 * customer satisfaction, and operational efficiency in microgreens delivery.
 *
 * @usage_example
 * // Create packaging requirement
 * $packaging = OrderPackaging::create([
 *     'order_id' => $order->id,
 *     'packaging_type_id' => $clamsellType->id,
 *     'quantity' => 12,
 *     'notes' => 'Label with harvest date'
 * ]);
 *
 * // Calculate packaging requirements
 * $totalVolume = $packaging->total_volume; // "24.0 oz"
 * $totalCost = $packaging->total_cost; // $3.60
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class OrderPackaging extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'packaging_type_id',
        'quantity',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the parent order requiring packaging.
     *
     * Relationship to the order that requires this packaging specification.
     * Essential for accessing customer requirements, delivery dates, and
     * overall order context for agricultural fulfillment planning.
     *
     * @return BelongsTo<Order> Parent order requiring packaging
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the packaging type specification and costs.
     *
     * Relationship to packaging type that defines container specifications,
     * capacity, costs, and presentation requirements for agricultural
     * product fulfillment and customer delivery.
     *
     * @return BelongsTo<PackagingType> Packaging specifications and costs
     */
    public function packagingType(): BelongsTo
    {
        return $this->belongsTo(PackagingType::class);
    }

    /**
     * Calculate total packaging volume for agricultural products.
     *
     * Computes total volume capacity by multiplying packaging quantity
     * by individual container capacity. Used for ensuring adequate
     * packaging for harvested microgreens and fulfillment planning.
     *
     * @return string Formatted total volume with units (e.g., "24.0 oz")
     */
    public function getTotalVolumeAttribute(): string
    {
        return number_format($this->quantity * $this->packagingType->capacity_volume, 2) . ' ' . 
            $this->packagingType->volume_unit;
    }

    /**
     * Calculate total packaging cost for agricultural order fulfillment.
     *
     * Computes total packaging expense by multiplying quantity by
     * per-unit cost. Used in order cost calculations, profitability
     * analysis, and agricultural business financial reporting.
     *
     * @return float Total packaging cost
     */
    public function getTotalCostAttribute(): float
    {
        return $this->quantity * $this->packagingType->cost_per_unit;
    }

    /**
     * Configure activity logging for packaging change tracking.
     *
     * Defines which fields to track for agricultural fulfillment auditing
     * and operational analysis. Logs packaging modifications for
     * quality control and cost management purposes.
     *
     * @return LogOptions Activity logging configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'packaging_type_id', 'quantity', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
