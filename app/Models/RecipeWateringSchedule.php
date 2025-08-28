<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents irrigation scheduling for agricultural recipe cultivation processes,
 * defining day-by-day watering requirements, amounts, methods, and fertilizer
 * needs for microgreens production. Critical for consistent agricultural yields.
 *
 * @business_domain Agricultural Irrigation Management & Cultivation Scheduling
 * @workflow_context Used in recipe development, crop cultivation, and production planning
 * @agricultural_process Defines precise watering schedules for microgreens cultivation
 *
 * Database Table: recipe_watering_schedule
 * @property int $id Primary identifier for watering schedule entry
 * @property int $recipe_id Reference to parent cultivation recipe
 * @property int $day_number Day number in cultivation cycle (1, 2, 3, etc.)
 * @property int $water_amount_ml Water amount in milliliters for this day
 * @property bool $needs_liquid_fertilizer Whether liquid fertilizer is required
 * @property string $watering_method Watering technique (bottom, top, mist)
 * @property string|null $notes Additional watering instructions or observations
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship recipe BelongsTo relationship to Recipe for cultivation context
 *
 * @business_rule Each day in cultivation cycle has specific watering requirements
 * @business_rule Watering methods affect absorption and agricultural outcomes
 * @business_rule Fertilizer timing is critical for microgreens development
 *
 * @agricultural_methods Bottom watering (sub-irrigation), Top watering (spray), Mist (fine spray)
 * @cultivation_science Precise water amounts ensure consistent agricultural production
 */
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
     * Get the agricultural recipe this watering schedule belongs to.
     * Links irrigation schedule to specific cultivation recipe.
     *
     * @return BelongsTo Recipe relationship
     * @agricultural_context Connects watering requirements to cultivation methodology
     * @business_usage Used in recipe management and cultivation planning
     * @cultivation_workflow Enables day-by-day irrigation guidance for crops
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Configure activity logging for watering schedule changes.
     * Tracks modifications to critical agricultural irrigation data.
     *
     * @return LogOptions Activity logging configuration
     * @audit_purpose Maintains history of irrigation schedule changes for cultivation tracking
     * @logged_fields Tracks recipe, day, water amount, fertilizer needs, method, and notes
     * @business_usage Used for cultivation auditing and irrigation optimization analysis
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
