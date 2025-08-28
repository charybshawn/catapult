<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory Transaction Management for Agricultural Product Tracking
 *
 * Represents individual inventory movements for agricultural microgreens products,
 * tracking all inbound and outbound transactions with detailed audit trails.
 * Essential for agricultural inventory control, cost accounting, and operational
 * analysis of microgreens production and sales.
 *
 * @property int $id Primary key identifier
 * @property int $product_inventory_id Inventory batch affected by transaction
 * @property int $product_id Agricultural product involved in transaction
 * @property int $inventory_transaction_type_id Type of inventory transaction
 * @property float $quantity Transaction quantity (positive for inbound, negative for outbound)
 * @property float $balance_after Inventory balance after this transaction
 * @property float|null $unit_cost Cost per unit for this transaction
 * @property float|null $total_cost Total cost impact of this transaction
 * @property string|null $reference_type Type of related record (order, crop, etc.)
 * @property int|null $reference_id ID of related record
 * @property int|null $user_id User who performed the transaction
 * @property string|null $notes Additional transaction details
 * @property array|null $metadata Additional structured transaction data
 *
 * @relationship productInventory BelongsTo Inventory batch affected
 * @relationship product BelongsTo Agricultural product involved
 * @relationship user BelongsTo User who performed transaction
 * @relationship inventoryTransactionType BelongsTo Transaction type definition
 * @relationship reference Polymorphic Related record (order, crop, etc.)
 *
 * @business_rule All inventory changes must be recorded as transactions
 * @business_rule Transactions maintain running balance for audit trails
 * @business_rule Cost information enables agricultural profitability analysis
 * @business_rule Reference links connect transactions to business events
 *
 * @agricultural_context Inventory transactions track agricultural operations:
 * - Production transactions record microgreens harvest completions
 * - Sale transactions record customer order fulfillments
 * - Adjustment transactions record agricultural waste or damage
 * - Return transactions record customer return processing
 *
 * Each transaction provides complete audit trail for agricultural inventory
 * movements and enables accurate cost tracking and operational analysis.
 *
 * @usage_example
 * // Record harvest production transaction
 * $transaction = InventoryTransaction::create([
 *     'product_inventory_id' => $inventory->id,
 *     'product_id' => $product->id,
 *     'inventory_transaction_type_id' => InventoryTransactionType::findByCode('production')->id,
 *     'quantity' => 5.75,
 *     'unit_cost' => 8.50,
 *     'reference_type' => 'crop',
 *     'reference_id' => $crop->id
 * ]);
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class InventoryTransaction extends Model
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
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
     * Check if transaction adds agricultural inventory stock.
     *
     * Determines if transaction increases available agricultural product
     * inventory through production, purchases, returns, or positive
     * adjustments. Used for agricultural inventory flow analysis.
     *
     * @return bool True if transaction adds inventory stock
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
     * Check if transaction removes agricultural inventory stock.
     *
     * Determines if transaction decreases available agricultural product
     * inventory through sales, damage, expiration, or negative adjustments.
     * Used for agricultural inventory depletion analysis.
     *
     * @return bool True if transaction removes inventory stock
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
     * Get formatted inventory impact for agricultural reporting.
     *
     * Formats transaction quantity with appropriate positive or negative
     * indicators for agricultural inventory reporting and display.
     * Used in agricultural inventory analysis and audit trails.
     *
     * @return string Formatted impact (+2.50 or -1.75)
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