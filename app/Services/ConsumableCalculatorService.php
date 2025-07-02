<?php

namespace App\Services;

use App\Models\Consumable;

class ConsumableCalculatorService
{
    /**
     * Valid measurement units for consumables
     */
    private const MEASUREMENT_UNITS = [
        'g' => 'Grams',
        'kg' => 'Kilograms', 
        'oz' => 'Ounces',
        'lb' => 'Pounds',
        'ml' => 'Milliliters',
        'L' => 'Liters',
        'tsp' => 'Teaspoons',
        'tbsp' => 'Tablespoons',
        'cup' => 'Cups'
    ];

    /**
     * Valid consumable types
     */
    private const TYPES = [
        'packaging' => 'Packaging',
        'soil' => 'Soil',
        'seed' => 'Seed',
        'label' => 'Label',
        'other' => 'Other'
    ];

    /**
     * Valid unit types for different consumables
     */
    private const UNIT_TYPES = [
        'pieces' => 'Pieces',
        'rolls' => 'Rolls', 
        'bags' => 'Bags',
        'containers' => 'Containers',
        'packages' => 'Packages',
        'bottles' => 'Bottles',
        'sheets' => 'Sheets'
    ];

    /**
     * Calculate available stock for a consumable
     */
    public function calculateAvailableStock(Consumable $consumable): float
    {
        return max(0, $consumable->initial_stock - $consumable->consumed_quantity);
    }

    /**
     * Calculate total quantity based on available stock and quantity per unit
     */
    public function calculateTotalQuantity(Consumable $consumable): float
    {
        $availableStock = $this->calculateAvailableStock($consumable);
        
        if (!$consumable->quantity_per_unit) {
            return $availableStock;
        }

        return $availableStock * $consumable->quantity_per_unit;
    }

    /**
     * Calculate cost per gram/unit for comparison
     */
    public function calculateCostPerGram(Consumable $consumable): ?float
    {
        if (!$consumable->cost_per_unit || !$consumable->quantity_per_unit) {
            return null;
        }

        // Convert to cost per gram for standardized comparison
        $quantityInGrams = $this->convertToGrams($consumable->quantity_per_unit, $consumable->quantity_unit);
        
        if (!$quantityInGrams) {
            return null;
        }

        return $consumable->cost_per_unit / $quantityInGrams;
    }

    /**
     * Calculate usage rate (consumption per day) based on recent activity
     */
    public function calculateUsageRate(Consumable $consumable, int $days = 30): float
    {
        // This would require activity log analysis - simplified for now
        if ($consumable->consumed_quantity <= 0) {
            return 0;
        }

        // Estimate based on created date and consumed quantity
        $daysActive = max(1, $consumable->created_at->diffInDays(now()));
        return $consumable->consumed_quantity / $daysActive;
    }

    /**
     * Calculate estimated days until restock needed
     */
    public function calculateDaysUntilRestock(Consumable $consumable): ?int
    {
        $availableStock = $this->calculateAvailableStock($consumable);
        $usageRate = $this->calculateUsageRate($consumable);

        if ($usageRate <= 0) {
            return null; // No usage pattern to calculate from
        }

        $stockUntilReorder = $availableStock - $consumable->restock_threshold;
        
        if ($stockUntilReorder <= 0) {
            return 0; // Already needs restock
        }

        return (int) ceil($stockUntilReorder / $usageRate);
    }

    /**
     * Get all valid measurement units
     */
    public function getValidMeasurementUnits(): array
    {
        return self::MEASUREMENT_UNITS;
    }

    /**
     * Get all valid consumable types
     */
    public function getValidTypes(): array
    {
        return self::TYPES;
    }

    /**
     * Get all valid unit types
     */
    public function getValidUnitTypes(): array
    {
        return self::UNIT_TYPES;
    }

    /**
     * Convert quantity to grams for standardized calculations
     */
    private function convertToGrams(float $quantity, ?string $unit): ?float
    {
        if (!$unit) {
            return null;
        }

        return match ($unit) {
            'g' => $quantity,
            'kg' => $quantity * 1000,
            'oz' => $quantity * 28.3495,
            'lb' => $quantity * 453.592,
            'ml' => $quantity, // Assume 1ml ≈ 1g for calculation purposes
            'L' => $quantity * 1000,
            default => null // Cannot convert non-weight units
        };
    }

    /**
     * Format display name for consumable
     */
    public function formatDisplayName(Consumable $consumable): string
    {
        if ($consumable->consumableType && $consumable->consumableType->isSeed()) {
            // Seed consumables no longer have direct cultivar relationship
            return $consumable->name;
        }
        
        return $consumable->name;
    }

    /**
     * Calculate reorder suggestion based on usage patterns
     */
    public function calculateReorderSuggestion(Consumable $consumable): array
    {
        $availableStock = $this->calculateAvailableStock($consumable);
        $usageRate = $this->calculateUsageRate($consumable);
        $daysUntilRestock = $this->calculateDaysUntilRestock($consumable);

        $suggestion = [
            'needs_reorder' => $availableStock <= $consumable->restock_threshold,
            'current_stock' => $availableStock,
            'restock_threshold' => $consumable->restock_threshold,
            'suggested_quantity' => $consumable->restock_quantity,
            'usage_rate_per_day' => $usageRate,
            'days_until_restock' => $daysUntilRestock,
            'urgency' => $this->calculateUrgency($daysUntilRestock)
        ];

        return $suggestion;
    }

    /**
     * Calculate urgency level based on days until restock
     */
    private function calculateUrgency(?int $daysUntilRestock): string
    {
        if ($daysUntilRestock === null) {
            return 'unknown';
        }

        if ($daysUntilRestock <= 0) {
            return 'critical';
        }

        if ($daysUntilRestock <= 7) {
            return 'high';
        }

        if ($daysUntilRestock <= 30) {
            return 'medium';
        }

        return 'low';
    }
}