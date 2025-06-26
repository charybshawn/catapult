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
        // Normalize to prevent duplicates (trim whitespace and remove case-sensitive duplicates)
        $cultivarNames = array_filter($this->cultivars);
        $cultivarNames = array_unique(array_map('trim', $cultivarNames));
        
        // Remove any duplicate values that differ only in case
        $uniqueCultivars = [];
        $lowerCaseTracker = [];
        foreach ($cultivarNames as $cultivar) {
            $lowerCase = strtolower($cultivar);
            if (!isset($lowerCaseTracker[$lowerCase])) {
                $uniqueCultivars[] = $cultivar;
                $lowerCaseTracker[$lowerCase] = true;
            }
        }
        $cultivarNames = $uniqueCultivars;

        // Get existing cultivars for this catalog
        $existingCultivars = $this->cultivars()->pluck('name')->toArray();

        // Create new cultivars that don't exist
        foreach ($cultivarNames as $cultivarName) {
            if (!empty($cultivarName) && !in_array($cultivarName, $existingCultivars)) {
                try {
                    $this->cultivars()->create([
                        'name' => $cultivarName,
                        'is_active' => true,
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    // Skip if already exists (race condition or case sensitivity issue)
                    \Log::warning("Cultivar '{$cultivarName}' already exists for catalog ID {$this->id}");
                }
            }
        }

        // Remove cultivars that are no longer in the list
        if (!empty($cultivarNames)) {
            $this->cultivars()
                ->whereNotIn('name', $cultivarNames)
                ->delete();
        }
    }

    protected static function booted()
    {
        static::saved(function ($catalog) {
            $catalog->syncCultivars();
        });
    }
}
