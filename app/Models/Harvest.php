<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Harvest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'recipe_id',
        'user_id',
        'total_weight_grams',
        'tray_count',
        'harvest_date',
        'notes',
    ];

    protected $casts = [
        'total_weight_grams' => 'decimal:2',
        'tray_count' => 'decimal:2',
        'harvest_date' => 'date',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'crop_harvest')
            ->withPivot([
                'harvested_weight_grams',
                'percentage_harvested',
                'notes'
            ])
            ->withTimestamps();
    }

    public function getWeekStartDateAttribute(): Carbon
    {
        return $this->harvest_date->copy()->startOfWeek(Carbon::WEDNESDAY);
    }

    public function getWeekEndDateAttribute(): Carbon
    {
        return $this->harvest_date->copy()->endOfWeek(Carbon::TUESDAY);
    }

    public function getAverageWeightPerTrayAttribute(): float
    {
        return $this->tray_count > 0 ? $this->total_weight_grams / $this->tray_count : 0;
    }

    public function getVarietyNameAttribute(): string
    {
        if (!$this->relationLoaded('recipe')) {
            $this->load('recipe.masterSeedCatalog');
        }

        if (!$this->recipe) {
            return 'Unknown Variety';
        }

        $commonName = $this->recipe->masterSeedCatalog?->common_name ?? $this->recipe->common_name ?? 'Unknown';

        if ($this->recipe->cultivar_name) {
            return $commonName . ' (' . $this->recipe->cultivar_name . ')';
        }

        return $commonName;
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'recipe_id',
                'user_id',
                'total_weight_grams',
                'tray_count',
                'harvest_date',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
