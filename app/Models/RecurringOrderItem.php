<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecurringOrderItem extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recurring_order_id',
        'item_id',
        'quantity',
        'price',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];
    
    /**
     * Get the recurring order that this item belongs to.
     */
    public function recurringOrder(): BelongsTo
    {
        return $this->belongsTo(RecurringOrder::class);
    }
    
    /**
     * Get the product item for this order item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
    
    /**
     * Get the subtotal for this item.
     */
    public function subtotal(): float
    {
        $price = $this->price ?? $this->item->price;
        return $this->quantity * $price;
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['recurring_order_id', 'item_id', 'quantity', 'price', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Recurring order item was {$eventName}");
    }
} 