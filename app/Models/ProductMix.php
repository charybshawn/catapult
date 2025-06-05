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
     * Get the seed cultivars that make up this mix.
     */
    public function seedCultivars(): BelongsToMany
    {
        return $this->belongsToMany(SeedCultivar::class, 'product_mix_components')
            ->withPivot('percentage')
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
        
        foreach ($this->seedCultivars as $cultivar) {
            $percentage = $cultivar->pivot->percentage;
            $trays = ceil(($percentage / 100) * $totalTrays);
            $cultivarTrays[$cultivar->id] = $trays;
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
} 