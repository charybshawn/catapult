<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type', // soil, seed, consumable
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'notes',
        'is_active',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the recipes where this supplier provides soil.
     */
    public function soilRecipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'supplier_soil_id');
    }
    
    /**
     * Get the seed varieties from this supplier.
     */
    public function seedVarieties(): HasMany
    {
        return $this->hasMany(SeedVariety::class);
    }
    
    /**
     * Get the inventory items from this supplier.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'contact_name', 'contact_email', 'contact_phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
