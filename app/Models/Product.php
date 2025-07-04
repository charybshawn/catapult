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
use Illuminate\Support\Facades\DB;

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
        'sku',
        'active',
        'image',
        'category_id',
        'is_visible_in_store',
        'product_mix_id',
        'master_seed_catalog_id',
        'total_stock',
        'reserved_stock',
        'reorder_threshold',
        'track_inventory',
        'stock_status',
        'wholesale_discount_percentage',
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
        'total_stock' => 'decimal:2',
        'reserved_stock' => 'decimal:2',
        'reorder_threshold' => 'decimal:2',
        'track_inventory' => 'boolean',
        'wholesale_discount_percentage' => 'decimal:2',
    ];
    
    /**
     * Get the validation rules for the model.
     *
     * @return array<string, mixed>
     */
    public static function rules($id = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:products,name' . ($id ? ',' . $id : '') . ',id,deleted_at,NULL',
            ],
            'description' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku' . ($id ? ',' . $id : '') . ',id,deleted_at,NULL'],
            'active' => ['boolean'],
            'is_visible_in_store' => ['boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_mix_id' => ['nullable', 'exists:product_mixes,id'],
            'master_seed_catalog_id' => ['nullable', 'exists:master_seed_catalogs,id'],
            'image' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'bulk_price' => ['nullable', 'numeric', 'min:0'],
            'special_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
    
    protected static function booted()
    {
        // Validate mutual exclusivity of master_seed_catalog_id and product_mix_id
        static::saving(function ($product) {
            if ($product->master_seed_catalog_id && $product->product_mix_id) {
                throw new \Exception('A product cannot have both a single variety and a product mix assigned.');
            }
            
            // Validate unique product name
            $query = static::where('name', $product->name)
                ->whereNull('deleted_at');
            
            if ($product->exists) {
                $query->where('id', '!=', $product->id);
            }
            
            if ($query->exists()) {
                throw new \Illuminate\Validation\ValidationException(
                    \Illuminate\Support\Facades\Validator::make(
                        ['name' => $product->name],
                        ['name' => 'unique:products,name'],
                        ['name.unique' => 'A product with this name already exists. Please choose a different name.']
                    )
                );
            }
        });

        // Note: Inventory deletion prevention is handled in the UI layer (ProductResource)
        // to provide better user experience with notifications instead of exceptions
        
        // Clean up related records when a product is soft deleted
        // (Hard deletes are handled by database CASCADE DELETE)
        static::deleting(function ($product) {
            // Only clean up if this is a soft delete
            if ($product->isForceDeleting()) {
                // This is a hard delete - let the database CASCADE handle it
                return;
            }
            
            // For soft deletes, we need to manually clean up
            // Delete all inventory entries for this product
            $product->inventories()->delete();
            
            // Delete all price variations for this product
            $product->priceVariations()->delete();
            
            // Delete all inventory transactions
            $product->inventoryTransactions()->delete();
            
            // Delete all inventory reservations
            $product->inventoryReservations()->delete();
            
            // Delete all product photos
            $product->photos()->delete();
        });
        
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
        // Use eager loaded relationship if available
        if ($this->relationLoaded('priceVariations')) {
            return $this->priceVariations->where('is_default', true)->first();
        }
        
        return $this->priceVariations()->where('is_default', true)->first();
    }

    /**
     * Get the active price variations for the product.
     */
    public function activePriceVariations(): Collection
    {
        // Use eager loaded relationship if available
        if ($this->relationLoaded('priceVariations')) {
            return $this->priceVariations->where('is_active', true);
        }
        
        return $this->priceVariations()->where('is_active', true)->get();
    }

    /**
     * Get the price for a given packaging type or default.
     */
    public function getPrice(?int $packagingTypeId = null, float $quantity = 1): float
    {
        // Use eager loaded relationship if available
        if ($this->relationLoaded('priceVariations')) {
            $activeVariations = $this->priceVariations->where('is_active', true);
            
            // Find a price variation that matches the packaging type
            if ($packagingTypeId) {
                $variation = $activeVariations
                    ->where('packaging_type_id', $packagingTypeId)
                    ->sortBy('price')
                    ->first();
            } else {
                $variation = null;
            }

            // If no matching variation found, try to get the default
            if (!$variation) {
                $variation = $activeVariations->where('is_default', true)->first();
            }

            // If still no variation, get the cheapest active one
            if (!$variation) {
                $variation = $activeVariations->sortBy('price')->first();
            }
        } else {
            // Find a price variation that matches the packaging type
            if ($packagingTypeId) {
                $variation = $this->priceVariations()
                    ->where('packaging_type_id', $packagingTypeId)
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->first();
            } else {
                $variation = null;
            }

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
        // Use eager loaded relationship if available
        if ($this->relationLoaded('priceVariations')) {
            return $this->priceVariations
                ->where('name', $name)
                ->where('is_active', true)
                ->first();
        }
        
        return $this->priceVariations()
            ->where('name', $name)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the retail price for a price variation (base price).
     */
    public function getRetailPrice(?int $priceVariationId = null, ?int $packagingTypeId = null): float
    {
        if ($priceVariationId) {
            $variation = $this->priceVariations()->find($priceVariationId);
            return $variation ? $variation->price : 0;
        }
        
        return $this->getPrice($packagingTypeId);
    }

    /**
     * Get the wholesale price for a price variation (with discount applied).
     */
    public function getWholesalePrice(?int $priceVariationId = null, ?int $packagingTypeId = null, ?User $customer = null): float
    {
        $retailPrice = $this->getRetailPrice($priceVariationId, $packagingTypeId);
        
        $discountPercentage = 0;
        
        // Get discount from customer first, then fall back to product default
        if ($customer) {
            $discountPercentage = $customer->getWholesaleDiscountPercentage($this);
        } elseif ($this->wholesale_discount_percentage && $this->wholesale_discount_percentage > 0) {
            $discountPercentage = $this->wholesale_discount_percentage;
        }
        
        if ($discountPercentage <= 0) {
            return $retailPrice;
        }
        
        // Cap discount percentage at 100% to prevent negative prices
        $discountPercentage = min($discountPercentage, 100);
        
        $discountAmount = $retailPrice * ($discountPercentage / 100);
        $wholesalePrice = $retailPrice - $discountAmount;
        
        // Ensure price never goes below zero
        return max($wholesalePrice, 0);
    }

    /**
     * Get price for customer type using new wholesale discount system.
     */
    public function getPriceForCustomer(string $customerType = 'retail', ?int $priceVariationId = null, ?int $packagingTypeId = null, ?User $customer = null): float
    {
        switch (strtolower($customerType)) {
            case 'wholesale':
                return $this->getWholesalePrice($priceVariationId, $packagingTypeId, $customer);
            case 'retail':
            default:
                return $this->getRetailPrice($priceVariationId, $packagingTypeId);
        }
    }

    /**
     * Get price for a specific customer, considering their type and individual discount.
     */
    public function getPriceForSpecificCustomer(User $customer, ?int $priceVariationId = null, ?int $packagingTypeId = null): float
    {
        if ($customer->isWholesaleCustomer()) {
            return $this->getWholesalePrice($priceVariationId, $packagingTypeId, $customer);
        }
        
        return $this->getRetailPrice($priceVariationId, $packagingTypeId);
    }

    /**
     * Get the wholesale discount amount for a given price.
     */
    public function getWholesaleDiscountAmount(float $retailPrice): float
    {
        if (!$this->wholesale_discount_percentage || $this->wholesale_discount_percentage <= 0) {
            return 0;
        }
        
        return $retailPrice * ($this->wholesale_discount_percentage / 100);
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
                'product_mix_id',
                'master_seed_catalog_id',
                'image',
                'base_price',
                'wholesale_price',
                'bulk_price',
                'special_price',
                'wholesale_discount_percentage',
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
            return $defaultPhoto->photo;
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
     * Get the master seed catalog entry for single-variety products.
     */
    public function masterSeedCatalog(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }

    /**
     * Get the varieties associated with this product (either direct or through mix).
     */
    public function getVarietiesAttribute()
    {
        if ($this->master_seed_catalog_id) {
            // Ensure masterSeedCatalog is loaded to avoid lazy loading
            if (!$this->relationLoaded('masterSeedCatalog')) {
                $this->load('masterSeedCatalog');
            }
            // Single variety product
            return collect([$this->masterSeedCatalog]);
        } elseif ($this->product_mix_id) {
            // Ensure productMix and its catalogs are loaded to avoid lazy loading
            if (!$this->relationLoaded('productMix')) {
                $this->load('productMix.masterSeedCatalogs');
            }
            if ($this->productMix) {
                // Mix product - return all varieties in the mix
                return $this->productMix->masterSeedCatalogs;
            }
        }
        
        return collect();
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
    public function createWholesalePriceVariation(?float $price = null)
    {
        return $this->priceVariations()->create([
            'name' => 'Wholesale',
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
    public function createBulkPriceVariation(?float $price = null)
    {
        return $this->priceVariations()->create([
            'name' => 'Bulk',
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
    public function createSpecialPriceVariation(?float $price = null)
    {
        return $this->priceVariations()->create([
            'name' => 'Special',
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
     * @param int|null $packagingTypeId Packaging type ID (optional)
     * @param array $additionalAttributes Additional attributes to set
     * @return \App\Models\PriceVariation
     */
    public function createCustomPriceVariation(string $name, float $price, ?int $packagingTypeId = null, array $additionalAttributes = [])
    {
        $attributes = array_merge([
            'name' => $name,
            'packaging_type_id' => $packagingTypeId,
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

    /**
     * Get the inventory batches for this product.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventory::class);
    }

    /**
     * Get active inventory batches.
     */
    public function activeInventories(): HasMany
    {
        return $this->inventories()->active();
    }

    /**
     * Get available inventory batches (with available quantity).
     */
    public function availableInventories(): HasMany
    {
        return $this->inventories()->available();
    }

    /**
     * Get inventory transactions.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get inventory reservations.
     */
    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    /**
     * Get the available stock attribute.
     */
    public function getAvailableStockAttribute(): float
    {
        return $this->total_stock - $this->reserved_stock;
    }

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->available_stock > 0;
    }

    /**
     * Check if the product needs reordering.
     */
    public function needsReorder(): bool
    {
        return $this->track_inventory && $this->available_stock <= $this->reorder_threshold;
    }

    /**
     * Add inventory to the product.
     */
    public function addInventory(array $data): ProductInventory
    {
        $inventory = $this->inventories()->create([
            'lot_number' => $data['lot_number'] ?? null,
            'quantity' => $data['quantity'],
            'cost_per_unit' => $data['cost_per_unit'] ?? null,
            'price_variation_id' => $data['price_variation_id'] ?? null,
            'expiration_date' => $data['expiration_date'] ?? null,
            'production_date' => $data['production_date'] ?? null,
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'active',
        ]);

        // Record the transaction
        $inventory->recordTransaction(
            type: $data['transaction_type'] ?? 'production',
            quantity: $data['quantity'],
            notes: $data['transaction_notes'] ?? null,
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null
        );

        return $inventory;
    }

    /**
     * Reserve stock for an order using FIFO.
     */
    public function reserveStock(float $quantity, int $orderId, int $orderItemId): array
    {
        if (!$this->track_inventory) {
            return []; // No reservation needed if not tracking inventory
        }

        if ($quantity > $this->available_stock) {
            throw new \Exception("Insufficient stock. Available: {$this->available_stock}, Requested: {$quantity}");
        }

        $reservations = [];
        $remainingQuantity = $quantity;

        // Get available inventory batches ordered by FIFO (expiration date, then creation date)
        $batches = $this->availableInventories()
            ->orderByRaw('COALESCE(expiration_date, DATE_ADD(created_at, INTERVAL 365 DAY))')
            ->orderBy('created_at')
            ->get();

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) break;

            $availableInBatch = $batch->available_quantity;
            $toReserve = min($remainingQuantity, $availableInBatch);

            if ($toReserve > 0) {
                $reservation = $batch->reserveStock($toReserve, $orderId, $orderItemId);
                $reservations[] = $reservation;
                $remainingQuantity -= $toReserve;
            }
        }

        return $reservations;
    }


    /**
     * Get inventory value for this product.
     */
    public function getInventoryValue(): float
    {
        return $this->activeInventories()->sum(\DB::raw('quantity * COALESCE(cost_per_unit, 0)'));
    }

    /**
     * Check if this product can be safely deleted.
     */
    public function canBeDeleted(): array
    {
        $errors = [];
        
        $totalInventory = $this->inventories()
            ->where('quantity', '>', 0)
            ->sum('quantity');
            
        if ($totalInventory > 0) {
            $errors[] = "Product has {$totalInventory} units in inventory. Please reduce inventory to zero first.";
        }
        
        $reservedInventory = $this->inventories()
            ->where('reserved_quantity', '>', 0)
            ->sum('reserved_quantity');
            
        if ($reservedInventory > 0) {
            $errors[] = "Product has {$reservedInventory} reserved units. Please clear reservations first.";
        }
        
        return [
            'canDelete' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Update stock status based on current levels.
     */
    public function updateStockStatus(): void
    {
        if (!$this->track_inventory) {
            $this->update(['stock_status' => 'in_stock']);
            return;
        }

        $available = $this->available_stock;

        if ($available <= 0) {
            $status = 'out_of_stock';
        } elseif ($available <= $this->reorder_threshold) {
            $status = 'low_stock';
        } else {
            $status = 'in_stock';
        }

        $this->update(['stock_status' => $status]);
    }

    /**
     * Check if inventory entries should be updated based on what changed
     */
    public function shouldUpdateInventoryEntries(): bool
    {
        // Update inventory entries if the product was activated/deactivated
        // or if other significant changes were made
        return $this->wasChanged('active') && $this->active;
    }
    
    /**
     * Clone this product with all its relationships
     */
    public function cloneProduct(): Product
    {
        DB::beginTransaction();
        
        try {
            // Clone the main product
            $newProduct = $this->replicate([
                'created_at',
                'updated_at',
                'deleted_at',
                'total_stock',
                'reserved_stock',
                'stock_status',
            ]);
            
            // Add " (Copy)" to the name to make it unique
            $baseName = $this->name . ' (Copy)';
            $counter = 1;
            $newName = $baseName;
            
            // Ensure unique name
            while (Product::where('name', $newName)->whereNull('deleted_at')->exists()) {
                $counter++;
                $newName = $baseName . ' ' . $counter;
            }
            
            $newProduct->name = $newName;
            
            // Also update SKU if it exists
            if ($this->sku) {
                $newProduct->sku = $this->sku . '-copy-' . time();
            }
            
            // Save the new product
            $newProduct->save();
            
            // Clone price variations
            foreach ($this->priceVariations as $variation) {
                $newVariation = $variation->replicate([
                    'created_at',
                    'updated_at',
                ]);
                $newVariation->product_id = $newProduct->id;
                $newVariation->save();
            }
            
            // Clone product photos
            foreach ($this->photos as $photo) {
                $newPhoto = $photo->replicate([
                    'created_at',
                    'updated_at',
                ]);
                $newPhoto->product_id = $newProduct->id;
                $newPhoto->save();
            }
            
            // Do NOT clone inventory entries - new product starts with zero inventory
            // Do NOT clone inventory transactions or reservations
            
            DB::commit();
            
            return $newProduct;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ensure inventory entries exist for all active price variations
     * This method only CREATES missing entries, never modifies existing ones
     */
    public function ensureInventoryEntriesExist(): void
    {
        $activeVariations = $this->priceVariations()
            ->where('is_active', true)
            ->get();

        $createdCount = 0;

        foreach ($activeVariations as $variation) {
            // Check if inventory entry already exists for this variation
            $existingInventory = $this->inventories()
                ->where('price_variation_id', $variation->id)
                ->first();

            if (!$existingInventory) {
                // Create new inventory entry with zero quantity
                $this->inventories()->create([
                    'price_variation_id' => $variation->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'cost_per_unit' => 0,
                    'production_date' => now(),
                    'expiration_date' => null,
                    'location' => null,
                    'status' => 'active',
                    'notes' => "Auto-created for {$variation->name} variation",
                ]);
                $createdCount++;
            }
        }

        // Log if any entries were created (for debugging)
        if ($createdCount > 0) {
            \Log::info("Created {$createdCount} inventory entries for product: {$this->name}");
        }
    }
} 