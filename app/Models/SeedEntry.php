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
        'seed_cultivar_id', // Optional during transition
        'cultivar_name',
        'common_name',
        'supplier_id', 
        'supplier_product_title', 
        'supplier_product_url', 
        'image_url', 
        'description', 
        'tags',
        'cataloged_at'
    ];
    
    protected $casts = [
        'tags' => 'array',
        'cataloged_at' => 'datetime',
    ];
    
    
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
