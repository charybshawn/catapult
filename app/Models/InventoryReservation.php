<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inventory Reservation Management for Agricultural Product Sales
 *
 * Represents temporary inventory allocations for agricultural microgreens products
 * during order processing, ensuring product availability while preventing
 * overselling. Essential for inventory control and customer order fulfillment
 * in agricultural business operations.
 *
 * @property int $id Primary key identifier
 * @property int $product_inventory_id Specific inventory batch being reserved
 * @property int $product_id Agricultural product being reserved
 * @property int $order_id Order requiring this inventory reservation
 * @property int $order_item_id Specific order line item requiring reservation
 * @property float $quantity Amount of product reserved (in product units)
 * @property int $status_id Current reservation status
 * @property \DateTime|null $expires_at Reservation expiration timestamp
 * @property \DateTime|null $fulfilled_at Timestamp when reservation was fulfilled
 *
 * @relationship productInventory BelongsTo Inventory batch being reserved
 * @relationship product BelongsTo Agricultural product being reserved
 * @relationship order BelongsTo Order requiring this reservation
 * @relationship orderItem BelongsTo Specific order line item
 * @relationship status BelongsTo Current reservation status and workflow state
 *
 * @business_rule Reservations prevent inventory overselling during order processing
 * @business_rule Expired reservations automatically release reserved inventory
 * @business_rule Fulfilled reservations convert to actual inventory transactions
 * @business_rule Cancelled reservations immediately release reserved inventory
 *
 * @agricultural_context Inventory reservations ensure agricultural product availability:
 * - Reservations hold microgreens inventory while orders are being processed
 * - Expiration prevents indefinite inventory locks on agricultural products
 * - Fulfillment converts reservations to actual sales and delivery scheduling
 * - Release mechanisms ensure optimal agricultural inventory utilization
 *
 * Each reservation directly impacts agricultural inventory availability and
 * production planning to ensure customer demand can be fulfilled efficiently.
 *
 * @usage_example
 * // Create inventory reservation for order item
 * $reservation = InventoryReservation::create([
 *     'product_inventory_id' => $inventory->id,
 *     'product_id' => $product->id,
 *     'order_id' => $order->id,
 *     'order_item_id' => $orderItem->id,
 *     'quantity' => 2.5,
 *     'expires_at' => now()->addHours(24)
 * ]);
 *
 * // Process reservation fulfillment
 * if ($reservation->isActive()) {
 *     $reservation->fulfill(); // Converts to sale
 * }
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class InventoryReservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_inventory_id',
        'product_id',
        'order_id',
        'order_item_id',
        'quantity',
        'status_id',
        'expires_at',
        'fulfilled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'expires_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        // When a reservation is deleted, release the reserved quantity
        static::deleting(function ($reservation) {
            if ($reservation->status?->holdsInventory()) {
                $reservation->release();
            }
        });
    }

    /**
     * Get the agricultural inventory batch being reserved.
     *
     * Relationship to specific inventory batch containing the agricultural
     * products being reserved. Essential for tracking inventory levels
     * and ensuring accurate agricultural stock management.
     *
     * @return BelongsTo<ProductInventory> Inventory batch being reserved
     */
    public function productInventory(): BelongsTo
    {
        return $this->belongsTo(ProductInventory::class);
    }

    /**
     * Get the agricultural product being reserved.
     *
     * Relationship to the specific microgreens product that requires
     * inventory reservation. Used for agricultural inventory tracking
     * and production planning based on reserved quantities.
     *
     * @return BelongsTo<Product> Agricultural product being reserved
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the order this reservation is for.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the order item this reservation is for.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * Get the status for this reservation.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(InventoryReservationStatus::class, 'status_id');
    }

    /**
     * Check if the reservation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if reservation is active and holding agricultural inventory.
     *
     * Determines if reservation is currently valid and holding inventory
     * for agricultural product orders. Active reservations prevent
     * overselling and ensure customer order fulfillment.
     *
     * @return bool True if reservation is active and not expired
     */
    public function isActive(): bool
    {
        return $this->status?->isActive() && !$this->isExpired();
    }

    /**
     * Confirm the reservation.
     */
    public function confirm(): void
    {
        $confirmedStatus = InventoryReservationStatus::findByCode('confirmed');
        $this->update(['status_id' => $confirmedStatus->id]);
    }

    /**
     * Fulfill reservation by converting to actual agricultural product sale.
     *
     * Converts reservation to completed sale transaction, removing inventory
     * from available stock and recording the agricultural transaction.
     * Enables agricultural fulfillment and delivery scheduling.
     *
     * @throws Exception If reservation is not active
     * @return void
     */
    public function fulfill(): void
    {
        if (!$this->isActive()) {
            throw new Exception('Cannot fulfill inactive reservation');
        }

        // Remove the reserved stock and record the sale
        $this->productInventory->reserved_quantity -= $this->quantity;
        $this->productInventory->quantity -= $this->quantity;
        $this->productInventory->save();

        // Record the sale transaction
        $this->productInventory->recordTransaction(
            type: 'sale',
            quantity: -$this->quantity,
            notes: "Fulfilled reservation for order #{$this->order_id}",
            referenceType: 'order',
            referenceId: $this->order_id
        );

        // Update reservation status
        $fulfilledStatus = InventoryReservationStatus::findByCode('fulfilled');
        $this->update([
            'status_id' => $fulfilledStatus->id,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * Cancel reservation and release agricultural inventory.
     *
     * Cancels reservation and immediately releases held agricultural
     * inventory back to available stock. Used when orders are cancelled
     * or modified during agricultural production processing.
     *
     * @param string|null $reason Optional cancellation reason for tracking
     * @throws Exception If reservation is already fulfilled
     * @return void
     */
    public function cancel(string $reason = null): void
    {
        if ($this->status?->isFulfilled()) {
            throw new Exception('Cannot cancel fulfilled reservation');
        }

        // Release the reserved stock
        $this->release($reason ?? 'Reservation cancelled');

        // Update status
        $cancelledStatus = InventoryReservationStatus::findByCode('cancelled');
        $this->update(['status_id' => $cancelledStatus->id]);
    }

    /**
     * Release the reserved stock.
     */
    protected function release(string $reason = null): void
    {
        if ($this->status?->holdsInventory()) {
            $this->productInventory->releaseReservation($this->quantity, $reason);
        }
    }

    /**
     * Scope for active reservations.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('status', function ($q) {
                $q->where('code', 'pending')->orWhere('code', 'confirmed');
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired reservations.
     */
    public function scopeExpired($query)
    {
        return $query->whereHas('status', function ($q) {
                $q->where('code', 'pending')->orWhere('code', 'confirmed');
            })
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Clean up expired agricultural inventory reservations.
     *
     * Automatically cancels expired reservations and releases held
     * agricultural inventory back to available stock. Essential for
     * maintaining optimal inventory utilization and preventing
     * indefinite inventory locks.
     *
     * @return int Number of expired reservations cleaned up
     */
    public static function cleanupExpired(): int
    {
        $expired = static::expired()->get();
        $count = 0;

        foreach ($expired as $reservation) {
            $reservation->cancel('Reservation expired');
            $count++;
        }

        return $count;
    }
}