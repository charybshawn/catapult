<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function seedEntry(): BelongsTo
    {
        return $this->belongsTo(SeedEntry::class);
    }
    
    /**
     * Get the price history records for this variation
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(SeedPriceHistory::class);
    }
    
    /**
     * Get the consumable inventory record associated with this seed variation
     */
    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }
    
    /**
     * Get the price per kg for this variation
     */
    public function getPricePerKgAttribute(): ?float
    {
        if ($this->weight_kg && $this->weight_kg > 0) {
            return $this->current_price / $this->weight_kg;
        }
        return null;
    }
}
