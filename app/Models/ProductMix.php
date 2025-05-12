<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Get the seed varieties that make up this mix.
     */
    public function seedVarieties(): BelongsToMany
    {
        return $this->belongsToMany(SeedVariety::class, 'product_mix_components')
            ->withPivot('percentage')
            ->withTimestamps();
    }
    
    /**
     * Calculate the number of trays needed for each variety in this mix.
     *
     * @param int $totalTrays The total number of trays for this mix
     * @return array Array of [variety_id => trays_needed]
     */
    public function calculateVarietyTrays(int $totalTrays): array
    {
        $varietyTrays = [];
        
        foreach ($this->seedVarieties as $variety) {
            $percentage = $variety->pivot->percentage;
            $trays = ceil(($percentage / 100) * $totalTrays);
            $varietyTrays[$variety->id] = $trays;
        }
        
        return $varietyTrays;
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