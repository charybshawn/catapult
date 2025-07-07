<?php

namespace App\Models;

use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class SeedVariation extends Model
{
    use HasFactory;
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($variation) {
            // Validate price is reasonable (allow null for out-of-stock items)
            if ($variation->current_price !== null && ($variation->current_price <= 0 || $variation->current_price > 50000)) {
                throw new \InvalidArgumentException('Price must be between $unit.01 and $50,000, or null for out-of-stock items');
            }
            
            // Validate weight is positive if provided
            if ($variation->weight_kg !== null && $variation->weight_kg <= 0) {
                throw new \InvalidArgumentException('Weight must be positive');
            }
            
            // Validate original weight values are consistent
            if ($variation->original_weight_value !== null && $variation->original_weight_value <= 0) {
                throw new \InvalidArgumentException('Original weight value must be positive');
            }
            
            // Log extreme price/kg ratios for review (only if price is available)
            if ($variation->weight_kg && $variation->weight_kg > 0 && $variation->current_price !== null) {
                $pricePerKg = $variation->current_price / $variation->weight_kg;
                if ($pricePerKg > 5000) {
                    Log::warning('Extremely high price per kg detected', [
                        'variation_id' => $variation->id,
                        'price_per_kg' => $pricePerKg,
                        'price' => $variation->current_price,
                        'weight_kg' => $variation->weight_kg,
                        'size' => $variation->size
                    ]);
                }
                if ($pricePerKg < 1) {
                    Log::warning('Extremely low price per kg detected', [
                        'variation_id' => $variation->id,
                        'price_per_kg' => $pricePerKg,
                        'price' => $variation->current_price,
                        'weight_kg' => $variation->weight_kg,
                        'size' => $variation->size
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        'seed_entry_id', 
        'size', 
        'sku', 
        'weight_kg',
        'original_weight_value', 
        'original_weight_unit',
        'unit',
        'current_price',
        'currency',
        'is_available', 
        'last_checked_at',
        'consumable_id'
    ];
    
    protected $casts = [
        'weight_kg' => 'decimal:4',
        'current_price' => 'decimal:2',
        'is_available' => 'boolean',
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
        if ($this->current_price === null) {
            return null;
        }
        
        if ($this->weight_kg && $this->weight_kg > 0) {
            return $this->current_price / $this->weight_kg;
        }
        return null;
    }
    
    /**
     * Get price converted to CAD
     */
    public function getPriceInCadAttribute(): ?float
    {
        if ($this->current_price === null) {
            return null;
        }
        
        $conversionService = app(CurrencyConversionService::class);
        return $conversionService->convertToCad($this->current_price, $this->currency);
    }
    
    /**
     * Get price converted to USD
     */
    public function getPriceInUsdAttribute(): ?float
    {
        if ($this->current_price === null) {
            return null;
        }
        
        $conversionService = app(CurrencyConversionService::class);
        return $conversionService->convertToUsd($this->current_price, $this->currency);
    }
    
    /**
     * Get price per kg in CAD
     */
    public function getPricePerKgInCadAttribute(): ?float
    {
        if ($this->weight_kg && $this->weight_kg > 0) {
            return $this->price_in_cad / $this->weight_kg;
        }
        return null;
    }
    
    /**
     * Get price per kg in USD
     */
    public function getPricePerKgInUsdAttribute(): ?float
    {
        if ($this->weight_kg && $this->weight_kg > 0) {
            return $this->price_in_usd / $this->weight_kg;
        }
        return null;
    }
    
    /**
     * Get formatted price with conversion info
     */
    public function getFormattedPriceWithConversion(string $displayCurrency = 'CAD'): string
    {
        // Handle null prices (out of stock items)
        if ($this->current_price === null) {
            return 'Out of Stock';
        }
        
        $conversionService = app(CurrencyConversionService::class);
        return $conversionService->getFormattedConversion(
            $this->current_price, 
            $this->currency, 
            $displayCurrency
        );
    }
    
    /**
     * Convert weight from pounds to kg if needed
     */
    public function getWeightInKgAttribute(): float
    {
        // If original weight unit is pounds, convert to kg
        if ($this->original_weight_unit && strtolower($this->original_weight_unit) === 'lbs') {
            return $this->original_weight_value * 0.453592; // Convert lbs to kg
        }
        
        // If original weight unit is ounces, convert to kg
        if ($this->original_weight_unit && strtolower($this->original_weight_unit) === 'oz') {
            return $this->original_weight_value * 0.0283495; // Convert oz to kg
        }
        
        // Return stored weight_kg or calculate from original values
        return $this->weight_kg ?? $this->original_weight_value ?? 0;
    }
}
