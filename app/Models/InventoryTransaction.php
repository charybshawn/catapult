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
        'type',
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
     * Transaction types
     */
    const TYPE_PRODUCTION = 'production';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE = 'sale';
    const TYPE_RETURN = 'return';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_DAMAGE = 'damage';
    const TYPE_EXPIRATION = 'expiration';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_RELEASE = 'release';

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
        return in_array($this->type, [
            self::TYPE_PRODUCTION,
            self::TYPE_PURCHASE,
            self::TYPE_RETURN,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity > 0);
    }

    /**
     * Check if this is an outbound transaction (removes stock).
     */
    public function isOutbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_SALE,
            self::TYPE_DAMAGE,
            self::TYPE_EXPIRATION,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity < 0);
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_PRODUCTION => 'Production',
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_RETURN => 'Customer Return',
            self::TYPE_ADJUSTMENT => 'Manual Adjustment',
            self::TYPE_DAMAGE => 'Damaged',
            self::TYPE_EXPIRATION => 'Expired',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_RESERVATION => 'Reserved',
            self::TYPE_RELEASE => 'Reservation Released',
        ];

        return $labels[$this->type] ?? ucfirst($this->type);
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