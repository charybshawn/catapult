<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
     * Get the order for this order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the product for this order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * Get the price variation for this order item.
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
     * Calculate the subtotal for this order item.
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
                    if ($priceVariation->isSoldByWeight()) {
                        $orderItem->quantity_in_grams = $priceVariation->convertToGrams($orderItem->quantity);
                    } else {
                        $orderItem->quantity_in_grams = null;
                    }
                }
            }
        });
    }
    
    /**
     * Get formatted quantity with unit
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
     * Get display price with unit if applicable
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->priceVariation && $this->priceVariation->pricing_unit && $this->priceVariation->pricing_unit !== 'each') {
            return '$' . number_format($this->price, 2) . '/' . $this->priceVariation->pricing_unit;
        }
        return '$' . number_format($this->price, 2);
    }
}
