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
        'cultivars',
        'category',
        'aliases',
        'growing_notes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'cultivars' => 'array',
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
     * Get the cultivar name - check JSON column first, fallback to relationship
     */
    public function getCultivarNameAttribute(): ?string
    {
        // Check new JSON cultivars column first
        if (!empty($this->cultivars) && is_array($this->cultivars)) {
            return $this->cultivars[0];
        }

        // Fallback to existing relationship for backward compatibility
        return $this->cultivar?->cultivar_name;
    }

    /**
     * Get combined seed catalog + cultivar options for select fields
     */
    public static function getCombinedSelectOptions(): array
    {
        $options = [];

        $catalogs = static::where('is_active', true)->get();

        foreach ($catalogs as $catalog) {
            $cultivars = $catalog->cultivars ?? [];

            if (!empty($cultivars) && is_array($cultivars)) {
                foreach ($cultivars as $cultivar) {
                    $displayName = "{$catalog->common_name} ({$cultivar})";
                    $value = "{$catalog->id}:{$cultivar}";
                    $options[$value] = $displayName;
                }
            } else {
                // Fallback for entries without cultivars
                $options["{$catalog->id}:"] = $catalog->common_name;
            }
        }

        return $options;
    }

    /**
     * Parse combined value and return catalog ID and cultivar name
     */
    public static function parseCombinedValue(string $value): array
    {
        [$catalogId, $cultivarName] = explode(':', $value, 2);
        return [
            'catalog_id' => (int) $catalogId,
            'cultivar_name' => $cultivarName ?: null,
            'catalog' => static::find($catalogId)
        ];
    }

    /**
     * Generate display name from catalog and cultivar
     */
    public function getDisplayNameWithCultivar(?string $cultivar = null): string
    {
        return $cultivar
            ? "{$this->common_name} ({$cultivar})"
            : $this->common_name;
    }

}
