<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderClassification extends Model
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

    // Classification check methods
    public function isScheduled(): bool { return $this->code === 'scheduled'; }
    public function isOnDemand(): bool { return $this->code === 'ondemand'; }
    public function isOverflow(): bool { return $this->code === 'overflow'; }
    public function isPriority(): bool { return $this->code === 'priority'; }
}