<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * PriceVariation Model for Agricultural Product Pricing Structures
 * 
 * Manages complex pricing variations for microgreens products across different
 * customer types (retail, wholesale, bulk), packaging options, and weight/volume
 * configurations. Supports both product-specific pricing and global pricing templates
 * for consistent pricing strategies across the agricultural business.
 * 
 * This model handles:
 * - Multi-tier pricing (retail, wholesale, bulk) for different customer segments
 * - Packaging-based pricing with container-specific fill weights and costs
 * - Weight-based pricing with automatic unit conversions (grams, pounds, ounces)
 * - Global pricing templates for consistent application across products
 * - Inventory integration with automatic stock tracking per variation
 * - SKU management and product identification
 * 
 * Business Context:
 * Agricultural products require flexible pricing strategies to serve diverse markets:
 * - Restaurants need wholesale pricing for bulk orders
 * - Retail customers pay premium prices for small packaged quantities
 * - Specialty packaging (clamshells, bags) affects pricing due to container costs
 * - Weight-based pricing accommodates customers wanting specific quantities
 * - Seasonal pricing adjustments based on growing costs and market demand
 * 
 * Pricing variations directly affect:
 * - Order profitability and margin calculations
 * - Inventory valuation and cost tracking
 * - Customer pricing consistency and transparency
 * - Production planning based on margin priorities
 * 
 * @property int $id Primary key
 * @property int|null $product_id Product being priced (null for global templates)
 * @property int|null $template_id Template used to create this variation
 * @property int|null $packaging_type_id Container/packaging type for this variation
 * @property string $pricing_type Customer pricing tier (retail, wholesale, bulk, special, custom)
 * @property string $name Variation display name (auto-generated or manual)
 * @property bool $is_name_manual Whether name was manually set or auto-generated
 * @property string|null $unit Measurement unit for pricing (deprecated)
 * @property string|null $sku Stock Keeping Unit identifier
 * @property float|null $fill_weight_grams Product weight in packaging (grams)
 * @property float $price Unit price for this variation
 * @property string $pricing_unit Unit basis for pricing (per_lb, per_kg, each, etc.)
 * @property bool $is_default Whether this is the default variation for product
 * @property bool $is_global Whether this is a reusable pricing template
 * @property bool $is_active Whether variation is available for orders
 * @property \Carbon\Carbon $created_at Variation creation timestamp
 * @property \Carbon\Carbon $updated_at Last variation modification
 * 
 * @relationship product BelongsTo relationship to Product being priced (null for templates)
 * @relationship packagingType BelongsTo relationship to PackagingType container
 * @relationship template BelongsTo relationship to template PriceVariation
 * 
 * @business_rules
 * - Each product must have exactly one default variation (is_default = true)
 * - Global templates cannot be default variations (is_global = true, is_default = false)
 * - Global templates have product_id = null for reusability
 * - Price variations with inventory cannot be deleted (safety constraint)
 * - Active variations automatically create inventory tracking entries
 * - Fill weight required except for Live Tray packaging (living plants)
 * - Pricing units support automatic weight conversions for order calculations
 * 
 * @workflow_patterns
 * Product Pricing Setup:
 * 1. Create global pricing templates for common pricing tiers
 * 2. Apply templates to new products with automatic customization
 * 3. Adjust pricing based on product-specific costs or market positioning
 * 4. Activate variations to enable customer ordering
 * 5. Monitor inventory levels and adjust pricing based on availability
 * 
 * Order Processing Integration:
 * 1. Customer selects product and desired variation (packaging/quantity)
 * 2. System calculates price based on variation pricing unit and customer tier
 * 3. Inventory reserved for selected variation during order processing
 * 4. Production planned based on variation fill weights and yield requirements
 * 
 * Inventory Management:
 * 1. Active price variations automatically create inventory entries
 * 2. Stock levels tracked per variation for accurate availability
 * 3. Deactivated variations maintain inventory history but prevent new orders
 * 4. Inventory safety checks prevent deletion of variations with stock
 * 
 * @pricing_calculations
 * Weight-Based Pricing: price * (customer_quantity / pricing_unit_factor)
 * Unit-Based Pricing: price * customer_quantity
 * Packaging Pricing: price per container regardless of actual fill weight
 * 
 * @agricultural_context
 * - Retail pricing: Premium for small quantities with attractive packaging
 * - Wholesale pricing: Volume discounts for restaurant and distributor customers
 * - Bulk pricing: Maximum discounts for large-scale buyers
 * - Live tray pricing: Special pricing for living plants delivered in growing trays
 * - Seasonal adjustments: Pricing reflects growing costs and market availability
 * 
 * @performance_considerations
 * - Database table uses 'product_price_variations' for legacy compatibility
 * - Inventory entries auto-created/updated via model lifecycle hooks
 * - Unit conversion calculations cached for frequently accessed variations
 * - Activity logging tracks pricing changes for audit and analysis
 * 
 * @see \App\Models\Product For products with multiple pricing variations
 * @see \App\Models\PackagingType For container specifications affecting pricing
 * @see \App\Models\ProductInventory For inventory tracking per variation
 * @see \App\Actions\PriceVariation\ApplyTemplateAction For template application workflow
 * 
 * @author Agricultural Systems Team
 * @package App\Models
 */
