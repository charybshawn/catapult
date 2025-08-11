<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MasterSeedCatalog extends Model
{
    protected $table = 'master_seed_catalog';
    
    protected $fillable = [
        'common_name',
        'cultivar_id',
        'category',
        'aliases',
        'growing_notes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_active' => 'boolean',
    ];

    public function cultivar(): BelongsTo
    {
        return $this->belongsTo(MasterCultivar::class, 'cultivar_id');
    }

    public function cultivars(): HasMany
    {
        return $this->hasMany(MasterCultivar::class, 'master_seed_catalog_id');
    }

    public function activeCultivars(): HasMany
    {
        return $this->hasMany(MasterCultivar::class, 'master_seed_catalog_id')->where('is_active', true);
    }
    
    public function primaryCultivar(): HasOne
    {
        return $this->hasOne(MasterCultivar::class, 'master_seed_catalog_id');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class);
    }

    public function seedEntries(): HasMany
    {
        return $this->hasMany(SeedEntry::class);
    }

    /**
     * Get the cultivar name from the related MasterCultivar
     */
    public function getCultivarNameAttribute(): ?string
    {
        return $this->cultivar?->cultivar_name;
    }
}
