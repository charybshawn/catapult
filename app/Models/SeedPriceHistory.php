<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeedPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'seed_variation_id', 
        'price', 
        'currency',
        'is_in_stock', 
        'scraped_at'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'is_in_stock' => 'boolean',
        'scraped_at' => 'datetime',
    ];
    
    /**
     * Get the seed variation that this price history belongs to
     */
    public function seedVariation()
    {
        return $this->belongsTo(SeedVariation::class);
    }
}
