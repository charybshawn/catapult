<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use App\Services\StatusTransitionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\LogOptions;
use App\Traits\Logging\ExtendedLogsActivity;

/**
 * Agricultural Order Management Model for Catapult Microgreens System
 *
 * Represents customer orders in the microgreens agricultural workflow, managing the complete
 * lifecycle from initial order placement through crop production to final delivery.
 * Supports both individual orders and complex recurring subscription models essential
 * for restaurant and wholesale customer relationships.
 *
 * @property int $id Primary key identifier
 * @property int|null $user_id User who created the order
 * @property int|null $customer_id Customer placing the order
 * @property Carbon|null $harvest_date Planned agricultural harvest date
 * @property Carbon|null $delivery_date Customer delivery date
 * @property int|null $status_id Unified order status (pending, confirmed, growing, etc.)
 * @property int|null $crop_status_id Agricultural production status
 * @property int|null $fulfillment_status_id Order fulfillment and shipping status
 * @property int|null $payment_status_id Payment processing status
 * @property int|null $delivery_status_id Delivery tracking status
 * @property string|null $customer_type Customer classification (retail, wholesale, bulk)
 * @property int|null $order_type_id Order type classification (website, b2b, farmers_market)
 * @property string|null $billing_frequency B2B billing frequency (immediate, weekly, monthly)
 * @property bool $requires_invoice Whether order needs formal invoice generation
 * @property Carbon|null $billing_period_start Start date for consolidated billing periods
 * @property Carbon|null $billing_period_end End date for consolidated billing periods
 * @property int|null $consolidated_invoice_id Reference to consolidated invoice
 * @property array|null $billing_preferences JSON billing configuration
 * @property string|null $billing_period Billing period classification
 * @property bool $is_recurring Whether this is a recurring order template
 * @property int|null $parent_recurring_order_id Parent template for generated orders
 * @property string|null $recurring_frequency Recurrence pattern (weekly, biweekly, monthly)
 * @property Carbon|null $recurring_start_date When recurring orders begin
 * @property Carbon|null $recurring_end_date When recurring orders stop
 * @property bool $is_recurring_active Whether recurring generation is active
 * @property array|null $recurring_days_of_week Days for recurring order generation
 * @property int|null $recurring_interval Interval for recurring patterns (e.g., every 2 weeks)
 * @property Carbon|null $last_generated_at When last recurring order was generated
 * @property Carbon|null $next_generation_date When next recurring order should be generated
 * @property string|null $harvest_day Preferred harvest day of week
 * @property string|null $delivery_day Preferred delivery day of week
 * @property int $start_delay_weeks Delay before first recurring order
 * @property string|null $notes Order-specific notes and instructions
 *
 * @property-read float $total_amount Computed order total from line items
 * @property-read float $remaining_balance Unpaid balance after payments
 * @property-read string $customer_type_display Formatted customer type name
 * @property-read string $recurring_frequency_display Formatted recurrence description
 * @property-read string $combined_status Legacy combined status display
 * @property-read string $unified_status_display Current unified status with stage
 * @property-read string $unified_status_color UI color for status display
 * @property-read int $generated_orders_count Number of orders generated from template
 *
 * @relationship user BelongsTo User who created the order
 * @relationship customer BelongsTo Customer placing the order
 * @relationship status BelongsTo Unified order status
 * @relationship orderType BelongsTo Order type classification
 * @relationship cropStatus BelongsTo Agricultural production status
 * @relationship fulfillmentStatus BelongsTo Order fulfillment status
 * @relationship orderItems HasMany Individual product line items
 * @relationship crops HasMany Agricultural crops for production
 * @relationship cropPlans HasMany Production planning schedules
 * @relationship payments HasMany Payment transactions
 * @relationship invoice HasOne Individual order invoice
 * @relationship consolidatedInvoice BelongsTo Consolidated billing invoice
 * @relationship packagingTypes BelongsToMany Packaging requirements with quantities
 * @relationship parentRecurringOrder BelongsTo Template for recurring orders
 * @relationship generatedOrders HasMany Orders generated from recurring template
 *
 * @business_rule Orders progress through unified status workflow: draft → pending → confirmed → growing → harvesting → packing → ready_for_delivery → out_for_delivery → delivered
 * @business_rule Recurring templates generate child orders automatically based on schedule
 * @business_rule B2B orders support consolidated billing with various frequencies
 * @business_rule Agricultural timing coordinates harvest dates with delivery schedules
 * @business_rule Order modifications restricted based on production and fulfillment status
 *
 * @agricultural_context Orders drive the entire microgreens production workflow.
 * Harvest dates determine when crops must be planted (accounting for growing time),
 * delivery dates ensure fresh product reaches customers, and recurring orders
 * provide predictable production schedules for agricultural planning.
 *
 * @usage_example
 * // Create standard order
 * $order = Order::create([
 *     'customer_id' => $customer->id,
 *     'delivery_date' => Carbon::parse('2024-03-15'),
 *     'harvest_date' => Carbon::parse('2024-03-13')
 * ]);
 *
 * // Create recurring order template
 * $recurringOrder = Order::create([
 *     'customer_id' => $customer->id,
 *     'is_recurring' => true,
 *     'recurring_frequency' => 'weekly',
 *     'recurring_start_date' => Carbon::now(),
 *     'harvest_day' => 'wednesday',
 *     'delivery_day' => 'friday'
 * ]);
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 2.0.0
 */
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
        'status_id',
        'crop_status_id',
        'fulfillment_status_id',
        'payment_status_id',
        'delivery_status_id',
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
        'harvest_day',
        'delivery_day',
        'start_delay_weeks',
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
     * Configure model event listeners for agricultural order workflow.
     *
     * Implements critical business logic and automated workflows:
     * - Sets authenticated user as order creator
     * - Assigns appropriate default status based on order type
     * - Manages recurring order template status transitions
     * - Automatically calculates billing periods for B2B orders
     * - Sets customer type from customer relationship
     * - Calculates next generation dates for recurring orders
     *
     * @return void
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
            if (!$order->status_id) {
                $defaultStatusCode = $order->is_recurring ? 'template' : 'pending';
                $defaultStatus = OrderStatus::where('code', $defaultStatusCode)->first();
                if ($defaultStatus) {
                    $order->status_id = $defaultStatus->id;
                }
            }
            
            // Set default unified status for new orders
            if (!$order->status_id) {
                $defaultStatusCode = $order->is_recurring ? 'template' : 'pending';
                $defaultUnifiedStatus = OrderStatus::where('code', $defaultStatusCode)->first();
                if ($defaultUnifiedStatus) {
                    $order->status_id = $defaultUnifiedStatus->id;
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
                $templateStatus = OrderStatus::where('code', 'template')->first();
                if ($templateStatus) {
                    $order->status_id = $templateStatus->id;
                }
                // Also update unified status
                $templateUnifiedStatus = OrderStatus::where('code', 'template')->first();
                if ($templateUnifiedStatus) {
                    $order->status_id = $templateUnifiedStatus->id;
                }
            } elseif (!$order->is_recurring && $currentStatus === 'template') {
                // If no longer recurring, change status from template to pending
                $pendingStatus = OrderStatus::where('code', 'pending')->first();
                if ($pendingStatus) {
                    $order->status_id = $pendingStatus->id;
                }
                // Also update unified status
                $pendingUnifiedStatus = OrderStatus::where('code', 'pending')->first();
                if ($pendingUnifiedStatus) {
                    $order->status_id = $pendingUnifiedStatus->id;
                }
            }
            
            // Automatically set customer_type from customer if not set
            if (!$order->customer_type && $order->customer_id) {
                $customer = $order->customer_id ? Customer::find($order->customer_id) : null;
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
                
                $deliveryDate = $order->delivery_date instanceof Carbon 
                    ? $order->delivery_date 
                    : Carbon::parse($order->delivery_date);
                
                switch ($order->billing_frequency) {
                    case 'weekly':
                        $order->billing_period_start = $deliveryDate->copy()->startOfWeek()->toDateString();
                        $order->billing_period_end = $deliveryDate->copy()->endOfWeek()->toDateString();
                        break;
                        
                    case 'biweekly':
                        // Find the start of the bi-weekly period (Monday of the week)
                        $startOfWeek = $deliveryDate->copy()->startOfWeek();
                        $order->billing_period_start = $startOfWeek->toDateString();
                        $order->billing_period_end = $startOfWeek->copy()->addWeeks(2)->subDay()->toDateString();
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
            
            // Automatically calculate and set next_generation_date for recurring templates
            if ($order->is_recurring && $order->parent_recurring_order_id === null) {
                $order->next_generation_date = $order->calculateNextGenerationDate();
            } elseif (!$order->is_recurring || $order->parent_recurring_order_id !== null) {
                $order->next_generation_date = null;
            }
        });
    }
    
    
    /**
     * Get the user who created this order.
     *
     * Relationship to the system user (staff member) who created or manages
     * this order. Essential for tracking responsibility and agricultural
     * order workflow accountability.
     *
     * @return BelongsTo<User> User who created the order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the customer for this order.
     *
     * Relationship to the customer entity placing the agricultural order.
     * Customer data determines pricing, delivery preferences, billing terms,
     * and agricultural production requirements.
     *
     * @return BelongsTo<Customer> Customer placing the order
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    /**
     * Get the unified status for this order.
     *
     * Relationship to the comprehensive order status that reflects the current
     * stage across the entire agricultural workflow (production, fulfillment, payment).
     * Unified status provides single source of truth for order progression.
     *
     * @return BelongsTo<OrderStatus> Current unified order status
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }
    
    /**
     * Get the order type for this order.
     *
     * Relationship to order type classification determining billing behavior,
     * agricultural workflows, and customer interaction patterns.
     * Types include: website (retail), b2b (wholesale), farmers_market (direct sales).
     *
     * @return BelongsTo<OrderType> Order type classification
     */
    public function orderType(): BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }
    
    /**
     * Get the agricultural production status for this order.
     *
     * Relationship to crop status tracking agricultural production phases:
     * not_started → planted → growing → ready_to_harvest → harvested → na (non-agricultural)
     * Essential for coordinating agricultural timing with customer expectations.
     *
     * @return BelongsTo<CropStatus> Current agricultural production status
     */
    public function cropStatus(): BelongsTo
    {
        return $this->belongsTo(CropStatus::class);
    }
    
    /**
     * Get the order fulfillment and shipping status.
     *
     * Relationship to fulfillment status tracking post-harvest activities:
     * pending → packing → ready_for_delivery → out_for_delivery → delivered
     * Coordinates with delivery schedules and customer communication.
     *
     * @return BelongsTo<FulfillmentStatus> Current fulfillment status
     */
    public function fulfillmentStatus(): BelongsTo
    {
        return $this->belongsTo(FulfillmentStatus::class);
    }
    
    /**
     * Get the product line items for this order.
     *
     * Relationship to individual products and quantities ordered by the customer.
     * Each order item references a specific product, price variation, and quantity,
     * driving agricultural production planning and inventory allocation.
     *
     * @return HasMany<OrderItem> Product line items with quantities and pricing
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Get the agricultural crops produced for this order.
     *
     * Relationship to individual crop batches grown to fulfill this order.
     * Each crop represents a specific variety planted, grown, and harvested
     * according to agricultural schedules and customer delivery requirements.
     *
     * @return HasMany<Crop> Agricultural crops for order fulfillment
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class);
    }
    
    /**
     * Get the agricultural production plans for this order.
     *
     * Relationship to crop planning schedules that coordinate planting times
     * with harvest dates to ensure timely delivery. Plans account for variety
     * growth periods, seasonal factors, and production capacity constraints.
     *
     * @return HasMany<CropPlan> Agricultural production planning schedules
     */
    public function cropPlans(): HasMany
    {
        return $this->hasMany(CropPlan::class);
    }
    
    /**
     * Get the payment transactions for this order.
     *
     * Relationship to all payment attempts, successes, and refunds associated
     * with this order. Supports partial payments, installment plans, and
     * complex B2B payment terms common in agricultural wholesale relationships.
     *
     * @return HasMany<Payment> Payment transactions and attempts
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    /**
     * Get the individual invoice for this order.
     *
     * Relationship to single order invoice for immediate billing scenarios.
     * Used for retail orders and website purchases requiring immediate
     * payment and invoicing.
     *
     * @return HasOne<Invoice> Individual order invoice
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
    
    /**
     * Get the consolidated invoice for this order.
     *
     * Relationship to consolidated billing invoice for B2B customers with
     * weekly, monthly, or quarterly billing frequencies. Multiple orders
     * are combined into single invoices for simplified agricultural
     * wholesale billing workflows.
     *
     * @return BelongsTo<Invoice> Consolidated billing invoice
     */
    public function consolidatedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'consolidated_invoice_id');
    }
    
    /**
     * Get the packaging requirements for this order.
     *
     * Relationship to specific packaging allocations including container types,
     * quantities, and special handling requirements. Essential for agricultural
     * post-harvest processing and customer delivery expectations.
     *
     * @return HasMany<OrderPackaging> Packaging requirements and allocations
     */
    public function orderPackagings(): HasMany
    {
        return $this->hasMany(OrderPackaging::class);
    }
    
    /**
     * Get the packaging types for this order.
     *
     * Many-to-many relationship to packaging types with pivot data for quantities
     * and notes. Supports complex packaging requirements where orders need
     * multiple container types and sizes for different agricultural products.
     *
     * @return BelongsToMany<PackagingType> Packaging types with quantities
     */
    public function packagingTypes()
    {
        return $this->belongsToMany(PackagingType::class, 'order_packagings')
            ->withPivot('quantity', 'notes')
            ->withTimestamps();
    }
    
    /**
     * Calculate the total amount for this order.
     *
     * Computes order total by summing all line items (quantity × price).
     * Optimized with eager loading to prevent N+1 queries in agricultural
     * order processing workflows. Essential for payment processing and
     * agricultural financial reporting.
     *
     * @return float Total order amount in dollars
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
     * Check if the order is fully paid.
     *
     * Determines payment completion by comparing total completed payments
     * against order total. Supports partial payments and complex B2B
     * payment arrangements common in agricultural wholesale relationships.
     *
     * @return bool True if total payments >= order total
     */
    public function isPaid(): bool
    {
        $completedStatusId = PaymentStatus::where('code', 'completed')->first()?->id;
        if (!$completedStatusId) {
            return false;
        }
        return $this->payments()->where('status_id', $completedStatusId)->sum('amount') >= $this->totalAmount();
    }

    /**
     * Calculate the remaining unpaid balance.
     *
     * Computes outstanding amount by subtracting completed payments from
     * order total. Used for payment reminders, credit management, and
     * agricultural accounts receivable tracking.
     *
     * @return float Remaining balance in dollars (minimum 0)
     */
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

    /**
     * Get the consumable supplies for this order.
     *
     * Many-to-many relationship to consumable supplies (packaging materials,
     * growing supplies) required for order fulfillment. Used in agricultural
     * cost accounting and inventory management for order-specific supplies.
     *
     * @return BelongsToMany<Consumable> Consumable supplies with quantities
     */
    public function consumables()
    {
        return $this->belongsToMany(Consumable::class, 'order_consumables')
            ->withPivot('quantity', 'notes')
            ->withTimestamps();
    }
    
    /**
     * Calculate the total packaging cost for this order.
     *
     * Computes packaging expenses by summing cost per unit × quantity
     * for all packaging types. Essential for accurate agricultural
     * order costing and margin analysis.
     *
     * @return float Total packaging cost in dollars
     */
    public function packagingCost(): float
    {
        return $this->packagingTypes()->sum(function ($packagingType) {
            return $packagingType->pivot->quantity * $packagingType->cost_per_unit;
        });
    }

    /**
     * Automatically assign appropriate packaging to this order.
     *
     * Intelligent packaging assignment algorithm for agricultural orders:
     * 1. Clears existing packaging assignments
     * 2. Calculates total item quantities
     * 3. Selects appropriate packaging size (prefers medium containers)
     * 4. Assigns packaging with auto-generated notes
     *
     * Used for streamlined order processing when manual packaging
     * selection is not required.
     *
     * @return void
     */
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
     *
     * Relationship to the recurring order template that generated this order.
     * Used for tracking recurring order relationships and maintaining
     * agricultural subscription consistency across generated orders.
     *
     * @return BelongsTo<Order> Parent recurring order template
     */
    public function parentRecurringOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_recurring_order_id');
    }
    
    /**
     * Get the child orders generated from this recurring order template.
     *
     * Relationship to all orders automatically generated from this recurring
     * template. Essential for tracking agricultural subscription fulfillment,
     * analyzing recurring customer patterns, and managing template modifications.
     *
     * @return HasMany<Order> Orders generated from this template
     */
    public function generatedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_recurring_order_id');
    }
    
    /**
     * Check if this is a recurring order template.
     *
     * Determines if this order serves as a template for generating recurring
     * agricultural orders. Templates drive automated order creation based on
     * agricultural schedules and customer delivery preferences.
     *
     * @return bool True if this is a recurring template (not a generated order)
     */
    public function isRecurringTemplate(): bool
    {
        return $this->is_recurring && $this->parent_recurring_order_id === null;
    }
    
    /**
     * Check if this is a B2B recurring order that can generate new orders.
     *
     * Specialized check for B2B recurring templates that support complex
     * wholesale agricultural subscriptions with consolidated billing and
     * advanced scheduling options.
     *
     * @return bool True if B2B recurring template
     */
    public function isB2BRecurringTemplate(): bool
    {
        return $this->orderType?->code === 'b2b' && 
               $this->is_recurring && 
               $this->parent_recurring_order_id === null;
    }
    
    /**
     * Check if this order was generated from a recurring template.
     *
     * Determines if this order was automatically created from a recurring
     * template rather than manually created. Affects order modification
     * permissions and agricultural workflow handling.
     *
     * @return bool True if generated from recurring template
     */
    public function isGeneratedFromRecurring(): bool
    {
        return $this->parent_recurring_order_id !== null;
    }
    
    /**
     * Calculate the next generation date based on frequency and settings.
     *
     * Complex agricultural scheduling algorithm that determines when the next
     * recurring order should be generated. Considers:
     * - Recurring frequency (weekly, biweekly, monthly)
     * - Start delay weeks for initial order timing
     * - Last generation date for subsequent orders
     * - Agricultural timing constraints
     *
     * @return Carbon|null Next generation date or null if not applicable
     */
    public function calculateNextGenerationDate(): ?Carbon
    {
        if (!$this->isRecurringTemplate() || !$this->is_recurring_active) {
            return null;
        }
        
        // If we already have a next_generation_date set, use it
        if ($this->next_generation_date) {
            return $this->next_generation_date instanceof Carbon 
                ? $this->next_generation_date 
                : Carbon::parse($this->next_generation_date);
        }
        
        $baseStartDate = $this->recurring_start_date;
        if (!$baseStartDate) {
            return null;
        }
        
        // Apply the start delay to get the effective start date
        $effectiveStartDate = $baseStartDate instanceof Carbon 
            ? $baseStartDate->copy() 
            : Carbon::parse($baseStartDate);
        
        if ($this->start_delay_weeks > 0) {
            $effectiveStartDate->addWeeks($this->start_delay_weeks);
        }
        
        // If this is the first generation and we haven't generated any orders yet
        if (!$this->last_generated_at) {
            // Find the first harvest day (based on harvest_day) on or after the effective start date
            $firstHarvestDate = $this->calculateNextDateForDayTime('harvest', $effectiveStartDate);
            
            // If the calculated harvest date is before the effective start date, move to next week
            if ($firstHarvestDate->lt($effectiveStartDate)) {
                $firstHarvestDate->addWeek();
            }
            
            return $firstHarvestDate;
        }
        
        // For subsequent generations, use the last generated date as the base
        $lastDate = $this->last_generated_at instanceof Carbon 
            ? $this->last_generated_at 
            : Carbon::parse($this->last_generated_at);
        
        return match($this->recurring_frequency) {
            'weekly' => $lastDate->copy()->addWeek(),
            'biweekly' => $lastDate->copy()->addWeeks($this->recurring_interval ?? 2),
            'monthly' => $lastDate->copy()->addMonth(),
            default => null
        };
    }
    
    /**
     * Calculate the next occurrence of a specific day from a given date.
     *
     * Agricultural timing utility that finds the next occurrence of a specified
     * day (harvest_day or delivery_day) from a given starting date. Essential
     * for coordinating agricultural schedules with customer preferences.
     *
     * @param string $type Type of day to calculate ('harvest' or 'delivery')
     * @param Carbon $fromDate Starting date for calculation
     * @return Carbon Next occurrence of the specified day
     */
    public function calculateNextDateForDayTime(string $type, Carbon $fromDate): Carbon
    {
        $dayField = $type . '_day';
        $targetDay = $this->{$dayField} ?? null;
        
        if (!$targetDay) {
            // Fallback to simple calculation if no schedule defined
            return $type === 'harvest' ? $fromDate->copy() : $fromDate->copy()->addDay();
        }
        
        // Convert day name to Carbon day constant
        $dayMap = [
            'sunday' => Carbon::SUNDAY,
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
        ];
        
        $targetDayConstant = $dayMap[strtolower($targetDay)] ?? Carbon::MONDAY;
        
        // Find the next occurrence of the target day
        $targetDate = $fromDate->copy()->next($targetDayConstant);
        
        // If the target day is today, use today
        if ($fromDate->dayOfWeek === $targetDayConstant) {
            $targetDate = $fromDate->copy();
        }
        
        return $targetDate;
    }
    
    /**
     * Generate multiple orders to catch up to current date plus several weeks ahead.
     *
     * Batch generation method for recurring agricultural orders that creates
     * multiple orders to maintain adequate production pipeline. Generates
     * 6 weeks ahead to accommodate longer-growing crops like basil (21 days).
     *
     * Prevents duplicate orders and manages template state efficiently
     * for high-volume agricultural subscription processing.
     *
     * @return array<Order> Array of generated orders
     */
    public function generateRecurringOrdersCatchUp(): array
    {
        if (!$this->isRecurringTemplate() || !$this->is_recurring_active) {
            return [];
        }
        
        $generatedOrders = [];
        $weeksToGenerate = 6; // Generate 6 weeks ahead to accommodate crops with longer growth periods like basil (21 days)
        
        // Track the current generation date locally instead of relying on database refresh
        $currentGenerationDate = $this->calculateNextGenerationDate();
        if (!$currentGenerationDate) {
            return [];
        }
        
        for ($week = 0; $week < $weeksToGenerate; $week++) {
            // Generate order for the current generation date
            $nextOrder = $this->generateOrderForSpecificDate($currentGenerationDate);
            if (!$nextOrder) {
                break; // Stop if generation fails
            }
            
            $generatedOrders[] = $nextOrder;
            
            // Advance to next generation date locally
            $currentGenerationDate = match($this->recurring_frequency) {
                'weekly' => $currentGenerationDate->copy()->addWeek(),
                'biweekly' => $currentGenerationDate->copy()->addWeeks($this->recurring_interval ?? 2),
                'monthly' => $currentGenerationDate->copy()->addMonth(),
                default => null
            };
            
            if (!$currentGenerationDate) {
                break;
            }
        }
        
        // Update the template's dates after all orders are generated
        if (!empty($generatedOrders)) {
            $this->update([
                'last_generated_at' => now(),
                'next_generation_date' => $currentGenerationDate
            ]);
        }
        
        return $generatedOrders;
    }

    /**
     * Generate an order for a specific generation date without updating template state.
     *
     * Internal method used by batch generation to create individual recurring orders
     * for specific dates. Handles agricultural timing calculations, duplicate prevention,
     * order replication, and price recalculation for current market conditions.
     *
     * Used internally by generateRecurringOrdersCatchUp().
     *
     * @param Carbon $generationDate Target date for order generation
     * @return Order|null Generated order or null if generation failed/skipped
     */
    protected function generateOrderForSpecificDate(Carbon $generationDate): ?Order
    {
        if (!$this->isRecurringTemplate() || !$this->is_recurring_active) {
            return null;
        }
        
        // Check if we're past the end date
        if ($this->recurring_end_date && $generationDate->gt($this->recurring_end_date)) {
            return null;
        }
        
        // Calculate actual harvest and delivery dates based on day/time settings
        $harvestDate = $this->calculateNextDateForDayTime('harvest', $generationDate);
        $deliveryDate = $this->calculateNextDateForDayTime('delivery', $generationDate);
        
        // Check if an order already exists for this delivery date to prevent duplicates
        $existingOrder = $this->generatedOrders()
            ->where('delivery_date', $deliveryDate->format('Y-m-d'))
            ->first();
            
        if ($existingOrder) {
            return null; // Skip if order already exists
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
        $newOrder->is_recurring = false; // Generated orders are not recurring themselves
        $newOrder->harvest_date = $harvestDate;
        $newOrder->delivery_date = $deliveryDate;
        
        // Set default statuses for generated orders
        $pendingOrderStatus = OrderStatus::where('code', 'pending')->first();
        if ($pendingOrderStatus) {
            $newOrder->status_id = $pendingOrderStatus->id;
        }
        
        $pendingPaymentStatus = PaymentStatus::where('code', 'pending')->first();
        if ($pendingPaymentStatus) {
            $newOrder->payment_status_id = $pendingPaymentStatus->id;
        }
        
        // Ensure relationships are loaded BEFORE saving the order
        if (!$this->relationLoaded('orderItems')) {
            $this->load(['orderItems.product', 'orderItems.priceVariation', 'customer', 'orderType', 'packagingTypes']);
        }
        
        $newOrder->save();
        
        // Copy order items with recalculated prices
        foreach ($this->orderItems as $item) {
            $currentPrice = $item->price; // Default to original price
            
            // Recalculate price based on current customer and product pricing
            if ($item->product && $this->customer) {
                try {
                    $currentPrice = $item->product->getPriceForSpecificCustomer(
                        $this->customer, 
                        $item->price_variation_id
                    );
                } catch (Exception $e) {
                    // If price calculation fails, use original price
                    Log::warning('Failed to calculate price for recurring order item', [
                        'template_id' => $this->id,
                        'product_id' => $item->product_id,
                        'error' => $e->getMessage()
                    ]);
                    $currentPrice = $item->price;
                }
            }
            
            try {
                $newOrderItem = $newOrder->orderItems()->create([
                    'product_id' => $item->product_id,
                    'price_variation_id' => $item->price_variation_id,
                    'quantity' => $item->quantity,
                    'price' => $currentPrice,
                ]);
                
                Log::info('Created order item for recurring order', [
                    'template_id' => $this->id,
                    'new_order_id' => $newOrder->id,
                    'order_item_id' => $newOrderItem->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $currentPrice,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to create order item for recurring order', [
                    'template_id' => $this->id,
                    'new_order_id' => $newOrder->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        // Copy packaging
        foreach ($this->packagingTypes as $packaging) {
            $newOrder->packagingTypes()->attach($packaging->id, [
                'quantity' => $packaging->pivot->quantity,
                'notes' => $packaging->pivot->notes,
            ]);
        }
        
        return $newOrder;
    }

    /**
     * Generate the next order in the recurring series.
     *
     * Creates the next scheduled order from this recurring template with
     * complete agricultural timing calculations and current pricing.
     * Updates template generation tracking and handles end-date validation
     * for agricultural subscription management.
     *
     * @return Order|null Generated order or null if generation not applicable
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
            // Order already exists for this date, advance to next generation date
            $nextGenerationDate = match($this->recurring_frequency) {
                'weekly' => $nextDate->copy()->addWeek(),
                'biweekly' => $nextDate->copy()->addWeeks($this->recurring_interval ?? 2),
                'monthly' => $nextDate->copy()->addMonth(),
                default => null
            };
            
            $this->update([
                'next_generation_date' => $nextGenerationDate
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
        $newOrder->is_recurring = false; // Generated orders are not recurring themselves
        
        // Calculate actual harvest and delivery dates based on day/time settings
        $harvestDate = $this->calculateNextDateForDayTime('harvest', $nextDate);
        $deliveryDate = $this->calculateNextDateForDayTime('delivery', $nextDate);
        
        $newOrder->harvest_date = $harvestDate;
        $newOrder->delivery_date = $deliveryDate;
        
        // Set default statuses for generated orders
        $pendingOrderStatus = OrderStatus::where('code', 'pending')->first();
        if ($pendingOrderStatus) {
            $newOrder->status_id = $pendingOrderStatus->id;
        }
        
        $pendingPaymentStatus = PaymentStatus::where('code', 'pending')->first();
        if ($pendingPaymentStatus) {
            $newOrder->payment_status_id = $pendingPaymentStatus->id;
        }
        
        // Ensure relationships are loaded BEFORE saving the order
        if (!$this->relationLoaded('orderItems')) {
            $this->load(['orderItems.product', 'orderItems.priceVariation', 'customer', 'orderType', 'packagingTypes']);
        }
        
        $newOrder->save();
        
        // Copy order items with recalculated prices
        foreach ($this->orderItems as $item) {
            $currentPrice = $item->price; // Default to original price
            
            // Recalculate price based on current customer and product pricing
            if ($item->product && $this->customer) {
                try {
                    $currentPrice = $item->product->getPriceForSpecificCustomer(
                        $this->customer, 
                        $item->price_variation_id
                    );
                } catch (Exception $e) {
                    // If price calculation fails, use original price
                    Log::warning('Failed to calculate price for recurring order item', [
                        'template_id' => $this->id,
                        'product_id' => $item->product_id,
                        'error' => $e->getMessage()
                    ]);
                    $currentPrice = $item->price;
                }
            }
            
            try {
                $newOrderItem = $newOrder->orderItems()->create([
                    'product_id' => $item->product_id,
                    'price_variation_id' => $item->price_variation_id,
                    'quantity' => $item->quantity,
                    'price' => $currentPrice,
                ]);
                
                Log::info('Created order item for recurring order', [
                    'template_id' => $this->id,
                    'new_order_id' => $newOrder->id,
                    'order_item_id' => $newOrderItem->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $currentPrice,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to create order item for recurring order', [
                    'template_id' => $this->id,
                    'new_order_id' => $newOrder->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        
        // Copy packaging
        foreach ($this->packagingTypes as $packaging) {
            $newOrder->packagingTypes()->attach($packaging->id, [
                'quantity' => $packaging->pivot->quantity,
                'notes' => $packaging->pivot->notes,
            ]);
        }
        
        // Calculate next generation date before updating last_generated_at
        $currentGenerationDate = $nextDate;
        
        // Update the last_generated_at first
        $this->last_generated_at = now();
        
        // Now calculate the next generation date based on the updated last_generated_at
        $nextGenerationDate = match($this->recurring_frequency) {
            'weekly' => $currentGenerationDate->copy()->addWeek(),
            'biweekly' => $currentGenerationDate->copy()->addWeeks($this->recurring_interval ?? 2),
            'monthly' => $currentGenerationDate->copy()->addMonth(),
            default => null
        };
        
        // Save both fields
        $this->update([
            'last_generated_at' => $this->last_generated_at,
            'next_generation_date' => $nextGenerationDate
        ]);
        
        return $newOrder;
    }
    
    /**
     * Get formatted recurring frequency display.
     *
     * Provides human-readable description of recurring frequency for
     * customer communication and agricultural subscription management.
     * Handles custom intervals (e.g., "Every 2 weeks").
     *
     * @return string Formatted frequency description
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
     *
     * Accessor for counting orders generated from this recurring template.
     * Used for agricultural subscription analytics, template performance
     * tracking, and customer relationship management.
     *
     * @return int Number of orders generated from this template
     */
    public function getGeneratedOrdersCountAttribute(): int
    {
        return $this->generatedOrders()->count();
    }

    /**
     * Get the customer type (from order or customer relationship).
     *
     * Accessor with fallback hierarchy for customer classification:
     * 1. Use order-specific customer_type if set
     * 2. Fall back to customer's default type
     * 3. Default to 'retail' if no classification available
     *
     * Essential for agricultural pricing and workflow decisions.
     *
     * @param string|null $value Stored customer type value
     * @return string Customer type classification
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
     *
     * Provides formatted customer type for UI display in agricultural
     * order management interfaces and customer communications.
     *
     * @return string Formatted customer type name
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
     *
     * Provides formatted order type for UI display and agricultural
     * workflow categorization. Falls back to 'Unknown' if type not set.
     *
     * @return string Formatted order type name
     */
    public function getOrderTypeDisplayAttribute(): string
    {
        return $this->orderType?->name ?? 'Unknown';
    }
    
    /**
     * Get the billing frequency display name.
     *
     * Provides formatted billing frequency for B2B agricultural customer
     * communications and consolidated billing workflows.
     *
     * @return string Formatted billing frequency description
     */
    public function getBillingFrequencyDisplayAttribute(): string
    {
        return match($this->billing_frequency) {
            'immediate' => 'Immediate',
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            default => 'Immediate',
        };
    }
    
    
    /**
     * Check if this order requires immediate invoicing.
     *
     * Determines if order should be invoiced immediately upon confirmation
     * rather than included in consolidated billing. Applies to website orders
     * and orders specifically marked for immediate billing.
     *
     * @return bool True if immediate invoicing required
     */
    public function requiresImmediateInvoicing(): bool
    {
        return $this->orderType?->code === 'website' || 
               $this->billing_frequency === 'immediate';
    }
    
    /**
     * Check if this order is part of consolidated billing.
     *
     * Determines if order should be included in periodic consolidated invoices
     * for B2B agricultural customers with weekly, monthly, or quarterly
     * billing arrangements.
     *
     * @return bool True if consolidated billing applies
     */
    public function isConsolidatedBilling(): bool
    {
        return $this->orderType?->code === 'b2b' && 
               in_array($this->billing_frequency, ['weekly', 'monthly', 'quarterly']);
    }
    
    /**
     * Check if this order should bypass invoicing completely.
     *
     * Determines if order should skip invoice generation entirely,
     * typically for farmers market sales with cash payments that
     * don't require formal invoicing processes.
     *
     * @return bool True if invoicing should be bypassed
     */
    public function shouldBypassInvoicing(): bool
    {
        return $this->orderType?->code === 'farmers_market' && !$this->requires_invoice;
    }
    
    /**
     * Check if the order requires agricultural crop production.
     *
     * Determines if order contains products that need agricultural growing
     * by checking for items with seed varieties (master_seed_catalog_id)
     * or product mixes requiring crop cultivation.
     *
     * @return bool True if agricultural production required
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
     * Check if order should have agricultural crop plans generated.
     *
     * Determines if order requires crop planning based on:
     * - Contains products needing agricultural production
     * - Not in final delivery state
     * - Not a recurring template
     * - Not in template status
     *
     * @return bool True if crop plans should be generated
     */
    public function shouldHaveCropPlans(): bool
    {
        // Order should have plans if it requires crops and is not in a final or template state
        return $this->requiresCropProduction() 
            && !$this->isInFinalState() 
            && $this->status?->code !== 'template'
            && !$this->is_recurring; // Don't generate for recurring templates
    }
    
    /**
     * Update agricultural crop status based on related crops.
     *
     * Analyzes all crops associated with this order and determines
     * appropriate agricultural status:
     * - 'na': No agricultural production required
     * - 'not_started': No crops created yet
     * - 'planted': Crops exist but none planted
     * - 'growing': Some crops planted
     * - 'ready_to_harvest': All crops ready for harvest
     * - 'harvested': All crops harvested
     *
     * @return void
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
     *
     * Complex agricultural workflow orchestration that determines the most appropriate
     * unified status by analyzing current state across all order dimensions:
     * - Order status (draft, pending, confirmed, etc.)
     * - Crop status (agricultural production phase)
     * - Fulfillment status (post-harvest processing)
     *
     * Provides single source of truth for order progression across
     * the complete agricultural and fulfillment workflow.
     *
     * @return void
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
     *
     * Internal helper method that sets unified status by looking up
     * OrderStatus record by code and updating if different from current.
     * Prevents unnecessary database updates for status synchronization.
     *
     * @param string $statusCode Status code to set
     * @return void
     */
    private function updateUnifiedStatus(string $statusCode): void
    {
        $status = OrderStatus::findByCode($statusCode);
        if ($status && $status->id !== $this->status_id) {
            $this->update(['status_id' => $status->id]);
        }
    }
    
    /**
     * Get a combined status display.
     *
     * Legacy method providing combined status display by concatenating
     * order, crop, and fulfillment statuses. Maintained for backwards
     * compatibility but superseded by unified status system.
     *
     * @return string Combined status description
     */
    public function getCombinedStatusAttribute(): string
    {
        // If unified status is available, use it as primary display
        if ($this->status) {
            return $this->status->name;
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
     *
     * Provides comprehensive status display including both status name
     * and stage information for complete agricultural workflow visibility.
     * Used in order management interfaces and customer communications.
     *
     * @return string Unified status with stage display
     */
    public function getUnifiedStatusDisplayAttribute(): string
    {
        if (!$this->status) {
            return 'Unknown';
        }
        
        return sprintf(
            '%s (%s)',
            $this->status->name,
            $this->status->stage_display
        );
    }
    
    /**
     * Get the unified status color for UI display.
     *
     * Provides color coding for unified status to enable visual order
     * management in agricultural workflow interfaces. Colors indicate
     * urgency and stage progression for operational efficiency.
     *
     * @return string Color code for UI display (e.g., 'green', 'orange', 'red')
     */
    public function getUnifiedStatusColorAttribute(): string
    {
        return $this->status?->getDisplayColor() ?? 'gray';
    }
    
    /**
     * Check if the order can be modified based on unified status.
     *
     * Determines if order allows modifications based on current workflow stage.
     * Prevents changes to orders in agricultural production or fulfillment
     * phases where modifications would disrupt operations.
     *
     * @return bool True if order modifications are allowed
     */
    public function canBeModified(): bool
    {
        return $this->status?->canBeModified() ?? true;
    }
    
    /**
     * Check if the order is in a final state.
     *
     * Determines if order has reached a terminal status (delivered, cancelled)
     * where no further agricultural workflow progression is expected.
     * Used for workflow validation and order management permissions.
     *
     * @return bool True if order is in final state
     */
    public function isInFinalState(): bool
    {
        return $this->status?->is_final ?? false;
    }
    
    /**
     * Get valid next unified statuses for this order.
     *
     * Returns collection of possible status transitions from current state
     * based on agricultural workflow rules and business logic.
     * Used for status transition validation and UI workflow controls.
     *
     * @return Collection<OrderStatus> Valid next status options
     */
    public function getValidNextStatuses(): Collection
    {
        if (!$this->status) {
            return collect();
        }
        
        return OrderStatus::getValidNextStatuses($this->status->code);
    }
    
    /**
     * Configure the activity log options for this model.
     *
     * Defines comprehensive activity logging for agricultural order changes.
     * Tracks critical order modifications, status transitions, and recurring
     * order management for audit trails and business intelligence.
     *
     * @return LogOptions Configured activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'customer_id', 'harvest_date', 'delivery_date', 'status', 'crop_status', 
                'fulfillment_status', 'status_id', 'customer_type', 'is_recurring', 
                'recurring_frequency', 'recurring_start_date', 'recurring_end_date', 
                'is_recurring_active', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Order was {$eventName}");
    }

    /**
     * Get the relationships that should be logged with this model.
     *
     * Defines which related models should be included in activity logging
     * when this order is created, updated, or deleted. Essential for
     * comprehensive audit trails in agricultural order management.
     *
     * @return array<string> Array of relationship method names to log
     */
    public function getLoggedRelationships(): array
    {
        return ['customer', 'orderStatus', 'status', 'orderType', 'orderItems', 'crops'];
    }

    /**
     * Get specific attributes to include from related models.
     *
     * Specifies which attributes from related models should be captured
     * in activity logs. Optimizes log storage while maintaining sufficient
     * detail for agricultural order audit and analysis requirements.
     *
     * @return array<string, array<string>> Relationship => [attributes] mapping
     */
    public function getRelationshipAttributesToLog(): array
    {
        return [
            'customer' => ['id', 'name', 'email', 'phone'],
            'orderStatus' => ['id', 'name', 'code'],
            'status' => ['id', 'name', 'code', 'stage'],
            'orderType' => ['id', 'name', 'code'],
            'orderItems' => ['id', 'product_id', 'quantity', 'price'],
            'crops' => ['id', 'recipe_id', 'tray_number', 'current_stage_id', 'planting_at'],
        ];
    }
    
    /**
     * Transition the order to a new unified status with validation.
     *
     * Delegates to StatusTransitionService for complex agricultural workflow
     * validation and execution. Handles business rules, prerequisite checks,
     * and cascading updates across the order ecosystem.
     *
     * @param string $statusCode Target status code for transition
     * @param array $context Additional context for the transition
     * @return array ['success' => bool, 'message' => string] Transition result
     */
    public function transitionTo(string $statusCode, array $context = []): array
    {
        $statusService = app(StatusTransitionService::class);
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
     * Validates if transition to target status is allowed based on current
     * state, agricultural workflow rules, and business constraints.
     * Uses StatusTransitionService for comprehensive validation logic.
     *
     * @param string $statusCode Target status code to validate
     * @return bool True if transition is allowed
     */
    public function canTransitionTo(string $statusCode): bool
    {
        $statusService = app(StatusTransitionService::class);
        $validation = $statusService->validateTransition($this, $statusCode);
        return $validation['valid'];
    }
    
    /**
     * Get the status transition history from the activity log.
     *
     * Retrieves chronological record of all status transitions for this order,
     * providing complete agricultural workflow audit trail. Essential for
     * order troubleshooting, performance analysis, and customer communication.
     *
     * @return Collection<Activity> Status transition history
     */
    public function getStatusHistory(): Collection
    {
        $statusService = app(StatusTransitionService::class);
        return $statusService->getStatusHistory($this);
    }
}
