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
        'price',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
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
            ->logOnly(['order_id', 'product_id', 'price_variation_id', 'quantity', 'price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
