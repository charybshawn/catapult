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
        'status',
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
            if ($reservation->status === 'pending' || $reservation->status === 'confirmed') {
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
        return in_array($this->status, ['pending', 'confirmed']) && !$this->isExpired();
    }

    /**
     * Confirm the reservation.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
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
        $this->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * Cancel the reservation.
     */
    public function cancel(string $reason = null): void
    {
        if ($this->status === 'fulfilled') {
            throw new \Exception('Cannot cancel fulfilled reservation');
        }

        // Release the reserved stock
        $this->release($reason ?? 'Reservation cancelled');

        // Update status
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Release the reserved stock.
     */
    protected function release(string $reason = null): void
    {
        if ($this->status === 'pending' || $this->status === 'confirmed') {
            $this->productInventory->releaseReservation($this->quantity, $reason);
        }
    }

    /**
     * Scope for active reservations.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
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
        return $query->whereIn('status', ['pending', 'confirmed'])
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