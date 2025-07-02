<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    // Type check methods
    public function isStandard(): bool { return $this->code === 'standard'; }
    public function isSubscription(): bool { return $this->code === 'subscription'; }
    public function isB2B(): bool { return $this->code === 'b2b'; }
}