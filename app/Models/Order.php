<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'harvest_date',
        'delivery_date',
        'status',
        'customer_type',
        'order_type',
        'billing_frequency',
        'requires_invoice',
        'billing_period_start',
        'billing_period_end',
        'consolidated_invoice_id',
        'billing_preferences',
        'billing_period',
        'is_recurring',
        'parent_recurring_order_id',
        'recurring_frequency',
        'recurring_start_date',
        'recurring_end_date',
        'is_recurring_active',
        'recurring_days_of_week',
        'recurring_interval',
        'last_generated_at',
        'next_generation_date',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'harvest_date' => 'date',
        'delivery_date' => 'date',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'requires_invoice' => 'boolean',
        'billing_preferences' => 'array',
        'recurring_start_date' => 'date',
        'recurring_end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_recurring_active' => 'boolean',
        'recurring_days_of_week' => 'array',
        'last_generated_at' => 'datetime',
        'next_generation_date' => 'datetime',
    ];
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            // Set default status for new orders
            if (!$order->status) {
                $order->status = $order->is_recurring ? 'template' : 'pending';
            }
        });
        
        static::saving(function ($order) {
            // Automatically set recurring_start_date based on harvest_date when marking as recurring
            if ($order->is_recurring && !$order->recurring_start_date && $order->harvest_date) {
                $order->recurring_start_date = $order->harvest_date;
            }
            
            // Automatically set status to template when marked as recurring (but not for B2B orders)
            if ($order->is_recurring && !in_array($order->order_type, ['b2b', 'b2b_recurring']) && $order->status !== 'template') {
                $order->status = 'template';
            } elseif (!$order->is_recurring && $order->status === 'template') {
                // If no longer recurring, change status from template to pending
                $order->status = 'pending';
            }
            
            // Automatically set customer_type from user if not set
            if (!$order->customer_type && $order->user_id) {
                $user = $order->user_id ? User::find($order->user_id) : null;
                if ($user) {
                    $order->customer_type = $user->customer_type ?? 'retail';
                }
            }
            
            // Set billing periods for B2B orders
            if (in_array($order->order_type, ['b2b', 'b2b_recurring']) && 
                $order->billing_frequency !== 'immediate' && 
                $order->delivery_date &&
                (!$order->billing_period_start || !$order->billing_period_end)) {
                
                $deliveryDate = $order->delivery_date instanceof \Carbon\Carbon 
                    ? $order->delivery_date 
                    : \Carbon\Carbon::parse($order->delivery_date);
                
                switch ($order->billing_frequency) {
                    case 'weekly':
                        $order->billing_period_start = $deliveryDate->copy()->startOfWeek()->toDateString();
                        $order->billing_period_end = $deliveryDate->copy()->endOfWeek()->toDateString();
                        break;
                        
                    case 'monthly':
                        $order->billing_period_start = $deliveryDate->copy()->startOfMonth()->toDateString();
                        $order->billing_period_end = $deliveryDate->copy()->endOfMonth()->toDateString();
                        break;
                        
                    case 'quarterly':
                        $order->billing_period_start = $deliveryDate->copy()->startOfQuarter()->toDateString();
                        $order->billing_period_end = $deliveryDate->copy()->endOfQuarter()->toDateString();
                        break;
                }
            }
        });
    }
    
    /**
     * Get the user (customer) for this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the order items for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Get the crops for this order.
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class);
    }
    
    /**
     * Get the crop plans for this order.
     */
    public function cropPlans(): HasMany
    {
        return $this->hasMany(CropPlan::class);
    }
    
    /**
     * Get the payments for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    /**
     * Get the invoice for this order.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
    
    /**
     * Get the consolidated invoice for this order.
     */
    public function consolidatedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'consolidated_invoice_id');
    }
    
    /**
     * Get the packaging for this order.
     */
    public function orderPackagings(): HasMany
    {
        return $this->hasMany(OrderPackaging::class);
    }
    
    /**
     * Get the packaging types for this order.
     */
    public function packagingTypes()
    {
        return $this->belongsToMany(PackagingType::class, 'order_packagings')
            ->withPivot('quantity', 'notes')
            ->withTimestamps();
    }
    
    /**
     * Calculate the total amount for this order.
     */
    public function totalAmount(): float
    {
        return $this->orderItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }
    
    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payments()->where('status', 'completed')->sum('amount') >= $this->totalAmount();
    }

    public function remainingBalance(): float
    {
        $total = $this->totalAmount();
        $paid = $this->payments()->where('status', 'completed')->sum('amount');
        return max(0, $total - $paid);
    }

    public function consumables()
    {
        return $this->belongsToMany(Consumable::class, 'order_consumables')
            ->withPivot('quantity', 'notes')
            ->withTimestamps();
    }
    
    public function packagingCost(): float
    {
        return $this->packagingTypes()->sum(function ($packagingType) {
            return $packagingType->pivot->quantity * $packagingType->cost_per_unit;
        });
    }

    public function autoAssignPackaging()
    {
        // Get all active packaging types
        $packagingTypes = PackagingType::where('is_active', true)
            ->get();

        // Clear existing packaging assignments
        $this->packagingTypes()->detach();
        
        // Get total number of items in order
        $totalItems = $this->orderItems->sum('quantity');
        
        // Assign appropriate packaging
        if ($totalItems > 0 && $packagingTypes->count() > 0) {
            // Look for a medium-sized packaging type first (assuming medium is better default)
            $mediumPackaging = $packagingTypes->first(function ($packagingType) {
                return stripos($packagingType->name, 'medium') !== false;
            });
            
            // If no medium packaging, try to find a default size that makes sense
            $defaultPackaging = $mediumPackaging ?? $packagingTypes->sortBy('capacity_volume')->first(function ($packagingType) {
                return $packagingType->capacity_volume >= 16; // Prefer at least 16oz containers
            });
            
            // If still no match, just use the first available packaging
            $defaultPackaging = $defaultPackaging ?? $packagingTypes->first();
            
            // Attach packaging with quantity = number of items
            $this->packagingTypes()->attach($defaultPackaging->id, [
                'quantity' => $totalItems,
                'notes' => 'Auto-assigned packaging: ' . $defaultPackaging->display_name,
            ]);
        }
    }

    /**
     * Get the parent recurring order template.
     */
    public function parentRecurringOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_recurring_order_id');
    }
    
    /**
     * Get the child orders generated from this recurring order template.
     */
    public function generatedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_recurring_order_id');
    }
    
    /**
     * Check if this is a recurring order template.
     */
    public function isRecurringTemplate(): bool
    {
        return $this->is_recurring && $this->parent_recurring_order_id === null;
    }
    
    /**
     * Check if this is a B2B recurring order that can generate new orders.
     */
    public function isB2BRecurringTemplate(): bool
    {
        return in_array($this->order_type, ['b2b', 'b2b_recurring']) && 
               $this->is_recurring && 
               $this->parent_recurring_order_id === null;
    }
    
    /**
     * Check if this order was generated from a recurring template.
     */
    public function isGeneratedFromRecurring(): bool
    {
        return $this->parent_recurring_order_id !== null;
    }
    
    /**
     * Calculate the next generation date based on frequency and settings.
     */
    public function calculateNextGenerationDate(): ?\Carbon\Carbon
    {
        if (!$this->isRecurringTemplate() || !$this->is_recurring_active) {
            return null;
        }
        
        $lastGenerated = $this->last_generated_at ?? $this->recurring_start_date;
        if (!$lastGenerated) {
            return null;
        }
        
        $lastDate = $lastGenerated instanceof \Carbon\Carbon ? $lastGenerated : \Carbon\Carbon::parse($lastGenerated);
        
        return match($this->recurring_frequency) {
            'weekly' => $lastDate->addWeek(),
            'biweekly' => $lastDate->addWeeks($this->recurring_interval ?? 2),
            'monthly' => $lastDate->addMonth(),
            default => null
        };
    }
    
    /**
     * Generate the next order in the recurring series.
     */
    public function generateNextRecurringOrder(): ?Order
    {
        if (!$this->isRecurringTemplate() || !$this->is_recurring_active) {
            return null;
        }
        
        // Check if we're past the end date
        if ($this->recurring_end_date && now()->gt($this->recurring_end_date)) {
            $this->update(['is_recurring_active' => false]);
            return null;
        }
        
        $nextDate = $this->calculateNextGenerationDate();
        if (!$nextDate) {
            return null;
        }
        
        // Check if an order already exists for this delivery date to prevent duplicates
        $deliveryDate = $nextDate->copy()->addDay();
        $existingOrder = $this->generatedOrders()
            ->where('delivery_date', $deliveryDate->format('Y-m-d'))
            ->first();
            
        if ($existingOrder) {
            // Order already exists for this date, update next generation date and skip
            $this->update([
                'next_generation_date' => $this->calculateNextGenerationDate()
            ]);
            return null;
        }
        
        // Create new order based on template
        $newOrder = $this->replicate([
            'is_recurring',
            'recurring_frequency',
            'recurring_start_date', 
            'recurring_end_date',
            'recurring_days_of_week',
            'recurring_interval',
            'last_generated_at',
            'next_generation_date'
        ]);
        
        $newOrder->parent_recurring_order_id = $this->id;
        $newOrder->harvest_date = $nextDate->copy();
        $newOrder->delivery_date = $nextDate->copy()->addDay(); // Delivery next day
        
        // For B2B orders, keep the same order_type and billing_frequency
        // but don't make the generated order recurring itself
        if ($this->isB2BRecurringTemplate()) {
            $newOrder->is_recurring = false;
            $newOrder->status = 'pending';
        } else {
            $newOrder->status = 'pending';
        }
        
        $newOrder->save();
        
        // Copy order items
        foreach ($this->orderItems as $item) {
            $newOrder->orderItems()->create([
                'product_id' => $item->product_id,
                'price_variation_id' => $item->price_variation_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]);
        }
        
        // Copy packaging
        foreach ($this->packagingTypes as $packaging) {
            $newOrder->packagingTypes()->attach($packaging->id, [
                'quantity' => $packaging->pivot->quantity,
                'notes' => $packaging->pivot->notes,
            ]);
        }
        
        // Update template's last generated date
        $this->update([
            'last_generated_at' => now(),
            'next_generation_date' => $this->calculateNextGenerationDate()
        ]);
        
        return $newOrder;
    }
    
    /**
     * Get formatted recurring frequency display.
     */
    public function getRecurringFrequencyDisplayAttribute(): string
    {
        if (!$this->is_recurring) {
            return 'Not recurring';
        }
        
        return match($this->recurring_frequency) {
            'weekly' => 'Weekly',
            'biweekly' => 'Every ' . ($this->recurring_interval ?? 2) . ' weeks',
            'monthly' => 'Monthly',
            default => 'Unknown frequency'
        };
    }
    
    /**
     * Get the total number of generated orders.
     */
    public function getGeneratedOrdersCountAttribute(): int
    {
        return $this->generatedOrders()->count();
    }

    /**
     * Get the customer type (from order or user).
     */
    public function getCustomerTypeAttribute($value)
    {
        // If order has customer_type set, use it
        if ($value) {
            return $value;
        }
        
        // Otherwise get from user
        return $this->user?->customer_type ?? 'retail';
    }
    
    /**
     * Get the customer type display name.
     */
    public function getCustomerTypeDisplayAttribute(): string
    {
        return match($this->customer_type) {
            'wholesale' => 'Wholesale',
            'retail' => 'Retail',
            default => 'Retail',
        };
    }
    
    /**
     * Get the order type display name.
     */
    public function getOrderTypeDisplayAttribute(): string
    {
        return match($this->order_type) {
            'farmers_market' => 'Farmer\'s Market',
            'b2b' => 'B2B',
            'b2b_recurring' => 'B2B', // Legacy support
            'website_immediate' => 'Website Order',
            default => 'Website Order',
        };
    }
    
    /**
     * Get the billing frequency display name.
     */
    public function getBillingFrequencyDisplayAttribute(): string
    {
        return match($this->billing_frequency) {
            'immediate' => 'Immediate',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            default => 'Immediate',
        };
    }
    
    /**
     * Check if this order requires immediate invoicing.
     */
    public function requiresImmediateInvoicing(): bool
    {
        return $this->order_type === 'website_immediate' || 
               $this->billing_frequency === 'immediate';
    }
    
    /**
     * Check if this order is part of consolidated billing.
     */
    public function isConsolidatedBilling(): bool
    {
        return in_array($this->order_type, ['b2b', 'b2b_recurring']) && 
               in_array($this->billing_frequency, ['weekly', 'monthly', 'quarterly']);
    }
    
    /**
     * Check if this order should bypass invoicing completely.
     */
    public function shouldBypassInvoicing(): bool
    {
        return $this->order_type === 'farmers_market' && !$this->requires_invoice;
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id', 'harvest_date', 'delivery_date', 'status', 'customer_type', 
                'is_recurring', 'recurring_frequency', 'recurring_start_date', 'recurring_end_date',
                'is_recurring_active', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Order was {$eventName}");
    }
}
