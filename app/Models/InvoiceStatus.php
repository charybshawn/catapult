<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceStatus extends Model
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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
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

    // Status check methods
    public function isDraft(): bool { return $this->code === 'draft'; }
    public function isSent(): bool { return $this->code === 'sent'; }
    public function isPaid(): bool { return $this->code === 'paid'; }
    public function isOverdue(): bool { return $this->code === 'overdue'; }
    public function isCancelled(): bool { return $this->code === 'cancelled'; }
}