<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SeedCultivar extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];
    
    /**
     * Get the seed entries for this cultivar
     */
    public function seedEntries(): HasMany
    {
        return $this->hasMany(SeedEntry::class);
    }
    
    /**
     * Get all suppliers that have entries for this cultivar
     */
    public function suppliers(): HasManyThrough
    {
        return $this->hasManyThrough(Supplier::class, SeedEntry::class, 'seed_cultivar_id', 'id', 'id', 'supplier_id')
            ->distinct();
    }
}
