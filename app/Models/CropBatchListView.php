<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only database view model for optimized crop batch listing operations.
 * 
 * Provides high-performance access to crop batch data through a database view that
 * pre-computes common attributes and joins. Optimized for table displays and reporting
 * with minimal database queries and improved performance over standard batch model.
 * 
 * @property int $id Primary key identifier from crop_batches table
 * @property int $recipe_id Recipe identifier for batch production
 * @property int $current_stage_id Current growth stage ID
 * @property string $current_stage_code Current stage code for filtering
 * @property string $current_stage_name Current stage display name
 * @property \Illuminate\Support\Carbon|null $planting_at Planting timestamp
 * @property \Illuminate\Support\Carbon|null $expected_harvest_at Expected harvest date
 * @property \Illuminate\Support\Carbon|null $watering_suspended_at Watering suspension timestamp
 * @property \Illuminate\Support\Carbon $created_at Batch creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property int $crop_count Number of crops in batch
 * @property string|null $tray_numbers Comma-separated tray numbers
 * @property int|null $stage_age_minutes Age in current stage (minutes)
 * @property int|null $time_to_next_stage_minutes Time to next stage (minutes)
 * @property int|null $total_age_minutes Total batch age (minutes)
 * @property-read array $tray_numbers_array Parsed array of tray numbers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Crop> $crops
 * @property-read \App\Models\Recipe $recipe
 * 
 * @agricultural_context Optimized for crop batch monitoring and production management
 * @performance_optimization Database view eliminates N+1 queries and expensive computations
 * @read_only Model represents database view, no modifications allowed
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class CropBatchListView extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'crop_batches_list_view';
    
    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'planting_at' => 'datetime',
        'expected_harvest_at' => 'datetime',
        'watering_suspended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'crop_count' => 'integer',
        'current_stage_id' => 'integer',
        'recipe_id' => 'integer',
        'stage_age_minutes' => 'integer',
        'time_to_next_stage_minutes' => 'integer',
        'total_age_minutes' => 'integer',
    ];
    
    /**
     * Get tray numbers as parsed array for display and operations.
     * 
     * Converts comma-separated tray numbers string from database view
     * into array format for UI display and batch operations.
     * 
     * @return array Array of tray numbers for this batch
     * @agricultural_context Tray numbers identify individual growing containers in batch
     * @ui_usage Used for displaying batch composition and tray management
     */
    public function getTrayNumbersArrayAttribute(): array
    {
        if (empty($this->tray_numbers)) {
            return [];
        }
        
        return array_map('trim', explode(',', $this->tray_numbers));
    }
    
    /**
     * Scope for active crop batches (not harvested).
     * 
     * Filters query to include only batches that are still in production
     * and have not reached the harvested stage.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder instance
     * @return \Illuminate\Database\Eloquent\Builder Filtered query for active batches
     * @agricultural_context Active batches require ongoing monitoring and care
     * @business_usage Used for production dashboards and operational views
     */
    public function scopeActive($query)
    {
        return $query->where('current_stage_code', '!=', 'harvested');
    }
    
    /**
     * Scope for harvested crop batches.
     * 
     * Filters query to include only batches that have completed production
     * and reached the harvested stage.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder instance
     * @return \Illuminate\Database\Eloquent\Builder Filtered query for harvested batches
     * @agricultural_context Harvested batches represent completed production cycles
     * @business_usage Used for harvest reports and production analytics
     */
    public function scopeHarvested($query)
    {
        return $query->where('current_stage_code', '=', 'harvested');
    }
    
    /**
     * Get the recipe relationship for batch production parameters.
     * 
     * Retrieves the recipe that defines growing parameters and timing
     * for this batch, providing compatibility with standard batch model.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Recipe>
     * @agricultural_context Recipe defines variety, timing, and growing parameters
     * @compatibility Maintains relationship compatibility with CropBatch model
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
    
    /**
     * Get the crops collection for this batch.
     * 
     * Since this is a database view, returns collection directly rather than
     * using standard Eloquent relationships for optimal performance.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Crop>
     * @agricultural_context Returns individual crop instances in this batch
     * @performance_note Direct query used since view cannot use standard relationships
     */
    public function getCropsAttribute()
    {
        return Crop::where('crop_batch_id', $this->id)->get();
    }
    
    /**
     * Get crops relationship for compatibility with standard batch model.
     * 
     * Provides hasMany relationship for compatibility with code expecting
     * standard Eloquent relationships, though limited by view constraints.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Crop>
     * @compatibility Maintains relationship compatibility with CropBatch model
     * @limitation Relationship functionality limited due to database view nature
     */
    public function crops()
    {
        return $this->hasMany(Crop::class, 'crop_batch_id', 'id');
    }
}