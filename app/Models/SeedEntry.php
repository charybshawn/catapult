<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeedEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'seed_cultivar_id', 
        'supplier_id', 
        'supplier_product_title', 
        'supplier_product_url', 
        'image_url', 
        'description', 
        'tags'
    ];
    
    protected $casts = [
        'tags' => 'array',
    ];
    
    /**
     * Get the cultivar that this seed entry belongs to
     */
    public function seedCultivar(): BelongsTo
    {
        return $this->belongsTo(SeedCultivar::class);
    }
    
    /**
     * Get the supplier that this seed entry belongs to
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Get the variations for this seed entry
     */
    public function variations(): HasMany
    {
        return $this->hasMany(SeedVariation::class);
    }
}
