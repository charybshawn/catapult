<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeCardStatus extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function timeCards(): HasMany
    {
        return $this->hasMany(TimeCard::class);
    }

    /**
     * Get status by name
     */
    public static function getByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    /**
     * Get the active status
     */
    public static function active(): ?self
    {
        return static::getByName('active');
    }

    /**
     * Get the completed status
     */
    public static function completed(): ?self
    {
        return static::getByName('completed');
    }

    /**
     * Get the cancelled status
     */
    public static function cancelled(): ?self
    {
        return static::getByName('cancelled');
    }
}