<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PriceVariation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_price_variations';

    /**
     * The attributes that are mass assignable.
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
        'fill_weight',
        'price',
        'pricing_unit',
        'is_default',
        'is_global',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'is_name_manual' => 'boolean',
        'fill_weight' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * Get validation rules for price variations.
     */
    public static function rules($isGlobal = false, $id = null, $packagingTypeId = null)
    {
        // Check if this is a live tray packaging type
        $isLiveTray = false;
        if ($packagingTypeId) {
            $packaging = \App\Models\PackagingType::find($packagingTypeId);
            $isLiveTray = $packaging && $packaging->name === 'Live Tray';
        }
        
        return [
            'product_id' => $isGlobal ? 'nullable' : 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'fill_weight' => $isGlobal || $isLiveTray ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
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
            
            // Auto-generate name in format: "Pricing Type - Packaging (size) - $price"
            if ((empty($priceVariation->name) || $priceVariation->name === 'New Variation' || $priceVariation->name === 'Auto-generated') && !$priceVariation->template_id && !$priceVariation->is_global) {
                $priceVariation->name = $priceVariation->generateVariationName();
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
            
            // Auto-update name when packaging type, pricing type, or price changes
            if (($priceVariation->isDirty('packaging_type_id') || $priceVariation->isDirty('pricing_type') || $priceVariation->isDirty('price')) && !$priceVariation->template_id && !$priceVariation->is_global) {
                $priceVariation->name = $priceVariation->generateVariationName();
            }
            
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
                $inventory = \App\Models\ProductInventory::where('product_id', $priceVariation->product_id)
                    ->where('price_variation_id', $priceVariation->id)
                    ->first();
                    
                if ($inventory && ($inventory->quantity > 0 || $inventory->reserved_quantity > 0)) {
                    throw new \Exception("Cannot delete price variation '{$priceVariation->name}' because it has inventory ({$inventory->quantity} units, {$inventory->reserved_quantity} reserved). Please reduce inventory to zero first.");
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
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the packaging type for this price variation.
     */
    public function packagingType(): BelongsTo
    {
        return $this->belongsTo(PackagingType::class, 'packaging_type_id');
    }

    /**
     * Get the template that this price variation was created from.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PriceVariation::class, 'template_id');
    }

    /**
     * Get the item that owns the price variation.
     * 
     * @deprecated Use product() instead
     */
    public function item(): BelongsTo
    {
        return $this->product();
    }

    /**
     * Configure the activity log options for this model.
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
                'fill_weight',
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
     * Ensure inventory entry exists for this price variation
     */
    public function ensureInventoryEntryExists(): void
    {
        if ($this->is_global || !$this->product_id) {
            return;
        }

        $existingInventory = \App\Models\ProductInventory::where('product_id', $this->product_id)
            ->where('price_variation_id', $this->id)
            ->first();

        if (!$existingInventory) {
            \App\Models\ProductInventory::create([
                'product_id' => $this->product_id,
                'price_variation_id' => $this->id,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'cost_per_unit' => 0,
                'production_date' => now(),
                'expiration_date' => null,
                'location' => null,
                'product_inventory_status_id' => \App\Models\ProductInventoryStatus::firstOrCreate(['code' => 'active'], ['name' => 'Active', 'description' => 'Active inventory'])->id,
                'notes' => "Auto-created for {$this->name} variation",
            ]);
        }
    }

    /**
     * Deactivate inventory entry for this price variation
     */
    public function deactivateInventoryEntry(): void
    {
        if ($this->is_global || !$this->product_id) {
            return;
        }

        \App\Models\ProductInventory::where('product_id', $this->product_id)
            ->where('price_variation_id', $this->id)
            ->update(['status' => 'inactive']);
    }

    /**
     * Update variation name automatically only if not manually overridden
     */
    public function updateVariationName(): void
    {
        if (!$this->is_name_manual) {
            $this->name = $this->generateVariationName();
        }
    }

    /**
     * Generate variation name in format: "Pricing Type - Packaging (size) - $price"
     * Example: "Retail - Clamshell (24oz) - $5.00"
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
        
        // 2. Add packaging information
        if ($this->packaging_type_id) {
            $packaging = $this->packagingType;
            if ($packaging) {
                $packagingPart = $packaging->name;
                
                // Add size information in parentheses
                if ($packaging->capacity_volume && $packaging->volume_unit) {
                    $packagingPart .= ' (' . $packaging->capacity_volume . $packaging->volume_unit . ')';
                }
                
                $parts[] = $packagingPart;
            }
        } else {
            // Handle package-free variations
            $parts[] = 'Package-Free';
        }
        
        // 3. Add price
        if ($this->price && is_numeric($this->price)) {
            $parts[] = '$' . number_format((float)$this->price, 2);
        }
        
        // Join with " - " separator
        return implode(' - ', $parts);
    }
}
