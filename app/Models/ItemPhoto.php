<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ItemPhoto extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_id',
        'photo',
        'order',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the item that owns the photo.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'item_id',
                'photo',
                'order',
                'is_default',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Set this photo as the default for its item, removing default status from other photos.
     */
    public function setAsDefault(): self
    {
        // First, remove default flag from all other photos for this item
        static::where('item_id', $this->item_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
            
        // Then set this one as default
        $this->is_default = true;
        $this->save();
        
        return $this;
    }
}
