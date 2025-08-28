<?php

namespace App\Models;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Throwable;
use App\Services\DebugService;
use App\Actions\Product\ValidateProductDeletionAction;
use App\Actions\Product\CloneProductAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use App\Traits\Logging\ExtendedLogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\HasActiveStatus;
use App\Traits\HasCostInformation;
use App\Traits\HasTimestamps;

/**
 * Agricultural Product Model for Catapult Microgreens Management System
 *
 * Represents individual agricultural products in the microgreens catalog, supporting both
 * single-variety seeds and complex seed mixes. Products form the foundation of the
 * agricultural business workflow, linking seed varieties to growing recipes, inventory
 * tracking, and customer pricing structures.
 *
 * @property int $id Primary key identifier
 * @property string $name Product name (unique across active products)
 * @property string|null $description Marketing and agricultural description
 * @property string|null $sku Stock Keeping Unit for inventory tracking
 * @property bool $active Product availability status
 * @property string|null $image Legacy image field (deprecated - use photos relationship)
 * @property int|null $category_id Category classification for product organization
 * @property bool $is_visible_in_store Customer-facing store visibility
 * @property int|null $product_mix_id Foreign key to ProductMix for complex variety blends
 * @property int|null $master_seed_catalog_id Foreign key to single seed variety
 * @property int|null $recipe_id Growing recipe instructions and parameters
 * @property float $total_stock Total inventory quantity across all batches
 * @property float $reserved_stock Inventory reserved for confirmed orders
 * @property float $reorder_threshold Minimum stock level triggering reorder alerts
 * @property bool $track_inventory Enable/disable inventory management
 * @property int|null $stock_status_id Current inventory status (in_stock, low_stock, out_of_stock)
 * @property float|null $wholesale_discount_percentage Default wholesale discount rate
 *
 * @property-read float $available_stock Computed available inventory (total - reserved)
 * @property-read Collection<MasterSeedCatalog> $varieties Seed varieties (single or mix)
 * @property-read ProductPhoto|null $default_photo Primary product image
 *
 * @relationship category BelongsTo Category classification for product organization
 * @relationship productMix BelongsTo ProductMix for complex variety combinations
 * @relationship masterSeedCatalog BelongsTo MasterSeedCatalog for single varieties
 * @relationship recipe BelongsTo Recipe growing instructions and parameters
 * @relationship priceVariations HasMany PriceVariation different packaging/pricing options
 * @relationship inventories HasMany ProductInventory batch tracking and stock management
 * @relationship photos HasMany ProductPhoto product image gallery
 * @relationship orderItems HasMany OrderItem customer order line items
 *
 * @business_rule Products must have either master_seed_catalog_id OR product_mix_id, never both
 * @business_rule Product names must be unique across active (non-deleted) products
 * @business_rule Inventory tracking is optional but affects stock management workflows
 * @business_rule Price variations provide flexible pricing for different customer types
 *
 * @agricultural_context Microgreens products represent either single seed varieties
 * (like Pea Shoots) or complex mixes (like Spicy Mix with multiple varieties).
 * Growing recipes define agricultural parameters like germination time, harvest timing,
 * and yield expectations specific to each product type.
 *
 * @usage_example
 * // Create single variety product
 * $product = Product::create([
 *     'name' => 'Pea Shoots',
 *     'master_seed_catalog_id' => $peaSeedCatalog->id,
 *     'recipe_id' => $peaRecipe->id
 * ]);
 *
 * // Create complex mix product
 * $spicyMix = Product::create([
 *     'name' => 'Spicy Mix',
 *     'product_mix_id' => $spicyMixDefinition->id,
 *     'recipe_id' => $mixRecipe->id
 * ]);
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class Product extends Model
{
    use HasFactory, ExtendedLogsActivity, SoftDeletes, HasActiveStatus, HasCostInformation, HasTimestamps;
    
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
        'recipe_id',
        'total_stock',
        'reserved_stock',
        'reorder_threshold',
        'track_inventory',
        'stock_status_id',
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
     * Get the relationships that should be logged with this model.
     *
     * Defines which related models should be included in activity logging
     * when this product is created, updated, or deleted. Essential for
     * audit trails in agricultural inventory management.
     *
     * @return array<string> Array of relationship method names to log
     */
    public function getLoggedRelationships(): array
    {
        return ['category', 'priceVariations', 'defaultPhoto', 'productMix', 'recipe'];
    }

    /**
     * Get specific attributes to include from related models.
     *
     * Specifies which attributes from related models should be captured
     * in activity logs. Prevents logging of sensitive data while maintaining
     * comprehensive audit trails for agricultural business operations.
     *
     * @return array<string, array<string>> Relationship => [attributes] mapping
     */
    public function getRelationshipAttributesToLog(): array
    {
        return [
            'category' => ['id', 'name', 'slug'],
            'priceVariations' => ['id', 'name', 'price', 'fill_weight', 'pricing_type', 'is_default'],
            'defaultPhoto' => ['id', 'filename', 'url', 'is_default'],
            'productMix' => ['id', 'name', 'description'],
        ];
    }
    
    /**
     * Get the validation rules for the model.
     *
     * Provides comprehensive validation rules for agricultural product data,
     * including business-specific constraints like unique product names and
     * mutual exclusivity of single varieties vs. product mixes.
     *
     * @param int|null $id Product ID for update validation (excludes self from uniqueness)
     * @return array<string, mixed> Laravel validation rules array
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
    
    /**
     * Configure model event listeners for agricultural business logic.
     *
     * Implements critical business rules and automated workflows:
     * - Enforces mutual exclusivity between single varieties and mixes
     * - Validates unique product names across active products
     * - Manages inventory cleanup during soft deletes
     * - Automatically creates/updates price variations based on legacy price fields
     * - Handles default photo assignment logic
     *
     * @return void
     * @throws Exception When business rules are violated
     * @throws ValidationException When validation fails
     */
    protected static function booted()
    {
        // Validate mutual exclusivity of master_seed_catalog_id and product_mix_id
        static::saving(function ($product) {
            if ($product->master_seed_catalog_id && $product->product_mix_id) {
                throw new Exception('A product cannot have both a single variety and a product mix assigned.');
            }
            
            // Validate unique product name
            $query = static::where('name', $product->name)
                ->whereNull('deleted_at');
            
            if ($product->exists) {
                $query->where('id', '!=', $product->id);
            }
            
            if ($query->exists()) {
                throw new ValidationException(
                    Validator::make(
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
     *
     * Relationship to customer order line items that reference this product.
     * Essential for tracking product demand, sales history, and agricultural
     * planning based on order patterns.
     *
     * @return HasMany<OrderItem> Customer order line items
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'item_id');
    }

    /**
     * Get the price variations for the product.
     *
     * Relationship to different pricing structures (retail, wholesale, bulk)
     * and packaging options. Supports flexible agricultural pricing based on
     * customer type, quantity, and packaging requirements.
     *
     * @return HasMany<PriceVariation> Pricing and packaging variations
     */
    public function priceVariations(): HasMany
    {
        return $this->hasMany(PriceVariation::class, 'product_id');
    }

    /**
     * Get the default price variation for the product.
     *
     * Retrieves the primary pricing option, typically retail pricing.
     * Uses eager loading when available to prevent N+1 query issues
     * in agricultural order processing workflows.
     *
     * @return PriceVariation|null Default pricing variation or null if none exists
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
     *
     * Retrieves all currently available pricing options, filtering out
     * disabled variations. Optimized for performance with eager loading
     * support for high-volume agricultural order processing.
     *
     * @return Collection<PriceVariation> Collection of active price variations
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
     *
     * Agricultural pricing logic with fallback hierarchy:
     * 1. Match specific packaging type (e.g., 4oz container vs 1lb bag)
     * 2. Use default price variation if no packaging match
     * 3. Use cheapest active variation as final fallback
     *
     * @param int|null $packagingTypeId Specific packaging type ID for pricing
     * @param float $quantity Order quantity (reserved for future quantity-based pricing)
     * @return float Product price for the specified packaging or default
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
     *
     * Retrieves system-wide pricing templates that can be applied to any
     * agricultural product. Used for standardizing pricing structures
     * across the entire microgreens catalog.
     *
     * @return Collection<PriceVariation> Global price variation templates
     */
    public static function getGlobalPriceVariations()
    {
        return PriceVariation::where('is_global', true)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get the price based on customer type.
     *
     * Agricultural business pricing logic supporting different customer segments:
     * - Retail customers: Standard pricing
     * - Wholesale customers: Discounted pricing based on customer type
     * - Bulk customers: Volume-based pricing
     * - Special customers: Custom pricing arrangements
     *
     * @param string $customerType Customer type code (retail, wholesale, bulk, special)
     * @param int $quantity Order quantity for volume-based calculations
     * @return float Appropriate price for the customer type
     */
    public function getPriceForCustomerType(string $customerType, int $quantity = 1): float
    {
        // Handle lookup by customer type code
        $customerTypeModel = CustomerType::findByCode(strtolower($customerType));
        
        if ($customerTypeModel?->qualifiesForWholesalePricing()) {
            $variation = $this->getPriceVariationByName('Wholesale');
            return $variation ? $variation->price : ($this->wholesale_price ?? $this->base_price ?? 0);
        }
        
        switch (strtolower($customerType)) {
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
     *
     * Searches for a specific pricing variation by name (e.g., 'Wholesale', 'Bulk').
     * Uses eager loading optimization when relationship is already loaded
     * to prevent additional database queries in agricultural order workflows.
     *
     * @param string $name Price variation name to search for
     * @return PriceVariation|null Matching price variation or null if not found
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
     *
     * Retrieves standard retail pricing for agricultural products, supporting
     * specific price variation selection or packaging-based pricing.
     * Used as the foundation for wholesale discount calculations.
     *
     * @param int|null $priceVariationId Specific price variation to use
     * @param int|null $packagingTypeId Packaging type for pricing lookup
     * @return float Retail price for the specified variation or default
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
     *
     * Calculates wholesale pricing for agricultural products with discount hierarchy:
     * 1. Customer-specific wholesale discount percentage
     * 2. Product default wholesale discount percentage
     * 3. No discount (retail price) if no wholesale rates configured
     *
     * Includes safeguards preventing negative prices from excessive discounts.
     *
     * @param int|null $priceVariationId Specific price variation to discount
     * @param int|null $packagingTypeId Packaging type for base price calculation
     * @param Customer|null $customer Customer for individual discount rates
     * @return float Wholesale price with appropriate discounts applied
     */
    public function getWholesalePrice(?int $priceVariationId = null, ?int $packagingTypeId = null, ?Customer $customer = null): float
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
     *
     * Unified pricing method supporting the agricultural business model with
     * customer type classification and individual discount structures.
     * Automatically applies appropriate pricing based on customer classification.
     *
     * @param string $customerType Customer type code (defaults to 'retail')
     * @param int|null $priceVariationId Specific price variation selection
     * @param int|null $packagingTypeId Packaging type for pricing
     * @param User|null $customer Individual customer for personalized pricing
     * @return float Final price with customer-appropriate discounts
     */
    public function getPriceForCustomer(string $customerType = 'retail', ?int $priceVariationId = null, ?int $packagingTypeId = null, ?User $customer = null): float
    {
        // Handle lookup by customer type code
        $customerTypeModel = CustomerType::findByCode(strtolower($customerType));
        
        if ($customerTypeModel?->qualifiesForWholesalePricing()) {
            return $this->getWholesalePrice($priceVariationId, $packagingTypeId, $customer);
        }
        
        return $this->getRetailPrice($priceVariationId, $packagingTypeId);
    }

    /**
     * Get price for a specific customer, considering their type and individual discount.
     *
     * Customer-specific pricing method that considers both customer type
     * classification and individual discount agreements. Essential for
     * agricultural B2B relationships with custom pricing arrangements.
     *
     * @param Customer $customer Customer model with type and discount information
     * @param int|null $priceVariationId Specific price variation to use
     * @param int|null $packagingTypeId Packaging type for pricing calculations
     * @return float Personalized price based on customer relationship
     */
    public function getPriceForSpecificCustomer(Customer $customer, ?int $priceVariationId = null, ?int $packagingTypeId = null): float
    {
        if ($customer->isWholesaleCustomer()) {
            return $this->getWholesalePrice($priceVariationId, $packagingTypeId, $customer);
        }
        
        return $this->getRetailPrice($priceVariationId, $packagingTypeId);
    }

    /**
     * Get the wholesale discount amount for a given price.
     *
     * Calculates the monetary discount amount based on product's wholesale
     * discount percentage. Used for pricing transparency and financial reporting
     * in agricultural wholesale operations.
     *
     * @param float $retailPrice Base retail price for discount calculation
     * @return float Dollar amount of wholesale discount
     */
    public function getWholesaleDiscountAmount(float $retailPrice): float
    {
        if (!$this->wholesale_discount_percentage || $this->wholesale_discount_percentage <= 0) {
            return 0;
        }
        
        return $retailPrice * ($this->wholesale_discount_percentage / 100);
    }

    /**
     * Override the active field name from HasActiveStatus trait.
     *
     * Product model uses 'active' instead of 'is_active' for historical
     * compatibility with existing agricultural product data. This override
     * ensures the HasActiveStatus trait works correctly with our schema.
     *
     * @return string Field name for active status ('active')
     */
    public function getActiveFieldName(): string
    {
        return 'active';
    }

    /**
     * Configure the activity log options for this model.
     *
     * Defines comprehensive activity logging for agricultural product changes.
     * Tracks all significant product modifications for audit trails, regulatory
     * compliance, and agricultural business intelligence.
     *
     * @return LogOptions Configured activity logging options
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
     * Provides compatibility accessor for the is_active attribute by mapping
     * to the actual 'active' field. Ensures consistent API across different
     * trait implementations in the agricultural management system.
     *
     * @return bool Product active status
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->active;
    }

    /**
     * Set the is_active attribute.
     *
     * Provides compatibility mutator for the is_active attribute by mapping
     * to the actual 'active' field. Maintains API consistency while using
     * the agricultural product schema's 'active' column.
     *
     * @param bool $value New active status value
     * @return void
     */
    public function setIsActiveAttribute(bool $value): void
    {
        $this->attributes['active'] = $value;
    }

    /**
     * Get the category that owns the product.
     *
     * Relationship to product categorization system for agricultural inventory
     * organization. Categories help group related products (e.g., 'Microgreens',
     * 'Herbs', 'Sprouts') for better catalog management and customer browsing.
     *
     * @return BelongsTo<Category> Product category relationship
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the photos for the product.
     *
     * Relationship to product image gallery supporting multiple photos per product.
     * Images are ordered by display order for consistent presentation in
     * agricultural product catalogs and customer-facing interfaces.
     *
     * @return HasMany<ProductPhoto> Product photo gallery
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class, 'product_id')->orderBy('order');
    }

    /**
     * Get the default photo for this product.
     *
     * Relationship to the primary product image with automatic fallback logic.
     * If no photo is marked as default, automatically promotes the first available
     * photo to default status. Essential for consistent product presentation.
     *
     * @return HasOne<ProductPhoto> Default product photo with fallback behavior
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
     *
     * Accessor providing fallback mechanism for product images:
     * 1. Use default photo from photos relationship
     * 2. Fall back to legacy image field for backwards compatibility
     * 3. Return null if no images available
     *
     * Supports migration from legacy image system to new photo gallery.
     *
     * @return string|null Default photo URL or null if no images
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
     *
     * Relationship to ProductMix for complex agricultural products containing
     * multiple seed varieties with specific percentage blends (e.g., Spicy Mix
     * with 40% Radish, 30% Mustard, 30% Arugula).
     *
     * Includes debug logging for troubleshooting mix-related issues in
     * agricultural variety calculations.
     *
     * @return BelongsTo<ProductMix> Product mix definition with variety percentages
     * @throws Throwable When relationship loading fails (logged for debugging)
     */
    public function productMix(): BelongsTo
    {
        try {
            // Add debug logging when the relationship is accessed
            Log::info('Product: productMix relationship accessed', [
                'product_id' => $this->id ?? 'null',
                'product_mix_id' => $this->product_mix_id ?? 'null',
            ]);
            
            return $this->belongsTo(ProductMix::class);
        } catch (Throwable $e) {
            // Log any errors
            DebugService::logError($e, 'Product::productMix');
            
            // We have to return a relationship, so re-throw after logging
            throw $e;
        }
    }

    /**
     * Get the master seed catalog entry for single-variety products.
     *
     * Relationship to the seed catalog for products containing only one variety.
     * Master seed catalog entries contain agricultural data like germination rates,
     * growing parameters, and supplier information essential for agricultural planning.
     *
     * @return BelongsTo<MasterSeedCatalog> Single seed variety information
     */
    public function masterSeedCatalog(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }

    /**
     * Get the recipe for this product.
     *
     * Relationship to growing instructions and agricultural parameters specific
     * to this product. Recipes define growing stages, watering schedules,
     * environmental conditions, and harvest timing for successful production.
     *
     * @return BelongsTo<Recipe> Growing recipe and agricultural parameters
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the stock status for this product.
     *
     * Relationship to inventory status lookup (in_stock, low_stock, out_of_stock).
     * Automatically updated based on inventory levels and reorder thresholds
     * to support agricultural inventory management and customer communication.
     *
     * @return BelongsTo<ProductStockStatus> Current inventory status
     */
    public function stockStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStockStatus::class, 'stock_status_id');
    }

    /**
     * Get the varieties associated with this product (either direct or through mix).
     *
     * Unified accessor for seed varieties regardless of product structure:
     * - Single variety products: Returns collection with one MasterSeedCatalog
     * - Mix products: Returns collection of all varieties in the mix
     * - Handles eager loading optimization to prevent N+1 queries
     *
     * Essential for agricultural calculations like seed quantity requirements,
     * growing space allocation, and variety-specific growing parameters.
     *
     * @return Collection<MasterSeedCatalog> All seed varieties for this product
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
     * Creates the primary pricing entry for new agricultural products.
     * Default variations serve as the foundation for retail pricing and
     * the basis for wholesale discount calculations.
     *
     * @param array $attributes Optional attributes to override defaults
     * @return PriceVariation Created default price variation
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
     * Creates wholesale pricing tier for agricultural B2B customers.
     * Uses product's wholesale_price field or falls back to base_price
     * if no wholesale pricing is configured.
     *
     * @param float|null $price Optional price to override the default wholesale price
     * @return PriceVariation Created wholesale price variation
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
     * Creates volume-based pricing for large agricultural orders.
     * Typically used for restaurant chains or food service customers
     * requiring significant quantities of microgreens.
     *
     * @param float|null $price Optional price to override the default bulk price
     * @return PriceVariation Created bulk price variation
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
     * Creates custom pricing tier for special circumstances like promotional
     * pricing, contract rates, or seasonal adjustments in agricultural markets.
     *
     * @param float|null $price Optional price to override the default special price
     * @return PriceVariation Created special price variation
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
     * Creates flexible pricing variations for specific agricultural business needs.
     * Supports packaging-specific pricing (e.g., different prices for 2oz vs 4oz containers)
     * and custom attributes for specialized pricing structures.
     *
     * @param string $name Name of the price variation
     * @param float $price Price for this variation
     * @param int|null $packagingTypeId Packaging type ID for package-specific pricing
     * @param array $additionalAttributes Additional attributes to set
     * @return PriceVariation Created custom price variation
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
     * Bulk creation method for setting up complete pricing structure
     * for new agricultural products. Creates default, wholesale, bulk,
     * and special variations based on provided prices or model attributes.
     *
     * @param array $prices Optional array of prices to use (overrides model attributes)
     * @return array<string, PriceVariation> Array of created price variations keyed by type
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
     * Backwards compatibility accessor for legacy pricing fields.
     * Retrieves price from default price variation if available,
     * otherwise returns the legacy base_price attribute.
     *
     * @deprecated Use price variations instead for new development
     * @return float|null Base price from default variation or legacy field
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
     * Backwards compatibility accessor for legacy wholesale pricing.
     * Retrieves price from wholesale price variation if available,
     * otherwise returns the legacy wholesale_price attribute.
     *
     * @deprecated Use price variations instead for new development
     * @return float|null Wholesale price from variation or legacy field
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
     * Backwards compatibility accessor for legacy bulk pricing.
     * Retrieves price from bulk price variation if available,
     * otherwise returns the legacy bulk_price attribute.
     *
     * @deprecated Use price variations instead for new development
     * @return float|null Bulk price from variation or legacy field
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
     * Backwards compatibility accessor for legacy special pricing.
     * Retrieves price from special price variation if available,
     * otherwise returns the legacy special_price attribute.
     *
     * @deprecated Use price variations instead for new development
     * @return float|null Special price from variation or legacy field
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
     *
     * Relationship to inventory batch tracking system supporting lot-based
     * inventory management for agricultural products. Each batch tracks
     * production dates, expiration dates, and quantity for food safety compliance.
     *
     * @return HasMany<ProductInventory> Inventory batches for this product
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventory::class);
    }

    /**
     * Get active inventory batches.
     *
     * Filtered relationship to inventory batches with 'active' status.
     * Used for agricultural inventory operations excluding expired,
     * damaged, or otherwise unavailable inventory batches.
     *
     * @return HasMany<ProductInventory> Active inventory batches only
     */
    public function activeInventories(): HasMany
    {
        return $this->inventories()->active();
    }

    /**
     * Get available inventory batches (with available quantity).
     *
     * Filtered relationship to inventory batches with unreserved quantity
     * available for new agricultural orders. Excludes fully reserved batches
     * and considers quantity > reserved_quantity.
     *
     * @return HasMany<ProductInventory> Inventory batches with available stock
     */
    public function availableInventories(): HasMany
    {
        return $this->inventories()->available();
    }

    /**
     * Get inventory transactions.
     *
     * Relationship to all inventory movement records for this product.
     * Tracks production, sales, adjustments, and other inventory changes
     * essential for agricultural inventory auditing and financial reporting.
     *
     * @return HasMany<InventoryTransaction> All inventory movement records
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get inventory reservations.
     *
     * Relationship to stock reservations for confirmed agricultural orders.
     * Reservations prevent overselling by temporarily allocating inventory
     * to specific orders before fulfillment.
     *
     * @return HasMany<InventoryReservation> Stock reservations for orders
     */
    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    /**
     * Get the available stock attribute.
     *
     * Computed attribute calculating unreserved inventory available for new orders.
     * Critical for preventing overselling in agricultural order management
     * and providing accurate stock information to customers.
     *
     * @return float Available quantity (total_stock - reserved_stock)
     */
    public function getAvailableStockAttribute(): float
    {
        return $this->total_stock - $this->reserved_stock;
    }

    /**
     * Check if the product is in stock.
     *
     * Boolean check for product availability in agricultural inventory.
     * Returns true if any unreserved stock exists, false if completely
     * out of stock or fully reserved.
     *
     * @return bool True if available stock > 0
     */
    public function isInStock(): bool
    {
        return $this->available_stock > 0;
    }

    /**
     * Check if the product needs reordering.
     *
     * Determines if agricultural product stock has fallen below the
     * configured reorder threshold. Only applies to products with
     * inventory tracking enabled.
     *
     * @return bool True if inventory tracking is enabled and available stock <= reorder threshold
     */
    public function needsReorder(): bool
    {
        return $this->track_inventory && $this->available_stock <= $this->reorder_threshold;
    }

    /**
     * Add inventory to the product.
     *
     * Creates new inventory batch for agricultural product with full transaction
     * logging. Supports lot numbers, expiration dates, and cost tracking
     * required for food safety compliance and agricultural inventory management.
     *
     * @param array $data Inventory batch data including quantity, lot_number, expiration_date
     * @return ProductInventory Created inventory batch with transaction record
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
     *
     * Implements First-In-First-Out inventory reservation for agricultural products
     * to ensure proper rotation and minimize waste from expiration. Automatically
     * spreads reservations across multiple batches if needed.
     *
     * FIFO Logic:
     * 1. Sort batches by expiration date (earliest first)
     * 2. Then by creation date for equal expiration dates
     * 3. Reserve from oldest inventory first
     *
     * @param float $quantity Total quantity to reserve
     * @param int $orderId Order requiring the reservation
     * @param int $orderItemId Specific order line item
     * @return array<InventoryReservation> Array of created reservations
     * @throws Exception When insufficient stock available
     */
    public function reserveStock(float $quantity, int $orderId, int $orderItemId): array
    {
        if (!$this->track_inventory) {
            return []; // No reservation needed if not tracking inventory
        }

        if ($quantity > $this->available_stock) {
            throw new Exception("Insufficient stock. Available: {$this->available_stock}, Requested: {$quantity}");
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
     *
     * Calculates total monetary value of all active inventory based on
     * cost per unit. Essential for agricultural financial reporting,
     * insurance valuations, and asset management.
     *
     * @return float Total inventory value (sum of quantity * cost_per_unit)
     */
    public function getInventoryValue(): float
    {
        return $this->activeInventories()->sum(\DB::raw('quantity * COALESCE(cost_per_unit, 0)'));
    }

    /**
     * Check if this product can be safely deleted.
     *
     * Validates whether agricultural product can be safely removed from the system
     * without breaking referential integrity. Checks for existing orders,
     * inventory transactions, and other dependent records.
     *
     * @return array Validation result with 'can_delete' boolean and 'reasons' array
     */
    public function canBeDeleted(): array
    {
        return app(ValidateProductDeletionAction::class)->execute($this);
    }

    /**
     * Update stock status based on current levels.
     *
     * Automatically updates product stock status based on current inventory levels
     * and configured reorder thresholds. Status transitions:
     * - out_of_stock: available_stock <= 0
     * - low_stock: available_stock <= reorder_threshold
     * - in_stock: available_stock > reorder_threshold
     *
     * Products not tracking inventory default to 'in_stock' status.
     *
     * @return void
     */
    public function updateStockStatus(): void
    {
        if (!$this->track_inventory) {
            $inStockStatus = ProductStockStatus::findByCode('in_stock');
            $this->update(['stock_status_id' => $inStockStatus?->id]);
            return;
        }

        $available = $this->available_stock;

        if ($available <= 0) {
            $statusCode = 'out_of_stock';
        } elseif ($available <= $this->reorder_threshold) {
            $statusCode = 'low_stock';
        } else {
            $statusCode = 'in_stock';
        }

        $status = ProductStockStatus::findByCode($statusCode);
        if ($status) {
            $this->update(['stock_status_id' => $status->id]);
        }
    }

    /**
     * Check if inventory entries should be updated based on what changed.
     *
     * Determines if inventory entries need updating based on product changes.
     * Currently triggers on product activation to ensure activated products
     * have proper inventory structures in place.
     *
     * @return bool True if inventory entries should be updated
     */
    public function shouldUpdateInventoryEntries(): bool
    {
        // Update inventory entries if the product was activated/deactivated
        // or if other significant changes were made
        return $this->wasChanged('active') && $this->active;
    }
    
    /**
     * Clone this product with all its relationships.
     *
     * Creates a complete copy of agricultural product including price variations,
     * photos, and related data. Useful for creating product variants or
     * seasonal product duplications. Delegates to CloneProductAction for
     * complex cloning logic.
     *
     * @return Product Newly created product clone
     */
    public function cloneProduct(): Product
    {
        return app(CloneProductAction::class)->execute($this);
    }

    /**
     * Ensure inventory entries exist for all active price variations.
     *
     * Creates missing inventory batch entries for active price variations to
     * maintain proper inventory structure. This method only CREATES missing entries,
     * never modifies existing ones to preserve historical inventory data.
     *
     * Each created entry starts with zero quantity and requires manual inventory
     * additions to reflect actual stock levels. Essential for maintaining
     * consistent inventory tracking across all pricing variations.
     *
     * @return void
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
            Log::info("Created {$createdCount} inventory entries for product: {$this->name}");
        }
    }
} 