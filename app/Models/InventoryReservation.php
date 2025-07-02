<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    use HasFactory;

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
     * Get the inventory batch this reservation is for.
     */
    public function productInventory(): BelongsTo
    {
        return $this->belongsTo(ProductInventory::class);
    }

    /**
     * Get the product this reservation is for.
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
     * Check if the reservation is active.
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
     * Fulfill the reservation (convert to actual sale).
     */
    public function fulfill(): void
    {
        if (!$this->isActive()) {
            throw new \Exception('Cannot fulfill inactive reservation');
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
     * Cancel the reservation.
     */
    public function cancel(string $reason = null): void
    {
        if ($this->status?->isFulfilled()) {
            throw new \Exception('Cannot cancel fulfilled reservation');
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
     * Clean up expired reservations.
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