<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use App\Traits\Logging\ExtendedLogsActivity;

class Order extends Model
{
    use HasFactory, ExtendedLogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'harvest_date',
        'delivery_date',
        'order_status_id',
        'unified_status_id',
        'crop_status_id',
        'fulfillment_status_id',
        'customer_type',
        'order_type_id',
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
        'harvest_date' => 'datetime',
        'delivery_date' => 'datetime',
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
            // Set user_id to current authenticated user if not set
            if (!$order->user_id && auth()->check()) {
                $order->user_id = auth()->id();
            }
            
            // Set default status for new orders
            if (!$order->order_status_id) {
                $defaultStatusCode = $order->is_recurring ? 'template' : 'pending';
                $defaultStatus = \App\Models\OrderStatus::where('code', $defaultStatusCode)->first();
                if ($defaultStatus) {
                    $order->order_status_id = $defaultStatus->id;
                }
            }
            
            // Set default unified status for new orders
            if (!$order->unified_status_id) {
                $defaultStatusCode = $order->is_recurring ? 'template' : 'pending';
                $defaultUnifiedStatus = \App\Models\UnifiedOrderStatus::where('code', $defaultStatusCode)->first();
                if ($defaultUnifiedStatus) {
                    $order->unified_status_id = $defaultUnifiedStatus->id;
                }
            }
        });
        
        static::saving(function ($order) {
            // Automatically set recurring_start_date based on harvest_date when marking as recurring
            if ($order->is_recurring && !$order->recurring_start_date && $order->harvest_date) {
                $order->recurring_start_date = $order->harvest_date;
            }
            
            // Automatically set status to template when marked as recurring (but not for B2B orders)
            $orderTypeCode = $order->orderType?->code ?? null;
            $currentStatus = $order->orderStatus?->code ?? null;
            if ($order->is_recurring && $orderTypeCode !== 'b2b' && $currentStatus !== 'template') {
                $templateStatus = \App\Models\OrderStatus::where('code', 'template')->first();
                if ($templateStatus) {
                    $order->order_status_id = $templateStatus->id;
                }
                // Also update unified status
                $templateUnifiedStatus = \App\Models\UnifiedOrderStatus::where('code', 'template')->first();
                if ($templateUnifiedStatus) {
                    $order->unified_status_id = $templateUnifiedStatus->id;
                }
            } elseif (!$order->is_recurring && $currentStatus === 'template') {
                // If no longer recurring, change status from template to pending
                $pendingStatus = \App\Models\OrderStatus::where('code', 'pending')->first();
                if ($pendingStatus) {
                    $order->order_status_id = $pendingStatus->id;
                }
                // Also update unified status
                $pendingUnifiedStatus = \App\Models\UnifiedOrderStatus::where('code', 'pending')->first();
                if ($pendingUnifiedStatus) {
                    $order->unified_status_id = $pendingUnifiedStatus->id;
                }
            }
            
            // Automatically set customer_type from customer if not set
            if (!$order->customer_type && $order->customer_id) {
                $customer = $order->customer_id ? \App\Models\Customer::find($order->customer_id) : null;
                if ($customer) {
                    $order->customer_type = $customer->customer_type ?? 'retail';
                }
            }
            
            // Set billing periods for B2B orders
            $orderTypeCode = $order->orderType?->code ?? null;
            if ($orderTypeCode === 'b2b' && 
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
     * Get the user who created this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the customer for this order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    /**
     * Get the order status for this order.
     */
    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }
    
    /**
     * Get the unified order status for this order.
     */
    public function unifiedStatus(): BelongsTo
    {
        return $this->belongsTo(UnifiedOrderStatus::class, 'unified_status_id');
    }
    
    /**
     * Get the order type for this order.
     */
    public function orderType(): BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }
    
    /**
     * Get the crop status for this order.
     */
    public function cropStatus(): BelongsTo
    {
        return $this->belongsTo(CropStatus::class);
    }
    
    /**
     * Get the fulfillment status for this order.
     */
    public function fulfillmentStatus(): BelongsTo
    {
        return $this->belongsTo(FulfillmentStatus::class);
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
        // Ensure orderItems are loaded to avoid lazy loading
        if (!$this->relationLoaded('orderItems')) {
            $this->load('orderItems');
        }
        
        return $this->orderItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }
    
    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        $completedStatusId = PaymentStatus::where('code', 'completed')->first()?->id;
        if (!$completedStatusId) {
            return false;
        }
        return $this->payments()->where('status_id', $completedStatusId)->sum('amount') >= $this->totalAmount();
    }

    public function remainingBalance(): float
    {
        $total = $this->totalAmount();
        $completedStatusId = PaymentStatus::where('code', 'completed')->first()?->id;
        if (!$completedStatusId) {
            return $total;
        }
        $paid = $this->payments()->where('status_id', $completedStatusId)->sum('amount');
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
        return $this->orderType?->code === 'b2b' && 
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
        
        // For B2B orders, keep the same order_type_id and billing_frequency
        // but don't make the generated order recurring itself
        if ($this->isB2BRecurringTemplate()) {
            $newOrder->is_recurring = false;
            $pendingStatus = \App\Models\OrderStatus::where('code', 'pending')->first();
            if ($pendingStatus) {
                $newOrder->order_status_id = $pendingStatus->id;
            }
            $pendingUnifiedStatus = \App\Models\UnifiedOrderStatus::where('code', 'pending')->first();
            if ($pendingUnifiedStatus) {
                $newOrder->unified_status_id = $pendingUnifiedStatus->id;
            }
        } else {
            $pendingStatus = \App\Models\OrderStatus::where('code', 'pending')->first();
            if ($pendingStatus) {
                $newOrder->order_status_id = $pendingStatus->id;
            }
            $pendingUnifiedStatus = \App\Models\UnifiedOrderStatus::where('code', 'pending')->first();
            if ($pendingUnifiedStatus) {
                $newOrder->unified_status_id = $pendingUnifiedStatus->id;
            }
        }
        
        $newOrder->save();
        
        // Ensure relationships are loaded to avoid lazy loading in the loop
        if (!$this->relationLoaded('orderItems')) {
            $this->load(['orderItems.product', 'orderItems.priceVariation']);
        }
        if (!$newOrder->relationLoaded('customer')) {
            $newOrder->load('customer');
        }
        
        // Copy order items with recalculated prices
        foreach ($this->orderItems as $item) {
            $currentPrice = $item->price; // Default to original price
            
            // Recalculate price based on current customer and product pricing
            if ($item->product && $newOrder->customer) {
                $currentPrice = $item->product->getPriceForSpecificCustomer(
                    $newOrder->customer, 
                    $item->price_variation_id
                );
            }
            
            $newOrder->orderItems()->create([
                'product_id' => $item->product_id,
                'price_variation_id' => $item->price_variation_id,
                'quantity' => $item->quantity,
                'price' => $currentPrice,
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
        
        // Otherwise get from customer
        return $this->customer?->customer_type ?? 'retail';
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
        return $this->orderType?->name ?? 'Unknown';
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
        return $this->orderType?->code === 'website' || 
               $this->billing_frequency === 'immediate';
    }
    
    /**
     * Check if this order is part of consolidated billing.
     */
    public function isConsolidatedBilling(): bool
    {
        return $this->orderType?->code === 'b2b' && 
               in_array($this->billing_frequency, ['weekly', 'monthly', 'quarterly']);
    }
    
    /**
     * Check if this order should bypass invoicing completely.
     */
    public function shouldBypassInvoicing(): bool
    {
        return $this->orderType?->code === 'farmers_market' && !$this->requires_invoice;
    }
    
    /**
     * Check if the order requires crop production.
     */
    public function requiresCropProduction(): bool
    {
        // Check if any order items are products with varieties that need growing
        return $this->orderItems()->whereHas('product', function ($query) {
            $query->where(function ($q) {
                $q->whereNotNull('master_seed_catalog_id')
                  ->orWhereNotNull('product_mix_id');
            });
        })->exists();
    }
    
    /**
     * Check if order should have crop plans generated
     */
    public function shouldHaveCropPlans(): bool
    {
        // Order should have plans if it requires crops and is not in a final or template state
        return $this->requiresCropProduction() 
            && !$this->isInFinalState() 
            && $this->unifiedStatus?->code !== 'template'
            && !$this->is_recurring; // Don't generate for recurring templates
    }
    
    /**
     * Update crop status based on related crops.
     */
    public function updateCropStatus(): void
    {
        if (!$this->requiresCropProduction()) {
            $this->update(['crop_status' => 'na']);
            return;
        }
        
        $crops = $this->crops;
        
        if ($crops->isEmpty()) {
            $this->update(['crop_status' => 'not_started']);
            return;
        }
        
        // Check crop stages
        $allHarvested = $crops->every(fn($crop) => $crop->current_stage === 'harvested');
        $anyPlanted = $crops->contains(fn($crop) => $crop->planting_at !== null);
        $allReady = $crops->every(fn($crop) => $crop->isReadyToHarvest());
        
        if ($allHarvested) {
            $this->update(['crop_status' => 'harvested']);
        } elseif ($allReady) {
            $this->update(['crop_status' => 'ready_to_harvest']);
        } elseif ($anyPlanted) {
            $this->update(['crop_status' => 'growing']);
        } else {
            $this->update(['crop_status' => 'planted']);
        }
    }
    
    /**
     * Synchronize the unified status based on current order, crop, and fulfillment statuses.
     * This method determines the most appropriate unified status based on the order's current state.
     */
    public function syncUnifiedStatus(): void
    {
        // Get current status codes
        $orderStatusCode = $this->orderStatus?->code;
        $cropStatusCode = $this->cropStatus?->code;
        $fulfillmentStatusCode = $this->fulfillmentStatus?->code;
        
        // Handle special cases first
        if ($orderStatusCode === 'cancelled') {
            $this->updateUnifiedStatus('cancelled');
            return;
        }
        
        if ($orderStatusCode === 'template') {
            $this->updateUnifiedStatus('template');
            return;
        }
        
        if ($orderStatusCode === 'completed' || $fulfillmentStatusCode === 'delivered') {
            $this->updateUnifiedStatus('delivered');
            return;
        }
        
        // Handle fulfillment stages
        if ($fulfillmentStatusCode === 'out_for_delivery') {
            $this->updateUnifiedStatus('out_for_delivery');
            return;
        }
        
        if ($fulfillmentStatusCode === 'ready_for_delivery') {
            $this->updateUnifiedStatus('ready_for_delivery');
            return;
        }
        
        if ($fulfillmentStatusCode === 'packing') {
            $this->updateUnifiedStatus('packing');
            return;
        }
        
        // Handle production stages (crop-related)
        if ($this->requiresCropProduction() && $cropStatusCode && $cropStatusCode !== 'na') {
            if ($cropStatusCode === 'harvested' || $cropStatusCode === 'harvesting') {
                $this->updateUnifiedStatus('harvesting');
                return;
            }
            
            if ($cropStatusCode === 'ready_to_harvest') {
                $this->updateUnifiedStatus('ready_to_harvest');
                return;
            }
            
            if ($cropStatusCode === 'growing' || $cropStatusCode === 'planted') {
                $this->updateUnifiedStatus('growing');
                return;
            }
        }
        
        // Handle pre-production stages
        if ($orderStatusCode === 'confirmed' || $orderStatusCode === 'processing') {
            $this->updateUnifiedStatus('confirmed');
            return;
        }
        
        if ($orderStatusCode === 'pending') {
            $this->updateUnifiedStatus('pending');
            return;
        }
        
        if ($orderStatusCode === 'draft') {
            $this->updateUnifiedStatus('draft');
            return;
        }
        
        // Default to pending if no specific status can be determined
        $this->updateUnifiedStatus('pending');
    }
    
    /**
     * Update the unified status by code.
     */
    private function updateUnifiedStatus(string $statusCode): void
    {
        $unifiedStatus = UnifiedOrderStatus::findByCode($statusCode);
        if ($unifiedStatus && $unifiedStatus->id !== $this->unified_status_id) {
            $this->update(['unified_status_id' => $unifiedStatus->id]);
        }
    }
    
    /**
     * Get a combined status display.
     */
    public function getCombinedStatusAttribute(): string
    {
        // If unified status is available, use it as primary display
        if ($this->unifiedStatus) {
            return $this->unifiedStatus->name;
        }
        
        // Fallback to old combined display
        $statuses = [];
        
        // Add order status
        $statusCode = $this->orderStatus?->code;
        $statuses[] = match($statusCode) {
            'draft' => 'Draft',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'template' => 'Template',
            default => $this->orderStatus?->name ?? 'Unknown'
        };
        
        // Add crop status if applicable
        $cropStatusCode = $this->cropStatus?->code;
        if ($cropStatusCode && $cropStatusCode !== 'na' && $cropStatusCode !== 'not_started') {
            $statuses[] = $this->cropStatus->name;
        }
        
        // Add fulfillment status if not pending
        $fulfillmentStatusCode = $this->fulfillmentStatus?->code;
        if ($fulfillmentStatusCode && $fulfillmentStatusCode !== 'pending') {
            $statuses[] = $this->fulfillmentStatus->name;
        }
        
        return implode(' - ', array_unique($statuses));
    }
    
    /**
     * Get the unified status display with stage information.
     */
    public function getUnifiedStatusDisplayAttribute(): string
    {
        if (!$this->unifiedStatus) {
            return 'Unknown';
        }
        
        return sprintf(
            '%s (%s)',
            $this->unifiedStatus->name,
            $this->unifiedStatus->stage_display
        );
    }
    
    /**
     * Get the unified status color for UI display.
     */
    public function getUnifiedStatusColorAttribute(): string
    {
        return $this->unifiedStatus?->getDisplayColor() ?? 'gray';
    }
    
    /**
     * Check if the order can be modified based on unified status.
     */
    public function canBeModified(): bool
    {
        return $this->unifiedStatus?->canBeModified() ?? true;
    }
    
    /**
     * Check if the order is in a final state.
     */
    public function isInFinalState(): bool
    {
        return $this->unifiedStatus?->is_final ?? false;
    }
    
    /**
     * Get valid next unified statuses for this order.
     */
    public function getValidNextStatuses(): Collection
    {
        if (!$this->unifiedStatus) {
            return collect();
        }
        
        return UnifiedOrderStatus::getValidNextStatuses($this->unifiedStatus->code);
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'customer_id', 'harvest_date', 'delivery_date', 'status', 'crop_status', 
                'fulfillment_status', 'unified_status_id', 'customer_type', 'is_recurring', 
                'recurring_frequency', 'recurring_start_date', 'recurring_end_date', 
                'is_recurring_active', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Order was {$eventName}");
    }

    /**
     * Get the relationships that should be logged with this model.
     */
    public function getLoggedRelationships(): array
    {
        return ['customer', 'orderStatus', 'unifiedStatus', 'orderType', 'orderItems', 'crops'];
    }

    /**
     * Get specific attributes to include from related models.
     */
    public function getRelationshipAttributesToLog(): array
    {
        return [
            'customer' => ['id', 'name', 'email', 'phone'],
            'orderStatus' => ['id', 'name', 'code'],
            'unifiedStatus' => ['id', 'name', 'code', 'stage'],
            'orderType' => ['id', 'name', 'code'],
            'orderItems' => ['id', 'product_id', 'quantity', 'price'],
            'crops' => ['id', 'recipe_id', 'tray_number', 'current_stage_id', 'planting_at'],
        ];
    }
    
    /**
     * Transition the order to a new unified status with validation.
     *
     * @param string $statusCode
     * @param array $context Additional context for the transition
     * @return array ['success' => bool, 'message' => string]
     */
    public function transitionTo(string $statusCode, array $context = []): array
    {
        $statusService = app(\App\Services\StatusTransitionService::class);
        $result = $statusService->transitionTo($this, $statusCode, $context);
        
        if ($result['success']) {
            // Reload the model to get updated values
            $this->refresh();
        }
        
        return $result;
    }
    
    /**
     * Check if the order can transition to a specific status.
     *
     * @param string $statusCode
     * @return bool
     */
    public function canTransitionTo(string $statusCode): bool
    {
        $statusService = app(\App\Services\StatusTransitionService::class);
        $validation = $statusService->validateTransition($this, $statusCode);
        return $validation['valid'];
    }
    
    /**
     * Get the status transition history from the activity log.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStatusHistory(): Collection
    {
        $statusService = app(\App\Services\StatusTransitionService::class);
        return $statusService->getStatusHistory($this);
    }
}
