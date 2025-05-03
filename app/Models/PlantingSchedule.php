<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PlantingSchedule extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'planting_date',
        'target_harvest_date',
        'recipe_id',
        'trays_required',
        'trays_planted',
        'status',
        'related_orders',
        'related_recurring_orders',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'planting_date' => 'date',
        'target_harvest_date' => 'date',
        'trays_required' => 'integer',
        'trays_planted' => 'integer',
        'related_orders' => 'array',
        'related_recurring_orders' => 'array',
    ];
    
    /**
     * Get the recipe for this planting schedule.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
    
    /**
     * Get related orders
     */
    public function orders()
    {
        if (!$this->related_orders) {
            return collect([]);
        }
        
        return Order::whereIn('id', $this->related_orders)->get();
    }
    
    /**
     * Get related recurring orders
     */
    public function recurringOrders()
    {
        if (!$this->related_recurring_orders) {
            return collect([]);
        }
        
        return RecurringOrder::whereIn('id', $this->related_recurring_orders)->get();
    }
    
    /**
     * Get actual associated crops that have been planted for this schedule
     */
    public function crops()
    {
        $plantingDateStart = $this->planting_date->copy()->startOfDay();
        $plantingDateEnd = $this->planting_date->copy()->endOfDay();
        
        return Crop::where('recipe_id', $this->recipe_id)
            ->whereBetween('planted_at', [$plantingDateStart, $plantingDateEnd])
            ->get();
    }
    
    /**
     * Scope a query to find the plantings for a given date range.
     */
    public function scopeInDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('planting_date', [$startDate, $endDate])
                ->orWhereBetween('target_harvest_date', [$startDate, $endDate]);
        });
    }
    
    /**
     * Scope a query to find the plantings for a given week.
     */
    public function scopeInWeek(Builder $query, Carbon $date): Builder
    {
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();
        
        return $query->inDateRange($startOfWeek, $endOfWeek);
    }
    
    /**
     * Update status based on trays planted
     */
    public function updateStatus(): void
    {
        if ($this->trays_planted === 0) {
            $this->status = 'pending';
        } elseif ($this->trays_planted < $this->trays_required) {
            $this->status = 'partially_planted';
        } elseif ($this->trays_planted >= $this->trays_required) {
            $this->status = 'fully_planted';
        }
        
        // Mark as completed if the harvest date is in the past and status was fully_planted
        if ($this->status === 'fully_planted' && 
            $this->target_harvest_date && 
            $this->target_harvest_date->isPast()) {
            $this->status = 'completed';
        }
        
        $this->save();
    }
    
    /**
     * Generate trays based on this planting schedule
     * 
     * @param int $count Number of trays to create
     * @return array Created crop models
     */
    public function generateTrays(int $count = 1): array
    {
        if ($count <= 0) {
            return [];
        }
        
        $recipe = $this->recipe;
        if (!$recipe) {
            return [];
        }
        
        $createdCrops = [];
        
        // Find order (if any)
        $orderId = null;
        if (!empty($this->related_orders)) {
            $orderId = $this->related_orders[0];
        }
        
        // Get the next available tray numbers
        $lastTrayNumber = Crop::max('tray_number');
        $nextTrayNumber = $lastTrayNumber ? (int)$lastTrayNumber + 1 : 1;
        
        // Create the crops
        for ($i = 0; $i < $count; $i++) {
            $trayNumber = $nextTrayNumber + $i;
            
            $crop = new Crop([
                'recipe_id' => $recipe->id,
                'order_id' => $orderId,
                'tray_number' => (string)$trayNumber,
                'planted_at' => $this->planting_date,
                'current_stage' => 'germination',
                'notes' => "Created from planting schedule #{$this->id}, targeting harvest on {$this->target_harvest_date->format('Y-m-d')}",
            ]);
            
            $crop->save();
            $createdCrops[] = $crop;
        }
        
        // Update trays planted count
        $this->trays_planted += $count;
        $this->updateStatus();
        
        return $createdCrops;
    }
    
    /**
     * Sync planting schedules from recurring orders
     * 
     * @param Carbon $startDate Start date for calculation
     * @param Carbon $endDate End date for calculation
     * @return int Number of planting schedules created
     */
    public static function syncFromRecurringOrders(Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;
        
        // Get all active recurring orders
        $recurringOrders = RecurringOrder::where('is_active', true)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startDate);
            })
            ->where('start_date', '<=', $endDate)
            ->get();
        
        foreach ($recurringOrders as $recurringOrder) {
            $plantingRequirements = $recurringOrder->calculatePlantingRequirements($startDate, 50);
            
            foreach ($plantingRequirements as $requirement) {
                $plantingDate = $requirement['planting_date'];
                $harvestDate = $requirement['harvest_date'];
                
                // Skip if outside our range
                if ($plantingDate->lt($startDate) || $plantingDate->gt($endDate)) {
                    continue;
                }
                
                // Check if we already have a planting schedule for this recipe/date combo
                $existingSchedule = self::where('recipe_id', $requirement['recipe_id'])
                    ->whereDate('planting_date', $plantingDate)
                    ->whereDate('target_harvest_date', $harvestDate)
                    ->first();
                
                if ($existingSchedule) {
                    // Update existing schedule
                    $recurringOrderIds = $existingSchedule->related_recurring_orders ?? [];
                    if (!in_array($recurringOrder->id, $recurringOrderIds)) {
                        $recurringOrderIds[] = $recurringOrder->id;
                        $existingSchedule->related_recurring_orders = $recurringOrderIds;
                    }
                    
                    // Update trays required (might have changed)
                    $existingSchedule->trays_required = max(
                        $existingSchedule->trays_required,
                        $requirement['trays_required']
                    );
                    
                    $existingSchedule->updateStatus();
                    $existingSchedule->save();
                } else {
                    // Create new schedule
                    $schedule = new self([
                        'planting_date' => $plantingDate,
                        'target_harvest_date' => $harvestDate,
                        'recipe_id' => $requirement['recipe_id'],
                        'trays_required' => $requirement['trays_required'],
                        'trays_planted' => 0,
                        'status' => 'pending',
                        'related_recurring_orders' => [$recurringOrder->id],
                        'notes' => "Auto-generated from recurring order: {$recurringOrder->name}",
                    ]);
                    
                    $schedule->save();
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Create a planting schedule from a specific order
     */
    public static function createFromOrder(Order $order): array
    {
        $created = [];
        
        // Calculate the planting requirements for each item in the order
        foreach ($order->orderItems as $orderItem) {
            $item = $orderItem->item;
            if (!$item || !$item->recipe_id) continue;
            
            $recipe = Recipe::find($item->recipe_id);
            if (!$recipe) continue;
            
            // Calculate planting and harvest dates
            $harvestDate = $order->harvest_date;
            $totalDays = $recipe->totalDays();
            $plantingDate = Carbon::parse($harvestDate)->subDays($totalDays);
            
            // Calculate trays needed
            $quantityNeeded = $orderItem->quantity;
            $expectedYieldPerTray = $item->expected_yield_grams;
            $traysNeeded = 0;
            
            if ($expectedYieldPerTray > 0) {
                $traysNeeded = ceil($quantityNeeded / ($expectedYieldPerTray / 1000));
            }
            
            // Check if a planting schedule already exists
            $existingSchedule = self::where('recipe_id', $recipe->id)
                ->whereDate('planting_date', $plantingDate)
                ->whereDate('target_harvest_date', $harvestDate)
                ->first();
            
            if ($existingSchedule) {
                // Update existing schedule
                $orderIds = $existingSchedule->related_orders ?? [];
                if (!in_array($order->id, $orderIds)) {
                    $orderIds[] = $order->id;
                    $existingSchedule->related_orders = $orderIds;
                }
                
                // Update trays required (might have changed)
                $existingSchedule->trays_required = max(
                    $existingSchedule->trays_required,
                    $traysNeeded
                );
                
                $existingSchedule->updateStatus();
                $existingSchedule->save();
                
                $created[] = $existingSchedule;
            } else {
                // Create new schedule
                $schedule = new self([
                    'planting_date' => $plantingDate,
                    'target_harvest_date' => $harvestDate,
                    'recipe_id' => $recipe->id,
                    'trays_required' => $traysNeeded,
                    'trays_planted' => 0,
                    'status' => 'pending',
                    'related_orders' => [$order->id],
                    'notes' => "Created from order #{$order->id} for {$order->user->name}",
                ]);
                
                $schedule->save();
                $created[] = $schedule;
            }
        }
        
        return $created;
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'planting_date', 'target_harvest_date', 'recipe_id', 
                'trays_required', 'trays_planted', 'status', 
                'related_orders', 'related_recurring_orders', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Planting schedule was {$eventName}");
    }
} 