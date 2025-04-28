<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecipeStage extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
        'stage', // germination, blackout, light
        'notes',
        'temperature_min_celsius',
        'temperature_max_celsius',
        'humidity_min_percent',
        'humidity_max_percent',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'temperature_min_celsius' => 'float',
        'temperature_max_celsius' => 'float',
        'humidity_min_percent' => 'integer',
        'humidity_max_percent' => 'integer',
    ];
    
    /**
     * Get the recipe for this stage.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'recipe_id', 
                'stage', 
                'notes',
                'temperature_min_celsius',
                'temperature_max_celsius',
                'humidity_min_percent',
                'humidity_max_percent'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
