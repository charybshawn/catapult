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
    ];
    
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
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'harvest_date', 'delivery_date', 'status', 'customer_type', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Order was {$eventName}");
    }
}
