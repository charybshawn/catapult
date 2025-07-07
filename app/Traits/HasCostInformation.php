<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasCostInformation
 *
 * Provides common functionality for models that track cost and price information.
 * This trait assumes the model has cost/price related fields like:
 * - cost_per_unit
 * - price
 * - base_price
 * - total_cost
 * - etc.
 *
 * @package App\Traits
 */
trait HasCostInformation
{
    /**
     * Initialize the trait for the model.
     *
     * @return void
     */
    public function initializeHasCostInformation(): void
    {
        // Common cost/price fields that might exist
        $costFields = [
            'cost_per_unit',
            'price',
            'base_price',
            'wholesale_price',
            'bulk_price',
            'special_price',
            'total_cost',
            'unit_cost',
        ];

        // Add cost fields to fillable if they exist on the model
        foreach ($costFields as $field) {
            if ($this->hasCostField($field) && !in_array($field, $this->fillable)) {
                $this->fillable[] = $field;
            }
        }

        // Add casting for cost fields
        foreach ($costFields as $field) {
            if ($this->hasCostField($field) && !isset($this->casts[$field])) {
                $this->casts[$field] = 'decimal:2';
            }
        }
    }

    /**
     * Check if a cost field exists on the model.
     *
     * @param string $field
     * @return bool
     */
    protected function hasCostField(string $field): bool
    {
        // Check if field is in fillable or if we can detect it from the database
        return in_array($field, $this->getFillable()) || 
               (method_exists($this, 'getConnection') && 
                $this->getConnection() && 
                $this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $field));
    }

    /**
     * Get the primary cost field name for this model.
     *
     * @return string
     */
    public function getCostFieldName(): string
    {
        // Prioritize common cost field names
        $priorityFields = ['cost_per_unit', 'unit_cost', 'cost', 'price'];
        
        foreach ($priorityFields as $field) {
            if ($this->hasCostField($field)) {
                return $field;
            }
        }
        
        return 'cost_per_unit'; // Default
    }

    /**
     * Get the primary price field name for this model.
     *
     * @return string
     */
    public function getPriceFieldName(): string
    {
        // Prioritize common price field names
        $priorityFields = ['price', 'base_price', 'unit_price'];
        
        foreach ($priorityFields as $field) {
            if ($this->hasCostField($field)) {
                return $field;
            }
        }
        
        return 'price'; // Default
    }

    /**
     * Scope a query to only include records with cost information.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithCost(Builder $query): Builder
    {
        $costField = $this->getCostFieldName();
        return $query->whereNotNull($costField)->where($costField, '>', 0);
    }

    /**
     * Scope a query to only include records without cost information.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutCost(Builder $query): Builder
    {
        $costField = $this->getCostFieldName();
        return $query->where(function ($q) use ($costField) {
            $q->whereNull($costField)->orWhere($costField, '<=', 0);
        });
    }

    /**
     * Scope a query to order by cost (ascending).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrderByCost(Builder $query): Builder
    {
        return $query->orderBy($this->getCostFieldName(), 'asc');
    }

    /**
     * Scope a query to order by cost (descending).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOrderByCostDesc(Builder $query): Builder
    {
        return $query->orderBy($this->getCostFieldName(), 'desc');
    }

    /**
     * Scope a query to filter by cost range.
     *
     * @param Builder $query
     * @param float $min
     * @param float $max
     * @return Builder
     */
    public function scopeCostBetween(Builder $query, float $min, float $max): Builder
    {
        $costField = $this->getCostFieldName();
        return $query->whereBetween($costField, [$min, $max]);
    }

    /**
     * Get the cost value.
     *
     * @return float
     */
    public function getCost(): float
    {
        $field = $this->getCostFieldName();
        return (float) ($this->getAttribute($field) ?? 0);
    }

    /**
     * Get the price value.
     *
     * @return float
     */
    public function getPrice(): float
    {
        $field = $this->getPriceFieldName();
        return (float) ($this->getAttribute($field) ?? 0);
    }

    /**
     * Set the cost value.
     *
     * @param float $cost
     * @return void
     */
    public function setCost(float $cost): void
    {
        $field = $this->getCostFieldName();
        $this->setAttribute($field, $cost);
    }

    /**
     * Set the price value.
     *
     * @param float $price
     * @return void
     */
    public function setPrice(float $price): void
    {
        $field = $this->getPriceFieldName();
        $this->setAttribute($field, $price);
    }

    /**
     * Calculate profit margin percentage.
     *
     * @return float
     */
    public function getProfitMarginAttribute(): float
    {
        $cost = $this->getCost();
        $price = $this->getPrice();
        
        if ($price <= 0) {
            return 0;
        }
        
        return round((($price - $cost) / $price) * 100, 2);
    }

    /**
     * Calculate markup percentage.
     *
     * @return float
     */
    public function getMarkupPercentageAttribute(): float
    {
        $cost = $this->getCost();
        $price = $this->getPrice();
        
        if ($cost <= 0) {
            return 0;
        }
        
        return round((($price - $cost) / $cost) * 100, 2);
    }

    /**
     * Calculate profit amount.
     *
     * @return float
     */
    public function getProfitAmountAttribute(): float
    {
        return $this->getPrice() - $this->getCost();
    }

    /**
     * Check if the item has a cost.
     *
     * @return bool
     */
    public function hasCost(): bool
    {
        return $this->getCost() > 0;
    }

    /**
     * Check if the item has a price.
     *
     * @return bool
     */
    public function hasPrice(): bool
    {
        return $this->getPrice() > 0;
    }

    /**
     * Check if the item is profitable.
     *
     * @return bool
     */
    public function isProfitable(): bool
    {
        return $this->getPrice() > $this->getCost();
    }

    /**
     * Format cost as currency.
     *
     * @return string
     */
    public function getFormattedCostAttribute(): string
    {
        return '$' . number_format($this->getCost(), 2);
    }

    /**
     * Format price as currency.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->getPrice(), 2);
    }
    
    /**
     * Format cost as currency with custom parameters.
     *
     * @param int $decimals
     * @param string $prefix
     * @return string
     */
    public function formatCost(int $decimals = 2, string $prefix = '$'): string
    {
        return $prefix . number_format($this->getCost(), $decimals);
    }

    /**
     * Format price as currency with custom parameters.
     *
     * @param int $decimals
     * @param string $prefix
     * @return string
     */
    public function formatPrice(int $decimals = 2, string $prefix = '$'): string
    {
        return $prefix . number_format($this->getPrice(), $decimals);
    }

    /**
     * Calculate total value based on quantity if available.
     *
     * @param string $quantityField
     * @return float
     */
    public function calculateTotalValue(string $quantityField = 'quantity'): float
    {
        if (!$this->hasAttribute($quantityField)) {
            return $this->getCost();
        }
        
        $quantity = (float) $this->getAttribute($quantityField);
        return $quantity * $this->getCost();
    }

    /**
     * Apply a discount percentage to the price.
     *
     * @param float $percentage
     * @return float
     */
    public function getPriceWithDiscount(float $percentage): float
    {
        $price = $this->getPrice();
        $discount = $price * ($percentage / 100);
        return max(0, $price - $discount);
    }

    /**
     * Apply a markup percentage to the cost.
     *
     * @param float $percentage
     * @return float
     */
    public function getCostWithMarkup(float $percentage): float
    {
        $cost = $this->getCost();
        $markup = $cost * ($percentage / 100);
        return $cost + $markup;
    }
}