<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only model for the crop_batches_list_view
 * This model is optimized for displaying crop batches in tables with minimal queries
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
     * Get tray numbers as array
     */
    public function getTrayNumbersArrayAttribute(): array
    {
        if (empty($this->tray_numbers)) {
            return [];
        }
        
        return array_map('trim', explode(',', $this->tray_numbers));
    }
    
    /**
     * Scope for active crops (not harvested)
     */
    public function scopeActive($query)
    {
        return $query->where('current_stage_code', '!=', 'harvested');
    }
    
    /**
     * Scope for harvested crops
     */
    public function scopeHarvested($query)
    {
        return $query->where('current_stage_code', '=', 'harvested');
    }
    
    /**
     * Get the recipe relationship (for compatibility)
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
    
    /**
     * Get the crops for this batch
     * Since this is a view, we return a collection instead of a relationship
     */
    public function getCropsAttribute()
    {
        return \App\Models\Crop::where('crop_batch_id', $this->id)->get();
    }
    
    /**
     * For compatibility with code expecting a relationship
     * Returns a hasMany relationship that won't work perfectly but prevents errors
     */
    public function crops()
    {
        return $this->hasMany(\App\Models\Crop::class, 'crop_batch_id', 'id');
    }
}