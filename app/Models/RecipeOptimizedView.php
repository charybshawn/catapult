<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only model for the recipes_optimized_view
 * Provides pre-calculated fields and eager-loaded consumable data
 */
class RecipeOptimizedView extends Model
{
    protected $table = 'recipes_optimized_view';
    
    /**
     * Indicates if the model should be timestamped.
     * Views don't have timestamps that can be updated.
     */
    public $timestamps = false;
    
    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'germination_days' => 'float',
        'blackout_days' => 'float',
        'days_to_maturity' => 'float',
        'light_days' => 'float',
        'total_days' => 'float',
        'seed_soak_hours' => 'integer',
        'expected_yield_grams' => 'float',
        'buffer_percentage' => 'decimal:2',
        'seed_density_grams_per_tray' => 'float',
        'is_active' => 'boolean',
        'lot_depleted_at' => 'datetime',
        'seed_total_quantity' => 'float',
        'seed_consumed_quantity' => 'float',
        'seed_available_quantity' => 'float',
        'soil_total_quantity' => 'float',
        'soil_consumed_quantity' => 'float',
        'soil_available_quantity' => 'float',
        'active_crops_count' => 'integer',
        'total_crops_count' => 'integer',
        'requires_soaking' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the master seed catalog for this recipe.
     */
    public function masterSeedCatalog(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }
    
    /**
     * Get the master cultivar for this recipe.
     */
    public function masterCultivar(): BelongsTo
    {
        return $this->belongsTo(MasterCultivar::class);
    }
    
    /**
     * Get the soil consumable for this recipe.
     */
    public function soilConsumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class, 'soil_consumable_id');
    }
    
    /**
     * Get the seed consumable for this recipe.
     */
    public function seedConsumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class, 'seed_consumable_id');
    }
    
    /**
     * Format seed lot display with availability
     */
    public function getSeedLotDisplayAttribute(): string
    {
        if (!$this->lot_number) {
            if ($this->seed_consumable_name) {
                $unit = $this->seed_quantity_unit ?? 'g';
                return $this->seed_consumable_name . " ({$this->seed_available_quantity} {$unit} available)";
            }
            return '-';
        }
        
        return $this->seed_available_quantity <= 0 
            ? "{$this->lot_number} (Depleted)" 
            : "{$this->lot_number} ({$this->seed_available_quantity}g)";
    }
    
    /**
     * Override save to prevent writes to view
     */
    public function save(array $options = [])
    {
        throw new Exception('Cannot save to a database view. Use the Recipe model instead.');
    }
    
    /**
     * Override delete to prevent deletion from view
     */
    public function delete()
    {
        throw new Exception('Cannot delete from a database view. Use the Recipe model instead.');
    }
}