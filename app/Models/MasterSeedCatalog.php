<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function cultivars(): HasMany
    {
        return $this->hasMany(MasterCultivar::class);
    }
    
    public function primaryCultivar(): HasOne
    {
        return $this->hasOne(MasterCultivar::class);
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
     * Get the cultivar name from the primary related MasterCultivar
     */
    public function getCultivarNameAttribute(): ?string
    {
        return $this->primaryCultivar?->cultivar_name;
    }
}
