<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'supplier_type_id',
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
     * Get the supplier type for this supplier.
     */
    public function supplierType(): BelongsTo
    {
        return $this->belongsTo(SupplierType::class);
    }

    /**
     * Get the recipes where this supplier provides soil.
     */
    public function soilRecipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'supplier_soil_id');
    }
    
    /**
     * Get the seed entries from this supplier.
     */
    public function seedEntries(): HasMany
    {
        return $this->hasMany(SeedEntry::class);
    }
    
    /**
     * Get the inventory items from this supplier.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Check if this supplier is a soil supplier.
     */
    public function isSoilSupplier(): bool
    {
        return $this->supplierType?->isSoil() ?? false;
    }

    /**
     * Check if this supplier is a seed supplier.
     */
    public function isSeedSupplier(): bool
    {
        return $this->supplierType?->isSeed() ?? false;
    }

    /**
     * Check if this supplier is a consumable supplier.
     */
    public function isConsumableSupplier(): bool
    {
        return $this->supplierType?->isConsumable() ?? false;
    }

    /**
     * Check if this supplier is a packaging supplier.
     */
    public function isPackagingSupplier(): bool
    {
        return $this->supplierType?->isPackaging() ?? false;
    }

    /**
     * Check if this supplier is an other supplier.
     */
    public function isOtherSupplier(): bool
    {
        return $this->supplierType?->isOther() ?? false;
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'supplier_type_id', 'contact_name', 'contact_email', 'contact_phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
