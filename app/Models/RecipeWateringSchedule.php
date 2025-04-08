<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecipeWateringSchedule extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recipe_watering_schedule';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
        'day_number',
        'water_amount_ml',
        'needs_liquid_fertilizer',
        'watering_method', // bottom, top, mist
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'day_number' => 'integer',
        'water_amount_ml' => 'integer',
        'needs_liquid_fertilizer' => 'boolean',
    ];
    
    /**
     * Get the recipe for this watering schedule.
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
                'day_number', 
                'water_amount_ml',
                'needs_liquid_fertilizer',
                'watering_method',
                'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
