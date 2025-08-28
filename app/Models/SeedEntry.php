<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use App\Traits\HasActiveStatus;
use App\Traits\HasSupplier;
use App\Traits\HasTimestamps;

/**
 * Represents individual seed catalog entries for agricultural seed management,
 * linking supplier products to internal seed varieties with validation,
 * normalization, and duplicate detection for microgreens production.
 *
 * @business_domain Agricultural Seed Catalog Management & Supplier Integration
 * @workflow_context Used in seed sourcing, catalog maintenance, and supplier management
 * @agricultural_process Manages seed variety data from multiple agricultural suppliers
 *
 * Database Table: seed_entries
 * @property int $id Primary identifier for seed entry
 * @property string $cultivar_name Specific variety name from supplier
 * @property string $common_name Normalized common seed type name
 * @property int $supplier_id Reference to agricultural supplier
 * @property string|null $supplier_product_title Original supplier product title
 * @property string|null $supplier_sku Supplier's stock keeping unit code
 * @property string|null $supplier_product_url Link to supplier's product page
 * @property string|null $url Alternative or canonical product URL
 * @property string|null $image_url Product image URL from supplier
 * @property string|null $description Seed variety description and characteristics
 * @property array|null $tags Classification tags for agricultural organization
 * @property bool $is_active Whether this seed entry is available for use
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship supplier BelongsTo relationship via HasSupplier trait
 * @relationship variations HasMany relationship to SeedVariation records
 * @relationship recipes HasMany relationship to Recipe cultivation records
 * @relationship consumables HasMany relationship to Consumable inventory items
 *
 * @business_rule Common and cultivar names are required and validated
 * @business_rule Names are normalized for consistency (capitalization, whitespace)
 * @business_rule Duplicate detection warns of potential catalog conflicts
 * @business_rule URLs are validated for proper format when provided
 *
 * @agricultural_integration Links supplier catalogs to internal seed management
 * @data_quality Automatic validation and normalization ensures catalog consistency
 */
class SeedEntry extends Model
{
    use HasFactory, HasActiveStatus, HasSupplier, HasTimestamps;
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($entry) {
            // Validate common name is not empty or just whitespace
            if (empty(trim($entry->common_name))) {
                throw new InvalidArgumentException('Common name is required and cannot be empty');
            }
            
            // Validate cultivar name is not empty or just whitespace
            if (empty(trim($entry->cultivar_name))) {
                throw new InvalidArgumentException('Cultivar name is required and cannot be empty');
            }
            
            // Validate supplier ID exists
            if (empty($entry->supplier_id)) {
                throw new InvalidArgumentException('Supplier is required');
            }
            
            // Validate URL format if provided
            if ($entry->url && !filter_var($entry->url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Supplier product URL must be a valid URL');
            }
            
            if ($entry->image_url && !filter_var($entry->image_url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Image URL must be a valid URL');
            }
            
            // Normalize common and cultivar names - trim whitespace and standardize capitalization
            $entry->common_name = ucwords(strtolower(trim($entry->common_name)));
            $entry->cultivar_name = trim($entry->cultivar_name);
            
            // Log potential duplicates for review
            $duplicate = static::where('common_name', $entry->common_name)
                ->where('cultivar_name', $entry->cultivar_name)
                ->where('supplier_id', $entry->supplier_id)
                ->where('id', '!=', $entry->id)
                ->first();
                
            if ($duplicate) {
                Log::warning('Potential duplicate seed entry detected', [
                    'existing_id' => $duplicate->id,
                    'new_entry' => [
                        'common_name' => $entry->common_name,
                        'cultivar_name' => $entry->cultivar_name,
                        'supplier_id' => $entry->supplier_id,
                    ]
                ]);
            }
        });
    }

    protected $fillable = [
        'cultivar_name',
        'common_name',
        'supplier_id', 
        'supplier_product_title',
        'supplier_sku', 
        'supplier_product_url',
        'url', 
        'image_url', 
        'description', 
        'tags',
        'is_active'
    ];
    
    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];
    
    
    // Supplier relationship is now provided by HasSupplier trait
    
    /**
     * Get all price and packaging variations for this seed entry.
     * Links to different supplier offerings and pricing options.
     *
     * @return HasMany SeedVariation collection with pricing data
     * @agricultural_context Different package sizes and pricing from suppliers
     * @business_usage Used in cost analysis and seed procurement decisions
     * @supplier_integration Tracks multiple offerings from same or different suppliers
     */
    public function variations(): HasMany
    {
        return $this->hasMany(SeedVariation::class);
    }
    
    /**
     * Get cultivation recipes that use this specific seed entry.
     * Links seed entries to agricultural cultivation methodologies.
     *
     * @return HasMany Recipe collection using this seed
     * @agricultural_context Connects seed varieties to growing instructions
     * @business_usage Used in recipe management and cultivation planning
     * @cultivation_workflow Enables seed-specific growing guidance
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'seed_entry_id');
    }
    
    /**
     * Get consumable inventory items linked to this seed entry.
     * Connects catalog entries to physical seed inventory management.
     *
     * @return HasMany Consumable seed inventory items
     * @agricultural_context Links catalog data to actual seed stock
     * @business_usage Used in inventory tracking and seed procurement
     * @inventory_integration Enables stock level monitoring for seed varieties
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }
}
