<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inventory extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'seed_variety_id',
        'item_type',
        'name',
        'quantity',
        'unit',
        'restock_threshold',
        'restock_quantity',
        'last_ordered_at',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'float',
        'restock_threshold' => 'float',
        'restock_quantity' => 'float',
        'last_ordered_at' => 'datetime',
    ];
    
    /**
     * Get the supplier for this inventory item.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Get the seed variety for this inventory item.
     */
    public function seedVariety(): BelongsTo
    {
        return $this->belongsTo(SeedVariety::class);
    }
    
    /**
     * Check if the inventory needs restocking.
     */
    public function needsRestock(): bool
    {
        return $this->quantity <= $this->restock_threshold;
    }

    /**
     * Deduct quantity from inventory.
     */
    public function deduct(float $amount): void
    {
        $this->quantity = max(0, $this->quantity - $amount);
        $this->save();
    }

    /**
     * Add quantity to inventory.
     */
    public function add(float $amount): void
    {
        $this->quantity += $amount;
        $this->last_ordered_at = now();
        $this->save();
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'supplier_id', 
                'seed_variety_id', 
                'item_type',
                'name',
                'quantity',
                'unit',
                'restock_threshold',
                'restock_quantity',
                'last_ordered_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
