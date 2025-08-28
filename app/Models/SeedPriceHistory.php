<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents historical pricing data for agricultural seed variations,
 * tracking price changes, availability fluctuations, and market trends
 * for microgreens seed procurement and cost analysis.
 *
 * @business_domain Agricultural Seed Pricing Analytics & Procurement Intelligence
 * @workflow_context Used in cost analysis, procurement planning, and supplier evaluation
 * @agricultural_process Tracks seed market pricing for optimal purchasing decisions
 *
 * Database Table: seed_price_history
 * @property int $id Primary identifier for price history record
 * @property int $seed_variation_id Reference to specific seed variation
 * @property float $price Seed price at time of check
 * @property string $currency Price currency (typically USD)
 * @property bool $is_in_stock Whether seed was available when checked
 * @property Carbon $checked_at Timestamp when price/availability was verified
 * @property Carbon|null $scraped_at Timestamp when data was automatically scraped
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship seedVariation BelongsTo relationship to SeedVariation for context
 *
 * @business_rule Price history enables trend analysis and procurement optimization
 * @business_rule Stock availability tracking prevents ordering out-of-stock items
 * @business_rule Historical data supports seasonal pricing analysis
 *
 * @agricultural_procurement Helps identify best times to purchase seeds
 * @cost_optimization Enables bulk purchasing during price dips
 */
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
     * Get the seed variation this price history record tracks.
     * Links pricing data to specific agricultural seed offerings.
     *
     * @return BelongsTo SeedVariation relationship
     * @agricultural_context Connects price data to specific seed variety and packaging
     * @business_usage Used in cost analysis and procurement decision making
     * @pricing_analytics Enables variety-specific price trend analysis
     */
    public function seedVariation(): BelongsTo
    {
        return $this->belongsTo(SeedVariation::class);
    }
}
