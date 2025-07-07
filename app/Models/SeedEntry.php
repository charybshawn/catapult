<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use App\Traits\HasActiveStatus;
use App\Traits\HasSupplier;
use App\Traits\HasTimestamps;

class SeedEntry extends Model
{
    use HasFactory, HasActiveStatus, HasSupplier, HasTimestamps;
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($entry) {
            // Validate common name is not empty or just whitespace
            if (empty(trim($entry->common_name))) {
                throw new \InvalidArgumentException('Common name is required and cannot be empty');
            }
            
            // Validate cultivar name is not empty or just whitespace
            if (empty(trim($entry->cultivar_name))) {
                throw new \InvalidArgumentException('Cultivar name is required and cannot be empty');
            }
            
            // Validate supplier ID exists
            if (empty($entry->supplier_id)) {
                throw new \InvalidArgumentException('Supplier is required');
            }
            
            // Validate URL format if provided
            if ($entry->url && !filter_var($entry->url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Supplier product URL must be a valid URL');
            }
            
            if ($entry->image_url && !filter_var($entry->image_url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Image URL must be a valid URL');
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
     * Get the variations for this seed entry
     */
    public function variations(): HasMany
    {
        return $this->hasMany(SeedVariation::class);
    }
    
    /**
     * Get the recipes that use this seed entry
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'seed_entry_id');
    }
    
    /**
     * Get the consumables linked to this seed entry
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }
}
