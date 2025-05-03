<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecurringOrder extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'frequency',
        'delivery_days',
        'customer_type',
        'start_date',
        'end_date',
        'interval',
        'interval_unit',
        'is_active',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'delivery_days' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the user (customer) for this recurring order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the order items for this recurring order.
     */
    public function recurringOrderItems(): HasMany
    {
        return $this->hasMany(RecurringOrderItem::class);
    }
    
    /**
     * Generate the next order dates from a starting point
     * 
     * @param Carbon $startFrom Date to start generating from
     * @param int $count Number of dates to generate
     * @return array Array of Carbon dates
     */
    public function generateDeliveryDates(Carbon $startFrom = null, int $count = 10): array
    {
        $startFrom = $startFrom ?? Carbon::now();
        $dates = [];
        $current = max($startFrom, $this->start_date);
        
        // If order is no longer active or past end date, return empty array
        if (!$this->is_active || ($this->end_date && $current->gt($this->end_date))) {
            return $dates;
        }
        
        // Loop until we have enough dates or reach end date
        while (count($dates) < $count) {
            // Skip if before start date
            if ($current->lt($this->start_date)) {
                $current = $current->addDay();
                continue;
            }
            
            // Stop if we've passed the end date
            if ($this->end_date && $current->gt($this->end_date)) {
                break;
            }
            
            // Check if current date's day of week is in delivery_days
            $dayOfWeek = $current->dayOfWeek;
            if (in_array($dayOfWeek, $this->delivery_days)) {
                $dates[] = $current->copy();
                
                // Advance based on frequency
                switch ($this->frequency) {
                    case 'weekly':
                        $current = $current->addWeek();
                        break;
                    case 'biweekly':
                        $current = $current->addWeeks(2);
                        break;
                    case 'monthly':
                        $current = $current->addMonth();
                        break;
                    case 'custom':
                        if ($this->interval && $this->interval_unit) {
                            switch ($this->interval_unit) {
                                case 'days':
                                    $current = $current->addDays($this->interval);
                                    break;
                                case 'weeks':
                                    $current = $current->addWeeks($this->interval);
                                    break;
                                case 'months':
                                    $current = $current->addMonths($this->interval);
                                    break;
                                default:
                                    $current = $current->addWeek();
                            }
                        } else {
                            $current = $current->addWeek();
                        }
                        break;
                    default:
                        $current = $current->addWeek();
                }
            } else {
                // Move to next day
                $current = $current->addDay();
            }
        }
        
        return $dates;
    }
    
    /**
     * Calculate the total amount for this recurring order based on current prices.
     */
    public function totalAmount(): float
    {
        return $this->recurringOrderItems->sum(function ($item) {
            $price = $item->price ?? $item->item->price;
            return $item->quantity * $price;
        });
    }
    
    /**
     * Generate actual Order from this recurring order for a specific delivery date
     */
    public function generateOrder(Carbon $deliveryDate): ?Order
    {
        // Skip if order is inactive or outside date range
        if (!$this->is_active || 
            ($this->end_date && $deliveryDate->gt($this->end_date)) || 
            $deliveryDate->lt($this->start_date)) {
            return null;
        }
        
        // Generate the order
        $order = new Order([
            'user_id' => $this->user_id,
            'delivery_date' => $deliveryDate,
            'harvest_date' => $deliveryDate->copy()->subDay(), // Default to day before
            'status' => 'pending',
            'customer_type' => $this->customer_type,
            'notes' => "Auto-generated from recurring order: {$this->name}"
        ]);
        
        $order->save();
        
        // Add order items
        foreach ($this->recurringOrderItems as $recurringItem) {
            $orderItem = new OrderItem([
                'order_id' => $order->id,
                'item_id' => $recurringItem->item_id,
                'quantity' => $recurringItem->quantity,
                'price' => $recurringItem->price ?? $recurringItem->item->price,
                'notes' => $recurringItem->notes
            ]);
            
            $orderItem->save();
        }
        
        return $order;
    }
    
    /**
     * Calculate planting requirements for the next dates
     */
    public function calculatePlantingRequirements(Carbon $startFrom = null, int $count = 10): array
    {
        $startFrom = $startFrom ?? Carbon::now();
        $deliveryDates = $this->generateDeliveryDates($startFrom, $count);
        $plantingRequirements = [];
        
        foreach ($deliveryDates as $deliveryDate) {
            $harvestDate = $deliveryDate->copy()->subDay(); // Default to day before delivery
            
            // Get required crops for each recurring order item
            foreach ($this->recurringOrderItems as $item) {
                if (!$item->item->recipe_id) continue;
                
                $recipe = Recipe::find($item->item->recipe_id);
                if (!$recipe) continue;
                
                $totalDays = $recipe->totalDays();
                $plantingDate = $harvestDate->copy()->subDays($totalDays);
                
                // Calculate trays needed
                $quantityNeeded = $item->quantity;
                $expectedYieldPerTray = $item->item->expected_yield_grams;
                $traysNeeded = 0;
                
                if ($expectedYieldPerTray > 0) {
                    $traysNeeded = ceil($quantityNeeded / ($expectedYieldPerTray / 1000));
                }
                
                // Create unique key for grouping by planting date, harvest date and recipe
                $key = $plantingDate->format('Y-m-d') . '_' . $harvestDate->format('Y-m-d') . '_' . $recipe->id;
                
                if (!isset($plantingRequirements[$key])) {
                    $plantingRequirements[$key] = [
                        'planting_date' => $plantingDate,
                        'harvest_date' => $harvestDate,
                        'recipe_id' => $recipe->id,
                        'recipe_name' => $recipe->name,
                        'trays_required' => 0,
                        'items' => [],
                        'delivery_dates' => [],
                    ];
                }
                
                // Add to the total trays required
                $plantingRequirements[$key]['trays_required'] += $traysNeeded;
                
                // Remember which items this is for
                $plantingRequirements[$key]['items'][] = [
                    'item_id' => $item->item_id,
                    'item_name' => $item->item->name,
                    'quantity' => $item->quantity,
                    'trays_needed' => $traysNeeded,
                ];
                
                // Remember which delivery this is for
                if (!in_array($deliveryDate->format('Y-m-d'), $plantingRequirements[$key]['delivery_dates'])) {
                    $plantingRequirements[$key]['delivery_dates'][] = $deliveryDate->format('Y-m-d');
                }
            }
        }
        
        return array_values($plantingRequirements);
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id', 'name', 'frequency', 'delivery_days', 'customer_type', 
                'start_date', 'end_date', 'interval', 'interval_unit', 'is_active', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Recurring order was {$eventName}");
    }
} 