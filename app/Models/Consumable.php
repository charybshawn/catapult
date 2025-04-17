<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Consumable extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type', // packaging, soil, seed, label, other
        'supplier_id',
        'packaging_type_id', // For packaging consumables only
        'current_stock',
        'unit', // pieces, rolls, bags, etc.
        'restock_threshold',
        'restock_quantity',
        'cost_per_unit',
        'quantity_per_unit', // Weight of each unit
        'quantity_unit', // Unit of measurement (g, kg, l, oz)
        'total_quantity',
        'notes',
        'lot_no',
        'is_active',
        'last_ordered_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_stock' => 'integer',
        'restock_threshold' => 'integer',
        'restock_quantity' => 'integer',
        'cost_per_unit' => 'decimal:2',
        'quantity_per_unit' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'is_active' => 'boolean',
        'last_ordered_at' => 'datetime',
    ];
    
    /**
     * Set the lot number to uppercase.
     */
    public function setLotNoAttribute($value)
    {
        $this->attributes['lot_no'] = $value ? strtoupper($value) : null;
    }
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Consumable $consumable) {
            // If quantity tracking fields are set, calculate the total quantity
            if ($consumable->quantity_per_unit) {
                $consumable->total_quantity = $consumable->current_stock * $consumable->quantity_per_unit;
            }
        });
    }
    
    /**
     * Get the supplier for this consumable.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Get the packaging type for this consumable.
     * Only applicable for packaging consumables.
     */
    public function packagingType(): BelongsTo
    {
        return $this->belongsTo(PackagingType::class);
    }
    
    /**
     * Get the display name for this consumable.
     * For packaging type consumables, includes the packaging type details.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'packaging' && $this->packagingType) {
            return "{$this->name} ({$this->packagingType->display_name})";
        }
        
        return $this->name;
    }
    
    /**
     * Check if the consumable needs to be restocked.
     */
    public function needsRestock(): bool
    {
        // For seeds, check based on total weight instead of unit count
        if ($this->type === 'seed') {
            return $this->needsSeedRestock();
        }
        
        // For all other consumables, check based on unit count
        return $this->current_stock <= $this->restock_threshold;
    }
    
    /**
     * Check if a seed consumable needs restocking based on total weight.
     */
    protected function needsSeedRestock(): bool
    {
        // If quantity tracking is not set up, fall back to unit count
        if (!$this->quantity_per_unit) {
            return $this->current_stock <= $this->restock_threshold;
        }
        
        // Calculate total weight (current_stock * quantity_per_unit)
        $totalWeight = $this->current_stock * $this->quantity_per_unit;
        
        // For seeds, restock_threshold represents the minimum total weight in grams
        return $totalWeight <= $this->restock_threshold;
    }
    
    /**
     * Calculate the total value of the current stock.
     */
    public function totalValue(): float
    {
        return $this->current_stock * ($this->cost_per_unit ?? 0);
    }
    
    /**
     * Deduct quantity from stock.
     */
    public function deduct(int $amount): void
    {
        $newStock = max(0, $this->current_stock - $amount);
        
        $data = [
            'current_stock' => $newStock,
        ];
        
        // Update total quantity if applicable
        if ($this->quantity_per_unit) {
            $data['total_quantity'] = $newStock * $this->quantity_per_unit;
        }
        
        $this->update($data);
    }
    
    /**
     * Add quantity to stock.
     */
    public function add(int $amount): void
    {
        $newStock = $this->current_stock + $amount;
        
        $data = [
            'current_stock' => $newStock,
            'last_ordered_at' => now(),
        ];
        
        // Update total quantity if applicable
        if ($this->quantity_per_unit) {
            $data['total_quantity'] = $newStock * $this->quantity_per_unit;
        }
        
        $this->update($data);
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'type', 
                'supplier_id',
                'packaging_type_id',
                'current_stock',
                'unit',
                'restock_threshold',
                'restock_quantity',
                'cost_per_unit',
                'quantity_per_unit',
                'quantity_unit',
                'total_quantity',
                'is_active',
                'last_ordered_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Get a formatted display of the total weight with unit.
     */
    public function getFormattedTotalWeightAttribute(): string
    {
        // For packaging consumables, return empty string
        if ($this->type === 'packaging') {
            return '';
        }
        
        if (!$this->total_quantity || !$this->quantity_unit) {
            return '-';
        }
        
        return number_format($this->total_quantity, 2) . ' ' . $this->quantity_unit;
    }
    
    /**
     * Get the valid measurement units for quantity.
     */
    public static function getValidMeasurementUnits(): array
    {
        return [
            'g' => 'Grams',
            'kg' => 'Kilograms',
            'l' => 'Liters',
            'oz' => 'Ounces',
        ];
    }
    
    /**
     * Get the valid types for consumables.
     */
    public static function getValidTypes(): array
    {
        return [
            'packaging' => 'Packaging',
            'soil' => 'Soil',
            'seed' => 'Seeds',
            'label' => 'Labels',
            'other' => 'Other',
        ];
    }
    
    /**
     * Get the valid unit types for inventory storage.
     */
    public static function getValidUnitTypes(): array
    {
        return [
            'bag' => 'Bag',
            'packet' => 'Packet',
            'box' => 'Box',
            'bale' => 'Bale',
        ];
    }
}
