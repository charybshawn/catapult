<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;

class Product extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'active',
        'image',
        'category_id',
        'is_visible_in_store',
        'base_price',
        'wholesale_price',
        'bulk_price',
        'special_price',
        'product_mix_id',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'is_visible_in_store' => 'boolean',
        'base_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'bulk_price' => 'decimal:2',
        'special_price' => 'decimal:2',
    ];
    
    protected static function booted()
    {
        // After a product is saved, handle setting the default photo if needed
        static::saved(function ($product) {
            // Find any photo marked as default
            $defaultPhoto = $product->photos()->where('is_default', true)->first();
            
            // If there's a default photo, use the setAsDefault method to ensure only one is default
            if ($defaultPhoto) {
                $defaultPhoto->setAsDefault();
            }
            
            // Create a default price variation if none exists
            if ($product->priceVariations()->count() === 0 && $product->base_price) {
                $product->createDefaultPriceVariation();
            }
            
            // Update the default price variation if base_price was changed
            if ($product->wasChanged('base_price') && $product->base_price) {
                $defaultVariation = $product->priceVariations()->where('is_default', true)->first();
                
                if ($defaultVariation) {
                    $defaultVariation->update(['price' => $product->base_price]);
                } else {
                    // Create default variation if it doesn't exist
                    $product->createDefaultPriceVariation();
                }
            }
            
            // Update wholesale price variation if wholesale_price was changed
            if ($product->wasChanged('wholesale_price') && $product->wholesale_price) {
                $wholesaleVariation = $product->priceVariations()->where('name', 'Wholesale')->first();
                
                if ($wholesaleVariation) {
                    $wholesaleVariation->update(['price' => $product->wholesale_price]);
                } else {
                    // Create wholesale variation if it doesn't exist
                    $product->createWholesalePriceVariation();
                }
            }
            
            // Update bulk price variation if bulk_price was changed
            if ($product->wasChanged('bulk_price') && $product->bulk_price) {
                $bulkVariation = $product->priceVariations()->where('name', 'Bulk')->first();
                
                if ($bulkVariation) {
                    $bulkVariation->update(['price' => $product->bulk_price]);
                } else {
                    // Create bulk variation if it doesn't exist
                    $product->createBulkPriceVariation();
                }
            }
            
            // Update special price variation if special_price was changed
            if ($product->wasChanged('special_price') && $product->special_price) {
                $specialVariation = $product->priceVariations()->where('name', 'Special')->first();
                
                if ($specialVariation) {
                    $specialVariation->update(['price' => $product->special_price]);
                } else {
                    // Create special variation if it doesn't exist
                    $product->createSpecialPriceVariation();
                }
            }
        });
    }
    
    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'item_id');
    }

    /**
     * Get the price variations for the product.
     */
    public function priceVariations(): HasMany
    {
        return $this->hasMany(PriceVariation::class, 'product_id');
    }

    /**
     * Get the default price variation for the product.
     */
    public function defaultPriceVariation(): ?PriceVariation
    {
        return $this->priceVariations()->where('is_default', true)->first();
    }

    /**
     * Get the active price variations for the product.
     */
    public function activePriceVariations(): Collection
    {
        return $this->priceVariations()->where('is_active', true)->get();
    }

    /**
     * Get the price for a given unit and quantity.
     */
    public function getPrice(string $unit = 'item', float $quantity = 1): float
    {
        // Find a price variation that matches the unit
        $variation = $this->priceVariations()
            ->where('unit', $unit)
            ->where('is_active', true)
            ->orderBy('price')
            ->first();

        // If no matching variation found, try to get the default
        if (!$variation) {
            $variation = $this->priceVariations()
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        // If still no variation, get the cheapest active one
        if (!$variation) {
            $variation = $this->priceVariations()
                ->where('is_active', true)
                ->orderBy('price')
                ->first();
        }

        return $variation ? $variation->price : 0;
    }

    /**
     * Get global price variations available for use with any product.
     */
    public static function getGlobalPriceVariations()
    {
        return \App\Models\PriceVariation::where('is_global', true)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get the price based on customer type.
     */
    public function getPriceForCustomerType(string $customerType, int $quantity = 1): float
    {
        switch (strtolower($customerType)) {
            case 'wholesale':
                $variation = $this->getPriceVariationByName('Wholesale');
                return $variation ? $variation->price : ($this->wholesale_price ?? $this->base_price ?? 0);
                
            case 'bulk':
                $variation = $this->getPriceVariationByName('Bulk');
                return $variation ? $variation->price : ($this->bulk_price ?? $this->base_price ?? 0);
                
            case 'special':
                $variation = $this->getPriceVariationByName('Special');
                return $variation ? $variation->price : ($this->special_price ?? $this->base_price ?? 0);
                
            default:
                $variation = $this->defaultPriceVariation();
                return $variation ? $variation->price : ($this->base_price ?? 0);
        }
    }

    /**
     * Get a price variation by name.
     */
    public function getPriceVariationByName(string $name): ?PriceVariation
    {
        return $this->priceVariations()
            ->where('name', $name)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'description',
                'active',
                'is_visible_in_store',
                'category_id',
                'image',
                'base_price',
                'wholesale_price',
                'bulk_price',
                'special_price',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the is_active attribute.
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->active;
    }

    /**
     * Set the is_active attribute.
     *
     * @param bool $value
     * @return void
     */
    public function setIsActiveAttribute(bool $value): void
    {
        $this->attributes['active'] = $value;
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the photos for the product.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class, 'product_id')->orderBy('order');
    }

    /**
     * Get the default photo for this product.
     */
    public function defaultPhoto(): HasOne
    {
        return $this->hasOne(ProductPhoto::class, 'product_id')
            ->where('is_default', true)
            ->withDefault(function () {
                // If no default photo exists, try to get any photo
                $firstPhoto = $this->photos()->first();
                if ($firstPhoto) {
                    // Set it as default
                    $firstPhoto->setAsDefault();
                    return $firstPhoto;
                }
                
                return null;
            });
    }

    /**
     * Get the default photo attribute.
     * This provides a fallback mechanism using the legacy image field
     * if no photos exist.
     */
    public function getDefaultPhotoAttribute()
    {
        $defaultPhoto = $this->defaultPhoto()->first();
        
        if ($defaultPhoto) {
            return $defaultPhoto->photo_path;
        }
        
        // Return the legacy image field or a placeholder
        return $this->image ?? null;
    }

    /**
     * Get the product mix for this product.
     */
    public function productMix(): BelongsTo
    {
        try {
            // Add debug logging when the relationship is accessed
            \Illuminate\Support\Facades\Log::info('Product: productMix relationship accessed', [
                'product_id' => $this->id ?? 'null',
                'product_mix_id' => $this->product_mix_id ?? 'null',
            ]);
            
            return $this->belongsTo(ProductMix::class);
        } catch (\Throwable $e) {
            // Log any errors
            \App\Services\DebugService::logError($e, 'Product::productMix');
            
            // We have to return a relationship, so re-throw after logging
            throw $e;
        }
    }

    /**
     * Create a default price variation for this product.
     * 
     * @param array $attributes Optional attributes to override defaults
     * @return \App\Models\PriceVariation
     */
    public function createDefaultPriceVariation(array $attributes = [])
    {
        $defaultAttributes = [
            'name' => 'Default',
            'unit' => 'item',
            'price' => $this->base_price ?? 0,
            'is_default' => true,
            'is_active' => true,
        ];
        
        return $this->priceVariations()->create(array_merge($defaultAttributes, $attributes));
    }
    
    /**
     * Create a wholesale price variation for this product.
     * 
     * @param float|null $price Optional price to override the default wholesale price
     * @return \App\Models\PriceVariation
     */
    public function createWholesalePriceVariation(float $price = null)
    {
        return $this->priceVariations()->create([
            'name' => 'Wholesale',
            'unit' => 'item',
            'price' => $price ?? $this->wholesale_price ?? $this->base_price ?? 0,
            'is_default' => false,
            'is_active' => true,
        ]);
    }
    
    /**
     * Create a bulk price variation for this product.
     * 
     * @param float|null $price Optional price to override the default bulk price
     * @return \App\Models\PriceVariation
     */
    public function createBulkPriceVariation(float $price = null)
    {
        return $this->priceVariations()->create([
            'name' => 'Bulk',
            'unit' => 'item',
            'price' => $price ?? $this->bulk_price ?? $this->base_price ?? 0,
            'is_default' => false,
            'is_active' => true,
        ]);
    }
    
    /**
     * Create a special price variation for this product.
     * 
     * @param float|null $price Optional price to override the default special price
     * @return \App\Models\PriceVariation
     */
    public function createSpecialPriceVariation(float $price = null)
    {
        return $this->priceVariations()->create([
            'name' => 'Special',
            'unit' => 'item',
            'price' => $price ?? $this->special_price ?? $this->base_price ?? 0,
            'is_default' => false,
            'is_active' => true,
        ]);
    }
    
    /**
     * Create a custom price variation for this product.
     * 
     * @param string $name Name of the price variation
     * @param float $price Price for this variation
     * @param string $unit Unit for this variation (default: 'item')
     * @param array $additionalAttributes Additional attributes to set
     * @return \App\Models\PriceVariation
     */
    public function createCustomPriceVariation(string $name, float $price, string $unit = 'item', array $additionalAttributes = [])
    {
        $attributes = array_merge([
            'name' => $name,
            'unit' => $unit,
            'price' => $price,
            'is_default' => false,
            'is_active' => true,
        ], $additionalAttributes);
        
        return $this->priceVariations()->create($attributes);
    }
    
    /**
     * Create all standard price variations for this product.
     * 
     * @param array $prices Optional array of prices to use
     * @return array Array of created price variations
     */
    public function createAllStandardPriceVariations(array $prices = [])
    {
        $variations = [];
        
        // Get prices from passed array or from model attributes
        $basePrice = $prices['base_price'] ?? $this->attributes['base_price'] ?? 0;
        $wholesalePrice = $prices['wholesale_price'] ?? $this->attributes['wholesale_price'] ?? null;
        $bulkPrice = $prices['bulk_price'] ?? $this->attributes['bulk_price'] ?? null;
        $specialPrice = $prices['special_price'] ?? $this->attributes['special_price'] ?? null;
        
        // Force model attributes to have these values for the create methods
        $this->attributes['base_price'] = $basePrice;
        if ($wholesalePrice) $this->attributes['wholesale_price'] = $wholesalePrice;
        if ($bulkPrice) $this->attributes['bulk_price'] = $bulkPrice;
        if ($specialPrice) $this->attributes['special_price'] = $specialPrice;
        
        // Create default variation (required)
        $variations['default'] = $this->createDefaultPriceVariation();
        
        // Create wholesale variation if wholesale_price is set
        if ($wholesalePrice) {
            $variations['wholesale'] = $this->createWholesalePriceVariation($wholesalePrice);
        }
        
        // Create bulk variation if bulk_price is set
        if ($bulkPrice) {
            $variations['bulk'] = $this->createBulkPriceVariation($bulkPrice);
        }
        
        // Create special variation if special_price is set
        if ($specialPrice) {
            $variations['special'] = $this->createSpecialPriceVariation($specialPrice);
        }
        
        return $variations;
    }

    /**
     * Get the base price attribute.
     * 
     * @deprecated Use price variations instead
     */
    public function getBasePriceAttribute(): ?float
    {
        $variation = $this->defaultPriceVariation();
        if ($variation) {
            return $variation->price;
        }
        
        return $this->attributes['base_price'] ?? null;
    }
    
    /**
     * Get the wholesale price attribute.
     * 
     * @deprecated Use price variations instead
     * @return float|null
     */
    public function getWholesalePriceAttribute()
    {
        $variation = $this->priceVariations()->where('name', 'Wholesale')->first();
        if ($variation) {
            return $variation->price;
        }
        
        return $this->attributes['wholesale_price'] ?? null;
    }
    
    /**
     * Get the bulk price attribute.
     * 
     * @deprecated Use price variations instead
     * @return float|null
     */
    public function getBulkPriceAttribute()
    {
        $variation = $this->priceVariations()->where('name', 'Bulk')->first();
        if ($variation) {
            return $variation->price;
        }
        
        return $this->attributes['bulk_price'] ?? null;
    }
    
    /**
     * Get the special price attribute.
     * 
     * @deprecated Use price variations instead
     * @return float|null
     */
    public function getSpecialPriceAttribute()
    {
        $variation = $this->priceVariations()->where('name', 'Special')->first();
        if ($variation) {
            return $variation->price;
        }
        
        return $this->attributes['special_price'] ?? null;
    }
} 