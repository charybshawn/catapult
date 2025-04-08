<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecipeMix extends Pivot
{
    use HasFactory, LogsActivity;
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recipe_mixes';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
        'component_recipe_id',
        'percentage',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'percentage' => 'float',
    ];
    
    /**
     * Get the mix recipe.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }
    
    /**
     * Get the component recipe.
     */
    public function componentRecipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'component_recipe_id');
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['recipe_id', 'component_recipe_id', 'percentage'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
