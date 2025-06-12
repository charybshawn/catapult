<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterSeedCatalog extends Model
{
    protected $table = 'master_seed_catalog';
    
    protected $fillable = [
        'common_name',
        'cultivars', // Store cultivars as JSON array
        'category',
        'aliases',
        'growing_notes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'cultivars' => 'array', // Store cultivars as JSON array
        'is_active' => 'boolean',
    ];

    public function cultivars(): HasMany
    {
        return $this->hasMany(MasterCultivar::class);
    }

    public function activeCultivars(): HasMany
    {
        return $this->hasMany(MasterCultivar::class)->where('is_active', true);
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
     * Sync cultivar names with MasterCultivar records
     */
    public function syncCultivars()
    {
        if (!$this->cultivars || !is_array($this->cultivars)) {
            return;
        }

        // Get current cultivar names from the cultivars field
        $cultivarNames = array_filter($this->cultivars);

        // Get existing cultivars for this catalog
        $existingCultivars = $this->cultivars()->pluck('cultivar_name')->toArray();

        // Create new cultivars that don't exist
        foreach ($cultivarNames as $cultivarName) {
            if (!in_array($cultivarName, $existingCultivars)) {
                $this->cultivars()->create([
                    'cultivar_name' => $cultivarName,
                    'is_active' => true,
                ]);
            }
        }

        // Remove cultivars that are no longer in the list
        $this->cultivars()
            ->whereNotIn('cultivar_name', $cultivarNames)
            ->delete();
    }

    protected static function booted()
    {
        static::saved(function ($catalog) {
            $catalog->syncCultivars();
        });
    }
}
