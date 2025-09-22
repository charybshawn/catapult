<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only model for the recipes_optimized_view
 * This model eliminates N+1 queries by using direct master catalog joins
 * instead of going through the consumables table
 */
class RecipeOptimizedView extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'recipes_optimized_view';
    
    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'germination_days' => 'float',
        'blackout_days' => 'float',
        'days_to_maturity' => 'float',
        'light_days' => 'float',
        'seed_soak_hours' => 'integer',
        'expected_yield_grams' => 'float',
        'buffer_percentage' => 'decimal:2',
        'seed_density_grams_per_tray' => 'float',
        'suspend_water_hours' => 'integer',
        'is_active' => 'boolean',
        'requires_soaking' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'master_seed_catalog_id' => 'integer',
        'master_cultivar_id' => 'integer',
    ];
    
    /**
     * Scope for active recipes only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for recipes that require soaking
     */
    public function scopeRequiresSoaking($query)
    {
        return $query->where('requires_soaking', true);
    }
    
    /**
     * Get the master seed catalog relationship (for compatibility)
     * Note: This will trigger a query, so prefer using the pre-loaded fields
     */
    public function masterSeedCatalog()
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }
    
    /**
     * Get the master cultivar relationship (for compatibility)
     * Note: This will trigger a query, so prefer using the pre-loaded fields
     */
    public function masterCultivar()
    {
        return $this->belongsTo(MasterCultivar::class);
    }
    
    /**
     * Get the full variety name (pre-computed in view)
     */
    public function getFullVarietyName(): string
    {
        return $this->variety_name ?? 'Unknown Variety';
    }
    
    /**
     * Check if this recipe requires soaking (pre-computed in view)
     */
    public function requiresSoaking(): bool
    {
        return $this->requires_soaking;
    }
    
    /**
     * Get display name for dropdowns and lists (uses the computed name field)
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }
    
    /**
     * Get a formatted name with growing days for detailed displays
     */
    public function getDetailedName(): string
    {
        $name = $this->name;
        if ($this->days_to_maturity) {
            $name .= " ({$this->days_to_maturity} days)";
        }
        return $name;
    }
    
    /**
     * Get all recipes as options for dropdowns
     * Pre-computed names eliminate need for N+1 queries
     */
    public static function getOptions(): array
    {
        return static::active()
            ->orderBy('common_name')
            ->orderBy('cultivar_name')
            ->pluck('name', 'id')
            ->toArray();
    }
    
    /**
     * Get recipes grouped by common name for organized dropdowns
     */
    public static function getGroupedOptions(): array
    {
        $recipes = static::active()
            ->orderBy('common_name')
            ->orderBy('cultivar_name')
            ->get();
            
        $grouped = [];
        foreach ($recipes as $recipe) {
            $commonName = $recipe->common_name ?: 'Other';
            $grouped[$commonName][$recipe->id] = $recipe->name;
        }
        
        return $grouped;
    }
}