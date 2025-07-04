<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_inventory_id',
        'product_id',
        'inventory_transaction_type_id',
        'quantity',
        'balance_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'metadata' => 'array',
    ];


    /**
     * Get the inventory batch this transaction belongs to.
     */
    public function productInventory(): BelongsTo
    {
        return $this->belongsTo(ProductInventory::class);
    }

    /**
     * Get the product this transaction belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who performed this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction type.
     */
    public function inventoryTransactionType(): BelongsTo
    {
        return $this->belongsTo(InventoryTransactionType::class);
    }

    /**
     * Get the reference model (polymorphic).
     */
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            $modelClass = $this->getModelClassFromType($this->reference_type);
            if (class_exists($modelClass)) {
                return $this->belongsTo($modelClass, 'reference_id');
            }
        }
        return null;
    }

    /**
     * Convert reference type to model class.
     */
    protected function getModelClassFromType(string $type): string
    {
        $typeMap = [
            'order' => Order::class,
            'invoice' => Invoice::class,
            'production_batch' => Crop::class,
            'crop' => Crop::class,
        ];

        return $typeMap[$type] ?? '';
    }

    /**
     * Check if this is an inbound transaction (adds stock).
     */
    public function isInbound(): bool
    {
        $typeCode = $this->inventoryTransactionType?->code;
        return in_array($typeCode, [
            'production',
            'purchase',
            'return',
        ]) || ($typeCode === 'adjustment' && $this->quantity > 0);
    }

    /**
     * Check if this is an outbound transaction (removes stock).
     */
    public function isOutbound(): bool
    {
        $typeCode = $this->inventoryTransactionType?->code;
        return in_array($typeCode, [
            'sale',
            'damage',
            'expiration',
        ]) || ($typeCode === 'adjustment' && $this->quantity < 0);
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabel(): string
    {
        return $this->inventoryTransactionType?->name ?? 'Unknown';
    }

    /**
     * Get the impact on inventory (positive or negative).
     */
    public function getImpact(): string
    {
        if ($this->quantity > 0) {
            return '+' . number_format(abs($this->quantity), 2);
        } else {
            return '-' . number_format(abs($this->quantity), 2);
        }
    }
}