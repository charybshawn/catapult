<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterSeedCatalog extends Model
{
    protected $table = 'master_seed_catalog';

    protected $fillable = [
        'common_name',
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
        if (! empty($this->cultivars) && is_array($this->cultivars)) {
            return $this->cultivars[0];
        }

        // Fallback to existing relationship for backward compatibility
        return $this->cultivar?->cultivar_name;
    }

    /**
     * Parse combined value format: {catalog_id}:{cultivar_name}
     */
    public static function parseCombinedValue(string $combinedValue): array
    {
        $parts = explode(':', $combinedValue, 2);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid combined value format. Expected: catalog_id:cultivar_name');
        }

        return [
            'catalog_id' => (int) $parts[0],
            'cultivar_name' => $parts[1],
        ];
    }
}
