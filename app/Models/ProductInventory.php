<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ProductInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price_variation_id',
        'lot_number',
        'quantity',
        'reserved_quantity',
        'cost_per_unit',
        'expiration_date',
        'production_date',
        'location',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'expiration_date' => 'date',
        'production_date' => 'date',
    ];

    protected $appends = ['available_quantity'];

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        // Validate that price variation belongs to product
        static::saving(function ($inventory) {
            if ($inventory->price_variation_id && $inventory->product_id) {
                $priceVariation = \App\Models\PriceVariation::find($inventory->price_variation_id);
                if ($priceVariation && $priceVariation->product_id != $inventory->product_id) {
                    throw new \Exception("Price variation does not belong to the selected product.");
                }
            }
        });
        
        // Prevent deletion of inventory with quantities
        static::deleting(function ($inventory) {
            if ($inventory->quantity > 0 || $inventory->reserved_quantity > 0) {
                throw new \Exception("Cannot delete inventory batch '{$inventory->batch_number}' because it has {$inventory->quantity} units ({$inventory->reserved_quantity} reserved). Please reduce quantities to zero first.");
            }
        });

        // Automatically update product totals when inventory changes
        static::saved(function ($inventory) {
            $inventory->updateProductTotals();
        });

        static::deleted(function ($inventory) {
            $inventory->updateProductTotals();
        });
    }

    /**
     * Get the available quantity attribute.
     */
    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Get the product that owns the inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the price variation associated with this inventory.
     */
    public function priceVariation(): BelongsTo
    {
        return $this->belongsTo(PriceVariation::class);
    }

    /**
     * Get the transactions for this inventory.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get the reservations for this inventory.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    /**
     * Scope for active inventory.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for available inventory (has available quantity).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
            ->whereRaw('quantity > reserved_quantity');
    }

    /**
     * Scope for expiring soon inventory.
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->active()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays($days))
            ->where('expiration_date', '>', now());
    }

    /**
     * Scope for expired inventory.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now());
    }

    /**
     * Add stock to this inventory batch.
     */
    public function addStock(float $quantity, array $transactionData = []): InventoryTransaction
    {
        $this->quantity += $quantity;
        $this->save();

        return $this->recordTransaction(
            type: $transactionData['type'] ?? 'adjustment',
            quantity: $quantity,
            notes: $transactionData['notes'] ?? null,
            referenceType: $transactionData['reference_type'] ?? null,
            referenceId: $transactionData['reference_id'] ?? null,
            userId: $transactionData['user_id'] ?? auth()->id()
        );
    }

    /**
     * Remove stock from this inventory batch.
     */
    public function removeStock(float $quantity, array $transactionData = []): InventoryTransaction
    {
        if ($quantity > $this->available_quantity) {
            throw new \Exception("Insufficient stock. Available: {$this->available_quantity}, Requested: {$quantity}");
        }

        $this->quantity -= $quantity;
        $this->save();

        return $this->recordTransaction(
            type: $transactionData['type'] ?? 'adjustment',
            quantity: -$quantity,
            notes: $transactionData['notes'] ?? null,
            referenceType: $transactionData['reference_type'] ?? null,
            referenceId: $transactionData['reference_id'] ?? null,
            userId: $transactionData['user_id'] ?? auth()->id()
        );
    }

    /**
     * Reserve stock for an order.
     */
    public function reserveStock(float $quantity, int $orderId, int $orderItemId, ?\DateTime $expiresAt = null): InventoryReservation
    {
        if ($quantity > $this->available_quantity) {
            throw new \Exception("Insufficient stock for reservation. Available: {$this->available_quantity}, Requested: {$quantity}");
        }

        $this->reserved_quantity += $quantity;
        $this->save();

        $reservation = InventoryReservation::create([
            'product_inventory_id' => $this->id,
            'product_id' => $this->product_id,
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'quantity' => $quantity,
            'status' => 'pending',
            'expires_at' => $expiresAt ?? now()->addHours(24),
        ]);

        $this->recordTransaction(
            type: 'reservation',
            quantity: $quantity,
            notes: "Reserved for order #{$orderId}",
            referenceType: 'order',
            referenceId: $orderId
        );

        return $reservation;
    }

    /**
     * Release reserved stock.
     */
    public function releaseReservation(float $quantity, ?string $reason = null): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->save();

        $this->recordTransaction(
            type: 'release',
            quantity: -$quantity,
            notes: $reason ?? 'Reservation released',
            referenceType: null,
            referenceId: null
        );
    }

    /**
     * Record an inventory transaction.
     */
    protected function recordTransaction(
        string $type,
        float $quantity,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): InventoryTransaction {
        return InventoryTransaction::create([
            'product_inventory_id' => $this->id,
            'product_id' => $this->product_id,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $this->quantity,
            'unit_cost' => $this->cost_per_unit,
            'total_cost' => abs($quantity) * $this->cost_per_unit,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Update product totals based on all inventory batches.
     */
    public function updateProductTotals(): void
    {
        $product = $this->product;
        
        $totals = static::where('product_id', $product->id)
            ->active()
            ->selectRaw('
                SUM(quantity) as total_quantity,
                SUM(reserved_quantity) as total_reserved
            ')
            ->first();

        $product->update([
            'total_stock' => $totals->total_quantity ?? 0,
            'reserved_stock' => $totals->total_reserved ?? 0,
            'stock_status' => $this->calculateStockStatus($totals->total_quantity ?? 0, $totals->total_reserved ?? 0, $product->reorder_threshold),
        ]);
    }

    /**
     * Calculate stock status based on quantities.
     */
    protected function calculateStockStatus(float $totalStock, float $reservedStock, float $reorderThreshold): string
    {
        $available = $totalStock - $reservedStock;

        if ($available <= 0) {
            return 'out_of_stock';
        } elseif ($available <= $reorderThreshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Check if this batch should be used first (FIFO by default).
     */
    public function shouldUseFirst(): bool
    {
        // Prioritize by expiration date if set, otherwise by creation date
        if ($this->expiration_date) {
            return true; // Items with expiration dates should be used first
        }

        return false;
    }

    /**
     * Get the value of this inventory batch.
     */
    public function getValue(): float
    {
        return $this->quantity * ($this->cost_per_unit ?? 0);
    }
}