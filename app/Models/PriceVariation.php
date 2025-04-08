<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PriceVariation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_id',
        'name',
        'unit',
        'sku',
        'weight',
        'price',
        'is_default',
        'is_global',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'weight' => 'float',
        'price' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($priceVariation) {
            // Ensure weight is never null
            if (is_null($priceVariation->weight)) {
                $priceVariation->weight = 0;
            }
            
            // Handle default pricing
            if ($priceVariation->is_default) {
                static::where('item_id', $priceVariation->item_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
        
        static::updating(function ($priceVariation) {
            // Ensure weight is never null
            if (is_null($priceVariation->weight)) {
                $priceVariation->weight = 0;
            }
            
            // Handle default pricing
            if ($priceVariation->is_default && $priceVariation->isDirty('is_default')) {
                static::where('item_id', $priceVariation->item_id)
                    ->where('id', '!=', $priceVariation->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
        
        // Ensure there's always a default price if possible
        static::deleted(function ($priceVariation) {
            if ($priceVariation->is_default) {
                $firstVariation = static::where('item_id', $priceVariation->item_id)->first();
                if ($firstVariation) {
                    $firstVariation->update(['is_default' => true]);
                }
            }
        });
    }

    /**
     * Get the item that owns the price variation.
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
                'name',
                'unit',
                'sku',
                'weight',
                'price',
                'is_default',
                'is_global',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
