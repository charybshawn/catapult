<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function seedCultivar()
    {
        return $this->belongsTo(SeedCultivar::class);
    }
    
    /**
     * Get the supplier that this seed entry belongs to
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Get the variations for this seed entry
     */
    public function variations()
    {
        return $this->hasMany(SeedVariation::class);
    }
}
