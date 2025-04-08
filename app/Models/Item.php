<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    
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
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'is_visible_in_store' => 'boolean',
    ];
    
    protected static function booted()
    {
        // After an item is saved, handle setting the default photo if needed
        static::saved(function ($item) {
            // Find any photo marked as default
            $defaultPhoto = $item->photos()->where('is_default', true)->first();
            
            // If there's a default photo, use the setAsDefault method to ensure only one is default
            if ($defaultPhoto) {
                $defaultPhoto->setAsDefault();
            }
        });
    }
    
    /**
     * Get the order items for this item.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the price variations for the item.
     */
    public function priceVariations(): HasMany
    {
        return $this->hasMany(PriceVariation::class);
    }

    /**
     * Get the default price variation for the item.
     */
    public function defaultPriceVariation()
    {
        return $this->priceVariations()->where('is_default', true)->first();
    }

    /**
     * Get the active price variations for the item.
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
     * @deprecated Use getPrice() instead
     */
    public function getPriceForCustomerType(string $customerType, int $quantity = 1): float
    {
        return $this->getPrice();
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
     * Get the category that owns the item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the photos for the item.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ItemPhoto::class)->orderBy('order');
    }

    /**
     * Get the default photo for this item.
     */
    public function defaultPhoto()
    {
        return $this->hasOne(ItemPhoto::class)
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
        // Get the default photo relationship
        $defaultPhoto = $this->defaultPhoto()->first();
        
        if ($defaultPhoto) {
            return $defaultPhoto->photo;
        }
        
        return $this->image;
    }
}
