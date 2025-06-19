<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PackagingType extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'capacity_volume',
        'volume_unit',
        'description',
        'is_active',
        'cost_per_unit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity_volume' => 'float',
        'is_active' => 'boolean',
        'cost_per_unit' => 'float',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'display_name',
    ];

    /**
     * Get the display name which combines the base name and volume information.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        // Special handling for bulk and live tray products
        if ($this->name === 'Bulk') {
            return 'Bulk (by weight)';
        }
        
        if ($this->name === 'Live Tray') {
            return 'Live Tray';
        }
        
        return "{$this->name} - {$this->capacity_volume}{$this->volume_unit}";
    }

    /**
     * Get the order packagings for this packaging type.
     */
    public function orderPackagings(): HasMany
    {
        return $this->hasMany(OrderPackaging::class);
    }

    /**
     * Get the price variations that use this packaging type.
     */
    public function priceVariations(): HasMany
    {
        return $this->hasMany(PriceVariation::class);
    }

    /**
     * Check if this packaging type allows decimal quantities (weight-based).
     *
     * @return bool
     */
    public function allowsDecimalQuantity(): bool
    {
        return in_array(strtolower($this->name), ['bulk']);
    }

    /**
     * Get the appropriate quantity unit for this packaging type.
     *
     * @return string
     */
    public function getQuantityUnit(): string
    {
        if ($this->allowsDecimalQuantity()) {
            return 'grams';
        }
        
        return 'units';
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'capacity_volume', 'volume_unit', 'description', 'is_active', 'cost_per_unit'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
