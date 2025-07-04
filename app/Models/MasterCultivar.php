<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterCultivar extends Model
{
    protected $fillable = [
        'master_seed_catalog_id',
        'cultivar_name',
        'aliases',
        'description',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_active' => 'boolean',
    ];

    public function masterSeedCatalog(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->masterSeedCatalog->common_name . ' (' . $this->cultivar_name . ')';
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }
}
