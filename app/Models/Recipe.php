<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        'seed_variety_id',
        'seed_consumable_id',
        'supplier_soil_id',
        'soil_consumable_id',
        'germination_days',
        'blackout_days',
        'light_days',
        'seed_soak_hours',
        'expected_yield_grams', 
        'seed_density_grams_per_tray',
        'is_active',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'germination_days' => 'float',
        'blackout_days' => 'float',
        'light_days' => 'float',
        'seed_soak_hours' => 'integer',
        'expected_yield_grams' => 'float',
        'seed_density_grams_per_tray' => 'float',
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the soil supplier for this recipe.
     */
    public function soilSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_soil_id');
    }
    
    /**
     * Get the seed variety for this recipe.
     */
    public function seedVariety(): BelongsTo
    {
        return $this->belongsTo(SeedVariety::class);
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
     * Calculate the total days from planting to harvest.
     */
    public function totalDays(): int
    {
        return $this->germination_days + $this->blackout_days + $this->light_days;
    }
    
    /**
     * Calculate days to harvest including seed soak time.
     */
    public function effectiveTotalDays(): float
    {
        return ($this->seed_soak_hours / 24) + $this->germination_days + $this->blackout_days + $this->light_days;
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'seed_variety_id', 
                'supplier_soil_id', 
                'germination_days', 
                'blackout_days', 
                'light_days',
                'expected_yield_grams',
                'seed_density_grams_per_tray',
                'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
