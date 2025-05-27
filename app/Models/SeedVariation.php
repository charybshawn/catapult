<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeedVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'seed_entry_id', 
        'size_description', 
        'sku', 
        'weight_kg',
        'original_weight_value', 
        'original_weight_unit',
        'current_price',
        'currency',
        'is_in_stock', 
        'last_checked_at',
        'consumable_id'
    ];
    
    protected $casts = [
        'weight_kg' => 'decimal:4',
        'current_price' => 'decimal:2',
        'is_in_stock' => 'boolean',
        'last_checked_at' => 'datetime',
    ];
    
    /**
     * Get the seed entry that this variation belongs to
     */
    public function seedEntry()
    {
        return $this->belongsTo(SeedEntry::class);
    }
    
    /**
     * Get the price history records for this variation
     */
    public function priceHistory()
    {
        return $this->hasMany(SeedPriceHistory::class);
    }
    
    /**
     * Get the consumable inventory record associated with this seed variation
     */
    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }
    
    /**
     * Get the price per kg for this variation
     */
    public function getPricePerKgAttribute()
    {
        if ($this->weight_kg && $this->weight_kg > 0) {
            return $this->current_price / $this->weight_kg;
        }
        return null;
    }
}
