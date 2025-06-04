<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductPhoto extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_photos';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'photo',
        'is_default',
        'order',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];
    
    /**
     * Get the product that owns the photo.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * Set this photo as the default photo.
     * Ensures only one photo is set as default.
     */
    public function setAsDefault(): void
    {
        if (!$this->is_default) {
            // Clear any existing default photos
            static::where('product_id', $this->product_id)
                ->where('id', '!=', $this->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            
            // Set this photo as default
            $this->is_default = true;
            $this->save();
        }
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'photo', 'is_default', 'order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
} 