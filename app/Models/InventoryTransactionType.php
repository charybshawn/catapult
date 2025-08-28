<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inventory Transaction Type Management for Agricultural Operations
 *
 * Represents different types of inventory transactions in the microgreens
 * agricultural business, defining categories for inventory movements and
 * providing classification for agricultural operational analysis.
 *
 * @property int $id Primary key identifier
 * @property string $code Unique system code for transaction type identification
 * @property string $name Human-readable transaction type name
 * @property string|null $description Detailed explanation of transaction type usage
 * @property string|null $color Display color for type visualization
 * @property bool $is_active Whether type is available for use
 * @property int|null $sort_order Display order for type prioritization
 *
 * @relationship inventoryTransactions HasMany Transactions using this type
 *
 * @business_rule Active types control availability in inventory processing
 * @business_rule Sort order determines type priority in UI dropdowns
 * @business_rule Type codes enable programmatic transaction classification
 *
 * @agricultural_context Transaction types support agricultural operations:
 * - production: Microgreens harvest and production completion
 * - sale: Customer order fulfillment and product delivery
 * - purchase: Agricultural supply and consumable acquisitions
 * - adjustment: Inventory corrections for waste, damage, or counting
 * - return: Customer return processing and inventory restoration
 * - expiration: Agricultural product spoilage and disposal
 *
 * Each type enables proper categorization and analysis of agricultural
 * inventory movements for operational efficiency and cost control.
 *
 * @usage_example
 * // Get transaction type for harvest recording
 * $productionType = InventoryTransactionType::findByCode('production');
 *
 * // Create transaction with proper type
 * $transaction = InventoryTransaction::create([
 *     'inventory_transaction_type_id' => $productionType->id,
 *     'quantity' => 5.0
 * ]);
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class InventoryTransactionType extends Model
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
    ];

    /**
     * Get inventory transactions using this type.
     *
     * Relationship to all inventory transactions classified with this
     * type. Essential for agricultural operational analysis and
     * transaction type usage reporting.
     *
     * @return HasMany<InventoryTransaction> Transactions using this type
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Find transaction type by unique code identifier.
     *
     * Retrieves transaction type using system code for programmatic
     * access in agricultural inventory processing workflows. Used for
     * automatic type assignment and transaction classification.
     *
     * @param string $code Unique transaction type code
     * @return self|null Transaction type or null if not found
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope query to only active transaction types.
     *
     * Query scope for filtering to currently available transaction types
     * used in agricultural inventory processing. Excludes disabled types
     * that may be retained for historical transaction tracking.
     *
     * @param Builder $query Query builder instance
     * @return Builder Filtered query for active types only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered types.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}