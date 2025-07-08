<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use App\Traits\Logging\ExtendedLogsActivity;
use App\Services\InventoryManagementService;
use App\Services\RecipeService;
use App\Traits\HasActiveStatus;
use App\Traits\HasTimestamps;

class Recipe extends Model
{
    use HasFactory, ExtendedLogsActivity, HasActiveStatus, HasTimestamps;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'master_seed_catalog_id',
        'master_cultivar_id',
        'common_name',
        'cultivar_name',
        'seed_consumable_id',
        'lot_number',
        'lot_depleted_at',
        'soil_consumable_id',
        'seed_density',
        'germination_days',
        'blackout_days',
        'days_to_maturity',
        'light_days',
        'harvest_days',
        'seed_soak_hours',
        'expected_yield_grams', 
        'buffer_percentage',
        'seed_density_grams_per_tray',
        'is_active',
        'notes',
        'suspend_water_hours',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
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
        'is_active' => 'boolean',
        'lot_depleted_at' => 'datetime',
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
     * 
     * @deprecated Use lot-based methods instead (lotConsumables, availableLotConsumables)
     */
    public function seedConsumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class, 'seed_consumable_id');
    }
    
    /**
     * Get all consumable entries for the recipe's lot_number.
     * Delegates to InventoryManagementService for consistency.
     * 
     * @return Collection
     */
    public function lotConsumables(): Collection
    {
        if (!$this->lot_number) {
            return collect();
        }
        
        return app(InventoryManagementService::class)->getEntriesInLot($this->lot_number);
    }
    
    /**
     * Get available (not depleted) consumable entries for the recipe's lot.
     * Delegates to InventoryManagementService for consistency.
     * 
     * @return Collection
     */
    public function availableLotConsumables(): Collection
    {
        if (!$this->lot_number) {
            return collect();
        }
        
        $inventoryService = app(InventoryManagementService::class);
        return $inventoryService->getEntriesInLot($this->lot_number)
            ->filter(function (Consumable $consumable) use ($inventoryService) {
                return $inventoryService->getCurrentStock($consumable) > 0;
            });
    }
    
    
    /**
     * Get the watering schedule for this recipe.
     */
    public function wateringSchedule(): HasMany
    {
        return $this->hasMany(RecipeWateringSchedule::class);
    }
    
    /**
     * Get the crops using this recipe.
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class);
    }
    
    /**
     * Get the harvests for this recipe.
     */
    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }
    
    /**
     * Calculate the total days from planting to harvest.
     * @return float
     */
    public function totalDays(): float
    {
        return app(RecipeService::class)->calculateTotalDays($this);
    }
    
    /**
     * Calculate days to harvest including seed soak time.
     * @return float
     */
    public function effectiveTotalDays(): float
    {
        return app(RecipeService::class)->calculateEffectiveTotalDays($this);
    }
    
    /**
     * Get total available quantity for the recipe's lot.
     * Delegates to InventoryManagementService.
     * 
     * @return float
     */
    public function getLotQuantity(): float
    {
        if (!$this->lot_number) {
            return 0.0;
        }
        
        return app(InventoryManagementService::class)->getLotQuantity($this->lot_number);
    }

    /**
     * Get the relationships that should be logged with this model.
     */
    public function getLoggedRelationships(): array
    {
        return ['soilConsumable', 'wateringSchedule'];
    }

    /**
     * Get specific attributes to include from related models.
     */
    public function getRelationshipAttributesToLog(): array
    {
        return [
            'soilConsumable' => ['id', 'item_name', 'supplier_name', 'current_stock'],
            'wateringSchedule' => ['id', 'stage', 'frequency_hours', 'duration_seconds', 'notes'],
        ];
    }
    
    /**
     * Check if the recipe's assigned lot is depleted.
     * 
     * @return bool
     */
    public function isLotDepleted(): bool
    {
        if (!$this->lot_number) {
            return true;
        }
        
        // Check if manually marked as depleted
        if ($this->lot_depleted_at) {
            return true;
        }
        
        // Check actual inventory
        return app(InventoryManagementService::class)->isLotDepleted($this->lot_number);
    }
    
    /**
     * Check if recipe can be executed with required seed amount.
     * 
     * @param float $requiredQuantity
     * @return bool
     */
    public function canExecute(float $requiredQuantity): bool
    {
        return app(RecipeService::class)->canExecuteRecipe($this, $requiredQuantity);
    }
    
    /**
     * Mark the lot as depleted with timestamp.
     * 
     * @return void
     */
    public function markLotDepleted(): void
    {
        app(RecipeService::class)->markLotDepleted($this);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Recipe $recipe) {
            // Update dependent fields including auto-generating name
            app(RecipeService::class)->updateDependentFields($recipe);
        });
    }

    // Name generation logic moved to RecipeService

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'common_name',
                'cultivar_name',
                'lot_number',
                'lot_depleted_at',
                'germination_days', 
                'blackout_days', 
                'days_to_maturity',
                'light_days',
                'expected_yield_grams',
                'seed_density_grams_per_tray',
                'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
