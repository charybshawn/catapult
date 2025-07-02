<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStockStatus extends Model
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

    /**
     * Get the products for this stock status.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'stock_status_id');
    }

    /**
     * Get options for select fields (active statuses only).
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all active stock statuses.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find stock status by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is in stock status.
     */
    public function isInStock(): bool
    {
        return $this->code === 'in_stock';
    }

    /**
     * Check if this is low stock status.
     */
    public function isLowStock(): bool
    {
        return $this->code === 'low_stock';
    }

    /**
     * Check if this is out of stock status.
     */
    public function isOutOfStock(): bool
    {
        return $this->code === 'out_of_stock';
    }

    /**
     * Check if this is discontinued status.
     */
    public function isDiscontinued(): bool
    {
        return $this->code === 'discontinued';
    }

    /**
     * Check if this status indicates available inventory.
     */
    public function hasInventory(): bool
    {
        return in_array($this->code, ['in_stock', 'low_stock']);
    }

    /**
     * Check if this status should be shown to customers.
     */
    public function isVisibleToCustomers(): bool
    {
        return !$this->isDiscontinued();
    }
}