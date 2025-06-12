<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PriceVariation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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
        'fill_weight_grams' => 'decimal:2',
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
            // Set product_id to NULL for global price variations
            if ($priceVariation->is_global) {
                $priceVariation->product_id = null;
                // Global templates can't be default
                $priceVariation->is_default = false;
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
            
            // Handle default pricing for product-specific variations
            if ($priceVariation->is_default && $priceVariation->isDirty('is_default') && !$priceVariation->is_global) {
                static::where('product_id', $priceVariation->product_id)
                    ->where('id', '!=', $priceVariation->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
        
        // Ensure there's always a default price if possible (only for product-specific variations)
        static::deleted(function ($priceVariation) {
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
}
