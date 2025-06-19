<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductMix extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Validate mix percentages after saving relationships
        static::saved(function ($productMix) {
            // This will run after the mix is saved, giving time for relationships to be updated
            // We'll handle validation in the form instead to provide better UX
        });
    }
    
    /**
     * Get the seed entries that make up this mix.
     * @deprecated Use masterSeedCatalogs() instead
     */
    public function seedEntries(): BelongsToMany
    {
        return $this->belongsToMany(SeedEntry::class, 'product_mix_components', 'product_mix_id', 'seed_entry_id')
            ->withPivot('percentage')
            ->withTimestamps();
    }
    
    /**
     * Get the master seed catalog entries that make up this mix.
     */
    public function masterSeedCatalogs(): BelongsToMany
    {
        return $this->belongsToMany(MasterSeedCatalog::class, 'product_mix_components', 'product_mix_id', 'master_seed_catalog_id')
            ->withPivot('percentage', 'cultivar')
            ->withTimestamps();
    }
    
    /**
     * Get the products that use this mix.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_mix_id');
    }
    
    /**
     * Calculate the number of trays needed for each cultivar in this mix.
     *
     * @param int $totalTrays The total number of trays for this mix
     * @return array Array of [cultivar_id => trays_needed]
     */
    public function calculateCultivarTrays(int $totalTrays): array
    {
        $cultivarTrays = [];
        
        foreach ($this->masterSeedCatalogs as $catalog) {
            $percentage = $catalog->pivot->percentage;
            $trays = ceil(($percentage / 100) * $totalTrays);
            $cultivarTrays[$catalog->id] = $trays;
        }
        
        return $cultivarTrays;
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    
    /**
     * Validate that the mix percentages add up to 100%
     */
    public function validatePercentages(): bool
    {
        $total = $this->masterSeedCatalogs()->sum('percentage');
        
        // Round to 2 decimal places to match database precision
        $total = round($total, 2);
        
        // Allow for very small floating point differences
        return abs($total - 100) < 0.01;
    }
    
    /**
     * Get the total percentage of the mix
     */
    public function getTotalPercentageAttribute(): float
    {
        return $this->masterSeedCatalogs()->sum('percentage');
    }
} 