class PriceVariation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The table associated with the model.
     * 
     * Uses legacy table name 'product_price_variations' for backward compatibility
     * with existing data and external integrations. Modern naming would use
     * 'price_variations' but maintained for data continuity.
     *
     * @var string
     */
    protected $table = 'product_price_variations';

    /**
     * The attributes that are mass assignable.
     * 
     * Defines which pricing fields can be bulk assigned during creation
     * and updates, supporting agricultural pricing management workflows.
     * Includes all pricing parameters, packaging relationships, and status flags.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'template_id',
        'packaging_type_id',
        'pricing_type',
        'name',
        'is_name_manual',
        'unit',
        'sku',
        'fill_weight_grams',
        'price',
        'pricing_unit',
        'is_default',
        'is_global',
        'is_active',
    ];

    /**
     * The attributes that should be cast to appropriate data types.
     * 
     * Ensures proper handling of boolean flags, decimal pricing values,
     * and weight measurements for accurate agricultural pricing calculations.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'is_name_manual' => 'boolean',
        'fill_weight_grams' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * Get validation rules for price variations.
     * 
     * Returns dynamic validation rules based on variation type (global vs product-specific)
     * and packaging type (Live Tray has different weight requirements). Ensures
     * data integrity for agricultural pricing structures.
     * 
     * @param bool $isGlobal Whether this is a global pricing template
     * @param int|null $id Existing variation ID (for update validation)
     * @param int|null $packagingTypeId Packaging type ID for weight requirement logic
     * @return array<string, string|array> Laravel validation rules
     * @business_context Global templates and Live Tray packaging have different requirements
     * @usage Form validation and API input validation
     */
    public static function rules($isGlobal = false, $id = null, $packagingTypeId = null)
    {
        // Check if this is a live tray packaging type
        $isLiveTray = false;
        if ($packagingTypeId) {
            $packaging = PackagingType::find($packagingTypeId);
            $isLiveTray = $packaging && $packaging->name === 'Live Tray';
        }
        
        return [
            'product_id' => $isGlobal ? 'nullable' : 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'fill_weight_grams' => $isGlobal || $isLiveTray ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'packaging_type_id' => 'nullable|exists:packaging_types,id',
            'is_default' => 'boolean',
            'is_global' => 'boolean',
            'is_active' => 'boolean'
        ];
    }

    protected static function booted()
    {
        static::creating(function ($priceVariation) {
            // Debug: Log the data being saved
            Log::info('PriceVariation creating', [
                'name' => $priceVariation->name,
                'packaging_type_id' => $priceVariation->packaging_type_id,
                'is_global' => $priceVariation->is_global,
                'price' => $priceVariation->price,
            ]);
            
            // Set product_id to NULL for global price variations
            if ($priceVariation->is_global) {
                $priceVariation->product_id = null;
                // Global templates can't be default
                $priceVariation->is_default = false;
            }
            
            // Set default name if empty
            if (empty($priceVariation->name) && !$priceVariation->template_id && !$priceVariation->is_global) {
                $priceVariation->name = 'Default';
            }
            
            // Handle default pricing for product-specific variations
            if ($priceVariation->is_default && !$priceVariation->is_global) {
                static::where('product_id', $priceVariation->product_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
        
        static::updating(function ($priceVariation) {
            // Set product_id to NULL for global price variations
            if ($priceVariation->is_global) {
                $priceVariation->product_id = null;
                // Global templates can't be default
                $priceVariation->is_default = false;
            }
            
            // Auto-update name removed - manual naming only
            
            // Handle default pricing for product-specific variations
            if ($priceVariation->is_default && $priceVariation->isDirty('is_default') && !$priceVariation->is_global) {
                static::where('product_id', $priceVariation->product_id)
                    ->where('id', '!=', $priceVariation->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
        
        // Create inventory entry when price variation is created
        static::created(function ($priceVariation) {
            if (!$priceVariation->is_global && $priceVariation->product_id && $priceVariation->is_active) {
                $priceVariation->ensureInventoryEntryExists();
            }
        });

        // Update inventory when price variation is activated/deactivated
        static::updated(function ($priceVariation) {
            if (!$priceVariation->is_global && $priceVariation->product_id) {
                if ($priceVariation->is_active && $priceVariation->wasChanged('is_active')) {
                    // Price variation was just activated - ensure inventory exists
                    $priceVariation->ensureInventoryEntryExists();
                } elseif (!$priceVariation->is_active && $priceVariation->wasChanged('is_active')) {
                    // Price variation was deactivated - optionally mark inventory as inactive
                    $priceVariation->deactivateInventoryEntry();
                }
            }
        });

        // Prevent deletion of price variations that have inventory
        static::deleting(function ($priceVariation) {
            if (!$priceVariation->is_global && $priceVariation->product_id) {
                $inventory = ProductInventory::where('product_id', $priceVariation->product_id)
                    ->where('price_variation_id', $priceVariation->id)
                    ->first();
                    
                if ($inventory && ($inventory->quantity > 0 || $inventory->reserved_quantity > 0)) {
                    throw new Exception("Cannot delete price variation '{$priceVariation->name}' because it has inventory ({$inventory->quantity} units, {$inventory->reserved_quantity} reserved). Please reduce inventory to zero first.");
                }
            }
        });

        // Handle inventory when price variation is deleted
        static::deleted(function ($priceVariation) {
            if (!$priceVariation->is_global && $priceVariation->product_id) {
                // Mark associated inventory as inactive rather than deleting
                $priceVariation->deactivateInventoryEntry();
            }

            // Ensure there's always a default price if possible (only for product-specific variations)
            if ($priceVariation->is_default && !$priceVariation->is_global) {
                $firstVariation = static::where('product_id', $priceVariation->product_id)
                    ->where('is_global', false)
                    ->first();
                if ($firstVariation) {
                    $firstVariation->update(['is_default' => true]);
                }
            }
        });
    }

    /**
     * Get the product that owns the price variation.
     * 
     * Returns the microgreens product being priced by this variation.
     * For global pricing templates, this relationship returns null since
     * templates are reusable across multiple products.
     * 
     * @return BelongsTo<Product, PriceVariation> Product being priced or null for templates
     * @business_context Global templates have product_id = null for reusability
     * @usage Product-specific pricing management and order calculations
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the packaging type for this price variation.
     * 
     * Returns the container/packaging specification that affects pricing
     * through material costs, fill weights, and customer appeal. Different
     * packaging types support different pricing strategies and target markets.
     * 
     * @return BelongsTo<PackagingType, PriceVariation> Container/packaging specification
     * @business_context Packaging affects pricing through container costs and presentation
     * @usage Packaging cost calculations and customer ordering workflows
     */
    public function packagingType(): BelongsTo
    {
        return $this->belongsTo(PackagingType::class, 'packaging_type_id');
    }

    /**
     * Get the template that this price variation was created from.
     * 
     * Returns the global pricing template used to create this product-specific
     * variation. Maintains traceability for pricing consistency and enables
     * bulk updates when templates are modified.
     * 
     * @return BelongsTo<PriceVariation, PriceVariation> Template variation used for creation
     * @business_context Links product variations to global pricing strategies
     * @usage Template management and bulk pricing updates
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PriceVariation::class, 'template_id');
    }

    /**
     * Get the item that owns the price variation.
     * 
     * Legacy method maintained for backward compatibility with existing code.
     * Modern code should use the product() relationship method instead.
     * 
     * @return BelongsTo<Product, PriceVariation> Product relationship (same as product())
     * @deprecated Use product() instead for clarity and modern naming conventions
     * @migration_path Replace calls with $priceVariation->product()
     */
    public function item(): BelongsTo
    {
        return $this->product();
    }

    /**
     * Configure the activity log options for this model.
     * 
     * Defines which pricing fields are tracked for audit and agricultural
     * compliance purposes. Logs changes to prices, packaging, and status
     * for pricing transparency and business analysis.
     * 
     * @return LogOptions Configured logging options for pricing changes
     * @business_context Pricing changes directly impact profitability and customer relations
     * @compliance Required for pricing audit trails and margin analysis
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'product_id',
                'template_id',
                'packaging_type_id',
                'name',
                'sku',
                'fill_weight_grams',
                'price',
                'pricing_unit',
                'is_default',
                'is_global',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Ensure inventory entry exists for this price variation.
     * 
     * Automatically creates ProductInventory record for active, product-specific
     * price variations to enable stock tracking per variation. Global templates
     * and inactive variations skip inventory creation.
     * 
     * @return void
     * @business_context Each price variation needs separate inventory tracking
     * @automation Called automatically when variations are activated
     * @safety Prevents inventory creation for global templates
     */
    public function ensureInventoryEntryExists(): void
    {
        if ($this->is_global || !$this->product_id) {
            return;
        }

        $existingInventory = ProductInventory::where('product_id', $this->product_id)
            ->where('price_variation_id', $this->id)
            ->first();

        if (!$existingInventory) {
            ProductInventory::create([
                'product_id' => $this->product_id,
                'price_variation_id' => $this->id,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'cost_per_unit' => 0,
                'production_date' => now(),
                'expiration_date' => null,
                'location' => null,
                'product_inventory_status_id' => ProductInventoryStatus::firstOrCreate(['code' => 'active'], ['name' => 'Active', 'description' => 'Active inventory'])->id,
                'notes' => "Auto-created for {$this->name} variation",
            ]);
        }
    }

    /**
     * Deactivate inventory entry for this price variation.
     * 
     * Marks associated inventory records as inactive rather than deleting them
     * to preserve historical data and prevent data integrity issues. Maintains
     * audit trail while preventing new orders against the variation.
     * 
     * @return void
     * @business_context Preserves historical inventory data for analysis
     * @safety Prevents deletion of inventory records with existing data
     * @automation Called automatically when variations are deactivated or deleted
     */
    public function deactivateInventoryEntry(): void
    {
        if ($this->is_global || !$this->product_id) {
            return;
        }

        // Get inactive status ID
        $inactiveStatus = ProductInventoryStatus::where('code', 'inactive')->first();
        if ($inactiveStatus) {
            ProductInventory::where('product_id', $this->product_id)
                ->where('price_variation_id', $this->id)
                ->update(['product_inventory_status_id' => $inactiveStatus->id]);
        }
    }

    /**
     * Update variation name automatically only if not manually overridden.
     * 
     * Refreshes auto-generated variation name based on current pricing type
     * and packaging, but only if the name hasn't been manually customized.
     * Preserves manual naming while keeping auto-generated names current.
     * 
     * @return void
     * @business_context Maintains consistent naming while preserving manual customization
     * @usage Called when packaging or pricing type changes
     * @safety Respects is_name_manual flag to preserve custom names
     */
    public function updateVariationName(): void
    {
        if (!$this->is_name_manual) {
            $this->name = $this->generateVariationName();
        }
    }

    /**
     * Generate variation name in format: "Pricing Type - Packaging".
     * 
     * Creates consistent, descriptive names for price variations using
     * pricing tier and packaging information. Provides fallbacks for
     * missing data and handles package-free variations appropriately.
     * 
     * @return string Generated variation name (e.g., "Wholesale - Clamshell")
     * @format "[Pricing Type] - [Packaging Type]" or "[Pricing Type] - Package-Free"
     * @business_context Consistent naming improves customer understanding and internal management
     * @usage Auto-naming during variation creation and updates
     * @example "Bulk - Live Tray", "Retail - Clamshell", "Wholesale - Package-Free"
     */
    public function generateVariationName(): string
    {
        $parts = [];
        
        // 1. Add pricing type (get from pricing_type attribute or infer from other fields)
        $pricingType = $this->pricing_type ?? 'retail'; // Default to retail if not set
        $pricingTypeNames = [
            'retail' => 'Retail',
            'wholesale' => 'Wholesale',
            'bulk' => 'Bulk',
            'special' => 'Special',
            'custom' => 'Custom',
        ];
        $parts[] = $pricingTypeNames[$pricingType] ?? ucfirst($pricingType);
        
        // 2. Add packaging information (without volume/size)
        if ($this->packaging_type_id) {
            $packaging = $this->packagingType;
            if ($packaging) {
                $parts[] = $packaging->name;
            }
        } else {
            // Handle package-free variations
            $parts[] = 'Package-Free';
        }
        
        // Join with " - " separator
        return implode(' - ', $parts);
    }
    
    /**
     * Check if this price variation is sold by weight.
     * 
     * Determines if pricing is weight-based (per pound, kilogram, etc.) versus
     * unit-based (per item/container). Affects order calculations, inventory
     * tracking, and customer ordering workflows for agricultural products.
     * 
     * @return bool True if pricing is weight-based (lb, kg, g, oz units)
     * @business_context Weight-based pricing common for bulk agricultural sales
     * @usage Order calculation logic and inventory management systems
     */
    public function isSoldByWeight(): bool
    {
        return in_array($this->pricing_unit, ['per_lb', 'per_kg', 'per_g', 'per_oz', 'lb', 'lbs', 'kg', 'g', 'oz']);
    }
    
    /**
     * Get the unit conversion factor to grams.
     * 
     * Returns multiplication factor to convert pricing unit quantities to grams
     * for standardized inventory tracking and yield calculations. Supports
     * common agricultural weight units with precise conversion factors.
     * 
     * @return float Conversion factor (pricing_unit_quantity * factor = grams)
     * @business_context All agricultural inventory tracked in grams for consistency
     * @precision Uses standard conversion factors for accurate weight calculations
     * @usage Inventory conversion and yield planning calculations
     * @example 1 lb = 453.592 grams, 1 oz = 28.3495 grams
     */
    public function getUnitToGramsConversionFactor(): float
    {
        return match($this->pricing_unit) {
            'per_g', 'g', 'gram', 'grams' => 1.0,
            'per_kg', 'kg', 'kilogram', 'kilograms' => 1000.0,
            'per_lb', 'lb', 'lbs', 'pound', 'pounds' => 453.592,
            'per_oz', 'oz', 'ounce', 'ounces' => 28.3495,
            default => 1.0
        };
    }
    
    /**
     * Convert a quantity in the pricing unit to grams.
     * 
     * Transforms customer order quantities from pricing units (pounds, ounces)
     * to standard gram measurements for inventory deduction and production
     * planning. Essential for weight-based agricultural sales.
     * 
     * @param float $quantity Quantity in pricing unit to convert
     * @return float Equivalent quantity in grams
     * @business_context Standardizes diverse customer units to production units
     * @usage Order processing and inventory reservation calculations
     * @example convertToGrams(2.5) for "per_lb" = 2.5 * 453.592 = 1133.98 grams
     */
    public function convertToGrams(float $quantity): float
    {
        return $quantity * $this->getUnitToGramsConversionFactor();
    }
    
    /**
     * Get display unit for quantity input.
     * 
     * Returns human-readable unit name for customer-facing forms and displays.
     * Converts technical pricing units to friendly names that customers
     * understand when placing orders for agricultural products.
     * 
     * @return string Display-friendly unit name for customer interfaces
     * @business_context Improves customer experience with clear unit labeling
     * @usage Order forms, product displays, and quantity input fields
     * @example "per_lb" → "lbs", "per_kg" → "kg", "each" → "units"
     */
    public function getDisplayUnit(): string
    {
        return match($this->pricing_unit) {
            'per_g', 'g', 'gram' => 'grams',
            'per_kg', 'kg', 'kilogram' => 'kg',
            'per_lb', 'lb', 'lbs', 'pound' => 'lbs',
            'per_oz', 'oz', 'ounce' => 'oz',
            'per_item', 'each' => 'units',
            default => 'units'
        };
    }
}
