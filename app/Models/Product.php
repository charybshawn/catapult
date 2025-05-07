<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'items';
    
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
        return $this->hasMany(PriceVariation::class, 'item_id');
    }

    /**
     * Get the default price variation for the product.
     */
    public function defaultPriceVariation()
    {
        return $this->priceVariations()->where('is_default', true)->first();
    }

    /**
     * Get the active price variations for the product.
     */
    public function activePriceVariations()
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
                return $this->wholesale_price ?? $this->base_price ?? 0;
            case 'bulk':
                return $this->bulk_price ?? $this->base_price ?? 0;
            case 'special':
                return $this->special_price ?? $this->base_price ?? 0;
            default:
                return $this->base_price ?? 0;
        }
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
        return $this->hasMany(ProductPhoto::class, 'item_id')->orderBy('order');
    }

    /**
     * Get the default photo for this product.
     */
    public function defaultPhoto()
    {
        return $this->hasOne(ProductPhoto::class, 'item_id')
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
        return $this->defaultPhoto();
    }

    /**
     * Get the product mix associated with this product.
     */
    public function productMix(): BelongsTo
    {
        return $this->belongsTo(ProductMix::class);
    }

    /**
     * Create a default price variation for this product.
     * 
     * @return \App\Models\PriceVariation
     */
    public function createDefaultPriceVariation()
    {
        return $this->priceVariations()->create([
            'name' => 'Default',
            'unit' => 'item',
            'price' => $this->base_price ?? 0,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Get the base price attribute.
     * 
     * @deprecated Use price variations instead
     * @return float|null
     */
    public function getBasePriceAttribute()
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