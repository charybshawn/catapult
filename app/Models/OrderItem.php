<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Order Item Management for Agricultural Product Sales
 *
 * Represents individual line items within orders for the microgreens agricultural
 * business, tracking specific products, quantities, pricing variations, and
 * agricultural production requirements. Each order item drives agricultural
 * resource allocation and production planning calculations.
 *
 * @property int $id Primary key identifier
 * @property int $order_id Parent order identifier
 * @property int $product_id Agricultural product being ordered
 * @property int|null $price_variation_id Specific pricing variation for packaging/quantity
 * @property float $quantity Quantity ordered in specified units
 * @property string|null $quantity_unit Unit of measurement for quantity
 * @property float|null $quantity_in_grams Quantity converted to grams for agricultural calculations
 * @property float $price Unit price at time of order (historical pricing)
 *
 * @property-read float $subtotal Calculated line item total (price × quantity)
 * @property-read string $formatted_quantity Quantity with unit display formatting
 * @property-read string $formatted_price Price with unit display formatting
 *
 * @relationship order BelongsTo Parent order containing this line item
 * @relationship product BelongsTo Agricultural product being ordered
 * @relationship priceVariation BelongsTo Pricing structure with packaging specifications
 *
 * @business_rule Quantity units automatically populated from price variation
 * @business_rule Gram conversion enables agricultural resource calculations
 * @business_rule Historical pricing preserved regardless of current product prices
 * @business_rule Activity logging tracks all line item modifications
 *
 * @agricultural_context Order items drive agricultural production requirements:
 * - Quantity in grams enables seed usage and yield calculations
 * - Price variations define packaging requirements for harvest
 * - Product relationships connect to growing recipes and cultivation methods
 * - Line item changes trigger crop plan updates and resource reallocation
 *
 * Each order item represents specific agricultural production demand that must
 * be scheduled, grown, harvested, and packaged according to customer specifications.
 *
 * @usage_example
 * // Create order item with price variation
 * $orderItem = OrderItem::create([
 *     'order_id' => $order->id,
 *     'product_id' => $product->id,
 *     'price_variation_id' => $priceVariation->id,
 *     'quantity' => 2.5,
 *     'price' => 12.99
 * ]);
 *
 * // Get agricultural resource requirements
 * $gramsNeeded = $orderItem->quantity_in_grams; // Auto-calculated
 * $subtotal = $orderItem->subtotal(); // Calculate line total
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class OrderItem extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_products';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'price_variation_id',
        'quantity',
        'quantity_unit',
        'quantity_in_grams',
        'price',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_in_grams' => 'decimal:3',
        'price' => 'float',
    ];
    
    /**
     * Get the parent order containing this line item.
     *
     * Relationship to the order that contains this agricultural product
     * line item. Essential for accessing customer information, delivery
     * dates, and overall order context for production planning.
     *
     * @return BelongsTo<Order> Parent order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the agricultural product being ordered.
     *
     * Relationship to the specific microgreens product with growing
     * requirements, cultivation methods, and seed specifications.
     * Used for agricultural production planning and resource allocation.
     *
     * @return BelongsTo<Product> Agricultural product being ordered
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * Get the pricing variation with packaging specifications.
     *
     * Relationship to price variation that defines packaging type,
     * quantity units, and pricing structure for this line item.
     * Drives agricultural packaging requirements and cost calculations.
     *
     * @return BelongsTo<PriceVariation> Pricing structure with packaging specs
     */
    public function priceVariation(): BelongsTo
    {
        return $this->belongsTo(PriceVariation::class, 'price_variation_id');
    }
    
    /**
     * Get the item for this order item.
     * @deprecated Use product() instead
     */
    public function item(): BelongsTo
    {
        return $this->product();
    }
    
    /**
     * Calculate line item subtotal for agricultural product sales.
     *
     * Computes total cost for this order item by multiplying quantity
     * by unit price. Used in order totals, agricultural revenue calculations,
     * and financial reporting for microgreens production profitability.
     *
     * @return float Line item subtotal (price × quantity)
     */
    public function subtotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'product_id', 'price_variation_id', 'quantity', 'quantity_unit', 'price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($orderItem) {
            // Auto-populate quantity_unit and quantity_in_grams based on price variation
            if ($orderItem->price_variation_id && $orderItem->isDirty('quantity')) {
                $priceVariation = $orderItem->priceVariation;
                if ($priceVariation) {
                    // Set the quantity unit from the price variation
                    $orderItem->quantity_unit = $priceVariation->getDisplayUnit();
                    
                    // Convert to grams if sold by weight
                    if ($priceVariation->isSoldByWeight() && $orderItem->quantity !== null) {
                        $orderItem->quantity_in_grams = $priceVariation->convertToGrams($orderItem->quantity);
                    } else {
                        $orderItem->quantity_in_grams = null;
                    }
                }
            }
        });
    }
    
    /**
     * Get formatted quantity display with agricultural units.
     *
     * Formats quantity with appropriate units for agricultural product
     * display. Handles various packaging units (grams, ounces, units)
     * with proper singular/plural formatting for customer interfaces.
     *
     * @return string Formatted quantity with units (e.g., "2.5 lbs", "3 units")
     */
    public function getFormattedQuantityAttribute(): string
    {
        $unit = $this->quantity_unit ?: 'units';
        
        if ($unit === 'units') {
            return number_format($this->quantity) . ' ' . ($this->quantity == 1 ? 'unit' : 'units');
        } else {
            return number_format($this->quantity, 2) . ' ' . $unit;
        }
    }
    
    /**
     * Get formatted price display with agricultural pricing units.
     *
     * Formats unit price with appropriate per-unit indicators for
     * agricultural product pricing display. Shows pricing context
     * for different packaging variations and quantity structures.
     *
     * @return string Formatted price with units (e.g., "$12.99/lb", "$8.50")
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->priceVariation && $this->priceVariation->pricing_unit && $this->priceVariation->pricing_unit !== 'each') {
            return '$' . number_format($this->price, 2) . '/' . $this->priceVariation->pricing_unit;
        }
        return '$' . number_format($this->price, 2);
    }
}
