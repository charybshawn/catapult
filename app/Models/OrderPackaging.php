<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderPackaging extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'packaging_type_id',
        'quantity',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the order for this packaging relationship.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the packaging type for this packaging relationship.
     */
    public function packagingType(): BelongsTo
    {
        return $this->belongsTo(PackagingType::class);
    }

    /**
     * Get the total volume with unit.
     */
    public function getTotalVolumeAttribute(): string
    {
        return number_format($this->quantity * $this->packagingType->capacity_volume, 2) . ' ' . 
            $this->packagingType->volume_unit;
    }

    /**
     * Get the total cost.
     */
    public function getTotalCostAttribute(): float
    {
        return $this->quantity * $this->packagingType->cost_per_unit;
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'packaging_type_id', 'quantity', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
