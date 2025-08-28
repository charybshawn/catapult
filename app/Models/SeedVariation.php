<?php

namespace App\Models;

use InvalidArgumentException;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * Represents specific packaging variations of agricultural seed entries,
 * including pricing, weight, availability, and currency conversion capabilities
 * for microgreens seed procurement and cost analysis.
 *
 * @business_domain Agricultural Seed Procurement & Price Management
 * @workflow_context Used in seed sourcing, cost analysis, and inventory planning
 * @agricultural_process Manages different packaging sizes and pricing from suppliers
 *
 * Database Table: seed_variations
 * @property int $id Primary identifier for seed variation
 * @property int $seed_entry_id Reference to parent seed entry
 * @property string|null $size Package size description (e.g., '1 lb', '5 kg')
 * @property string|null $sku Supplier stock keeping unit code
 * @property float|null $weight_kg Normalized weight in kilograms
 * @property float|null $original_weight_value Original weight value from supplier
 * @property string|null $original_weight_unit Original weight unit (lbs, oz, kg, g)
 * @property string|null $unit Supplier's unit description
 * @property float|null $current_price Current price (null for out-of-stock)
 * @property string $currency Price currency (USD, CAD, EUR, etc.)
 * @property bool $is_available Whether this variation is currently available
 * @property Carbon|null $last_checked_at Last price/availability check timestamp
 * @property int|null $consumable_id Link to inventory consumable record
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship seedEntry BelongsTo relationship to SeedEntry for variety context
 * @relationship priceHistory HasMany relationship to SeedPriceHistory for trends
 * @relationship consumable BelongsTo relationship to Consumable inventory item
 *
 * @business_rule Prices must be reasonable ($0.01 to $50,000) or null for out-of-stock
 * @business_rule Weight values must be positive when provided
 * @business_rule Extreme price-per-kg ratios trigger warning logs
 * @business_rule Currency conversion supports multi-currency price analysis
 *
 * @agricultural_procurement Enables cost comparison across package sizes and suppliers
 * @cost_optimization Supports bulk purchasing decisions through price-per-kg analysis
 */
class SeedVariation extends Model
{
    use HasFactory;
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($variation) {
            // Validate price is reasonable (allow null for out-of-stock items)
            if ($variation->current_price !== null && ($variation->current_price <= 0 || $variation->current_price > 50000)) {
                throw new InvalidArgumentException('Price must be between $unit.01 and $50,000, or null for out-of-stock items');
            }
            
            // Validate weight is positive if provided
            if ($variation->weight_kg !== null && $variation->weight_kg <= 0) {
                throw new InvalidArgumentException('Weight must be positive');
            }
            
            // Validate original weight values are consistent
            if ($variation->original_weight_value !== null && $variation->original_weight_value <= 0) {
                throw new InvalidArgumentException('Original weight value must be positive');
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
