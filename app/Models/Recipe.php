<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Services\LotInventoryService;

class Recipe extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'seed_entry_id',
        'common_name',
        'cultivar_name',
        'seed_consumable_id',
        'lot_number',
        'lot_depleted_at',
        'supplier_soil_id',
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
     * Get the soil supplier for this recipe.
     */
    public function soilSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_soil_id');
    }
    
    /**
     * Get the seed entry for this recipe.
     */
    public function seedEntry(): BelongsTo
    {
        return $this->belongsTo(SeedEntry::class, 'seed_entry_id');
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
     * 
     * @return Collection
     */
    public function lotConsumables(): Collection
    {
        if (!$this->lot_number) {
            return collect();
        }
        
        $lotInventoryService = new LotInventoryService();
        $seedTypeId = $lotInventoryService->getSeedTypeId();
        
        if (!$seedTypeId) {
            return collect();
        }
        
        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', strtoupper($this->lot_number))
            ->where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    /**
     * Get available (not depleted) consumable entries for the recipe's lot.
     * 
     * @return Collection
     */
    public function availableLotConsumables(): Collection
    {
        if (!$this->lot_number) {
            return collect();
        }
        
        $lotInventoryService = new LotInventoryService();
        $seedTypeId = $lotInventoryService->getSeedTypeId();
        
        if (!$seedTypeId) {
            return collect();
        }
        
        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', strtoupper($this->lot_number))
            ->where('is_active', true)
            ->whereRaw('(total_quantity - consumed_quantity) > 0')
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    /**
     * Get the stages for this recipe.
     */
    public function stages(): HasMany
    {
        return $this->hasMany(RecipeStage::class);
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
     */
    public function totalDays(): float
    {
        // If days_to_maturity is set, prefer that value
        if ($this->days_to_maturity) {
            return $this->days_to_maturity;
        }
        
        // Otherwise use the sum of all stage durations
        return $this->germination_days + $this->blackout_days + $this->light_days;
    }
    
    /**
     * Calculate days to harvest including seed soak time.
     */
    public function effectiveTotalDays(): float
    {
        return ($this->seed_soak_hours / 24) + $this->totalDays();
    }
    
    /**
     * Get total available quantity for the recipe's lot.
     * 
     * @return float
     */
    public function getLotQuantity(): float
    {
        if (!$this->lot_number) {
            return 0.0;
        }
        
        $lotInventoryService = new LotInventoryService();
        return $lotInventoryService->getLotQuantity($this->lot_number);
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
        $lotInventoryService = new LotInventoryService();
        return $lotInventoryService->isLotDepleted($this->lot_number);
    }
    
    /**
     * Check if recipe can be executed with required seed amount.
     * 
     * @param float $requiredQuantity
     * @return bool
     */
    public function canExecute(float $requiredQuantity): bool
    {
        if (!$this->lot_number) {
            return false;
        }
        
        if ($this->isLotDepleted()) {
            return false;
        }
        
        return $this->getLotQuantity() >= $requiredQuantity;
    }
    
    /**
     * Mark the lot as depleted with timestamp.
     * 
     * @return void
     */
    public function markLotDepleted(): void
    {
        $this->lot_depleted_at = now();
        $this->save();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Recipe $recipe) {
            // Auto-generate recipe name if not set or if key fields changed
            $recipe->generateRecipeName();
        });
    }

    /**
     * Generate recipe name from variety, cultivar, seed density, DTM, and lot number.
     * Format: Variety (Cultivar) - Seed Density - DTM - LOT NO
     */
    public function generateRecipeName(): void
    {
        // Extract variety and cultivar from the consumable based on lot_number
        if ($this->lot_number) {
            $consumable = \App\Models\Consumable::where('type', 'seed')
                ->where('lot_no', $this->lot_number)
                ->where('is_active', true)
                ->first();
            
            if ($consumable && $consumable->name) {
                // Parse consumable name format: "Variety (Cultivar)"
                if (preg_match('/^(.+?)\s*\((.+?)\)$/', $consumable->name, $matches)) {
                    $variety = trim($matches[1]);
                    $cultivar = trim($matches[2]);
                    
                    $nameParts = [];
                    
                    // Add variety (cultivar) part
                    $nameParts[] = $variety . ' (' . $cultivar . ')';
                    
                    // Add seed density if available
                    if ($this->seed_density_grams_per_tray) {
                        $nameParts[] = $this->seed_density_grams_per_tray . 'G';
                    }
                    
                    // Add DTM if available
                    if ($this->days_to_maturity) {
                        $nameParts[] = $this->days_to_maturity . ' DTM';
                    }
                    
                    // Add lot number
                    $nameParts[] = $this->lot_number;
                    
                    $baseName = implode(' - ', $nameParts);
                    $this->name = $this->ensureUniqueRecipeName($baseName);
                    
                    // Also populate the common_name and cultivar_name fields for consistency
                    $this->common_name = $variety;
                    $this->cultivar_name = $cultivar;
                }
            }
        }
    }

    /**
     * Ensure the recipe name is unique by appending a number if necessary.
     * 
     * @param string $baseName The base name to make unique
     * @return string The unique name
     */
    protected function ensureUniqueRecipeName(string $baseName): string
    {
        $originalName = $baseName;
        $counter = 1;
        
        // Check if this exact name already exists (excluding current record if updating)
        while ($this->nameExists($baseName)) {
            $counter++;
            $baseName = $originalName . ' (' . $counter . ')';
        }
        
        return $baseName;
    }

    /**
     * Check if a recipe name already exists in the database.
     * 
     * @param string $name The name to check
     * @return bool Whether the name exists
     */
    protected function nameExists(string $name): bool
    {
        $query = static::where('name', $name);
        
        // If this is an update (record exists), exclude current record from check
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }
        
        return $query->exists();
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'seed_entry_id', 
                'lot_number',
                'lot_depleted_at',
                'supplier_soil_id', 
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
