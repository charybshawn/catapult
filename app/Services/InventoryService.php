<?php

namespace App\Services;

use App\Models\Consumable;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Check if a consumable needs restocking
     */
    public function needsRestock(Consumable $consumable): bool
    {
        $currentStock = $this->getCurrentStock($consumable);
        return $currentStock <= $consumable->restock_threshold;
    }

    /**
     * Check if a consumable is out of stock
     */
    public function isOutOfStock(Consumable $consumable): bool
    {
        return $this->getCurrentStock($consumable) <= 0;
    }

    /**
     * Get current available stock for a consumable
     */
    public function getCurrentStock(Consumable $consumable): float
    {
        // For seeds, return the total_quantity directly 
        if ($consumable->consumableType && $consumable->consumableType->isSeed()) {
            return $consumable->total_quantity;
        }
        
        // For other consumables, use the original calculation
        return max(0, $consumable->initial_stock - $consumable->consumed_quantity);
    }

    /**
     * Calculate total value of consumable inventory
     */
    public function calculateTotalValue(Consumable $consumable): float
    {
        $currentStock = $this->getCurrentStock($consumable);
        return $currentStock * $consumable->cost_per_unit;
    }

    /**
     * Deduct quantity from consumable stock
     */
    public function deductStock(Consumable $consumable, float $amount, ?string $unit = null): void
    {
        $normalizedAmount = $this->normalizeQuantity($consumable, $amount, $unit);
        
        if ($consumable->consumableType && $consumable->consumableType->isSeed()) {
            $this->deductSeedStock($consumable, $normalizedAmount);
        } else {
            $this->deductGeneralStock($consumable, $normalizedAmount);
        }

        Log::info('Stock deducted from consumable', [
            'consumable_id' => $consumable->id,
            'amount' => $amount,
            'unit' => $unit,
            'normalized_amount' => $normalizedAmount
        ]);
    }

    /**
     * Add quantity to consumable stock
     */
    public function addStock(Consumable $consumable, float $amount, ?string $unit = null, ?string $lotNo = null): bool
    {
        $normalizedAmount = $this->normalizeQuantity($consumable, $amount, $unit);
        
        // For seed consumables, check lot number compatibility
        if ($consumable->consumableType && $consumable->consumableType->isSeed() && $lotNo !== null) {
            if (!$this->isLotNumberCompatible($consumable, $lotNo)) {
                return false; // Indicates new record should be created
            }
        }

        $this->performStockAddition($consumable, $normalizedAmount, $lotNo);

        Log::info('Stock added to consumable', [
            'consumable_id' => $consumable->id,
            'amount' => $amount,
            'unit' => $unit,
            'lot_no' => $lotNo
        ]);

        return true;
    }

    /**
     * Get formatted total weight display
     */
    public function getFormattedTotalWeight(Consumable $consumable): string
    {
        if (!$consumable->total_quantity || !$consumable->quantity_unit) {
            return 'N/A';
        }

        $quantity = $consumable->total_quantity;
        $unit = $consumable->quantity_unit;

        // Convert to most appropriate unit for display
        if ($unit === 'g' && $quantity >= 1000) {
            return number_format($quantity / 1000, 2) . ' kg';
        }

        if ($unit === 'ml' && $quantity >= 1000) {
            return number_format($quantity / 1000, 2) . ' L';
        }

        return number_format($quantity, 2) . ' ' . $unit;
    }

    /**
     * Normalize quantity to base unit for calculations
     */
    private function normalizeQuantity(Consumable $consumable, float $amount, ?string $unit): float
    {
        if (!$unit || $unit === $consumable->quantity_unit) {
            return $amount;
        }

        // Handle common unit conversions
        return match ([$unit, $consumable->quantity_unit]) {
            ['kg', 'g'] => $amount * 1000,
            ['g', 'kg'] => $amount / 1000,
            ['L', 'ml'] => $amount * 1000,
            ['ml', 'L'] => $amount / 1000,
            ['oz', 'g'] => $amount * 28.3495,
            ['g', 'oz'] => $amount / 28.3495,
            default => $amount // No conversion needed or unknown units
        };
    }

    /**
     * Deduct stock for seed consumables
     */
    private function deductSeedStock(Consumable $consumable, float $normalizedAmount): void
    {
        $data = [
            'total_quantity' => max(0, $consumable->total_quantity - $normalizedAmount),
            'consumed_quantity' => $consumable->consumed_quantity + $normalizedAmount,
        ];
        
        $consumable->update($data);
    }

    /**
     * Deduct stock for general consumables
     */
    private function deductGeneralStock(Consumable $consumable, float $normalizedAmount): void
    {
        $newConsumedQuantity = $consumable->consumed_quantity + $normalizedAmount;
        
        $data = [
            'consumed_quantity' => $newConsumedQuantity,
        ];
        
        // Update total quantity if applicable
        if ($consumable->quantity_per_unit) {
            $availableStock = max(0, $consumable->initial_stock - $newConsumedQuantity);
            $data['total_quantity'] = $availableStock * $consumable->quantity_per_unit;
        }
        
        $consumable->update($data);
    }

    /**
     * Check if lot number is compatible with existing stock
     */
    private function isLotNumberCompatible(Consumable $consumable, string $lotNo): bool
    {
        // If consumable already has a lot number and it's different, not compatible
        if ($consumable->lot_no && $consumable->lot_no !== $lotNo) {
            return false;
        }

        return true;
    }

    /**
     * Perform the actual stock addition
     */
    private function performStockAddition(Consumable $consumable, float $normalizedAmount, ?string $lotNo): void
    {
        $data = [
            'initial_stock' => $consumable->initial_stock + $normalizedAmount,
        ];

        if ($consumable->consumableType && $consumable->consumableType->isSeed()) {
            $data['total_quantity'] = $consumable->total_quantity + $normalizedAmount;
            
            if ($lotNo) {
                $data['lot_no'] = $lotNo;
            }
        } else {
            // Update total quantity if applicable
            if ($consumable->quantity_per_unit) {
                $availableStock = max(0, $data['initial_stock'] - $consumable->consumed_quantity);
                $data['total_quantity'] = $availableStock * $consumable->quantity_per_unit;
            }
        }

        $consumable->update($data);
    }

    /**
     * Get count of consumables that need restocking
     */
    public function getLowStockCount(): int
    {
        return Consumable::whereRaw('
            CASE 
                WHEN consumable_type_id = (SELECT id FROM consumable_types WHERE code = "seed") THEN total_quantity <= restock_threshold
                ELSE (initial_stock - consumed_quantity) <= restock_threshold
            END
        ')->count();
    }

    /**
     * Get consumables that need restocking
     */
    public function getLowStockItems($limit = null)
    {
        $query = Consumable::whereRaw('
            CASE 
                WHEN consumable_type_id = (SELECT id FROM consumable_types WHERE code = "seed") THEN total_quantity <= restock_threshold
                ELSE (initial_stock - consumed_quantity) <= restock_threshold
            END
        ')->orderByRaw('
            CASE 
                WHEN consumable_type_id = (SELECT id FROM consumable_types WHERE code = "seed") THEN (total_quantity / NULLIF(restock_threshold, 0))
                ELSE ((initial_stock - consumed_quantity) / NULLIF(restock_threshold, 0))
            END ASC
        ');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}