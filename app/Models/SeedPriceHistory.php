<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeedPriceHistory extends Model
{
    use HasFactory;

    // Explicitly set the table name to match the database
    protected $table = 'seed_price_history';

    protected $fillable = [
        'seed_variation_id', 
        'price', 
        'currency',
        'is_in_stock', 
        'checked_at'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'is_in_stock' => 'boolean',
        'checked_at' => 'datetime',
        'scraped_at' => 'datetime',
    ];
    
    /**
     * Get the seed variation that this price history belongs to
     */
    public function seedVariation(): BelongsTo
    {
        return $this->belongsTo(SeedVariation::class);
    }
}
