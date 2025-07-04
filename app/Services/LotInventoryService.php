<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\ConsumableType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing lot-level inventory operations in the FIFO system.
 * 
 * This service handles lot-specific inventory operations for seed consumables,
 * supporting FIFO (First In, First Out) inventory management by tracking
 * individual lot quantities and providing methods to work with the oldest
 * stock first.
 */
class LotInventoryService
{
    /**
     * Get the seed consumable type ID dynamically.
     */
    private function getSeedConsumableTypeId(): ?int
    {
        return ConsumableType::where('code', 'seed')->value('id');
    }

    /**
     * Get total available quantity across all consumable entries for a specific lot.
     * 
     * @param string $lotNumber The lot number to check
     * @return float Total available quantity for the lot
     */
    public function getLotQuantity(string $lotNumber): float
    {
        $entries = $this->getEntriesInLot($lotNumber);
        
        return $entries->sum(function (Consumable $consumable) {
            return max(0, $consumable->total_quantity - $consumable->consumed_quantity);
        });
    }

    /**
     * Check if a lot is completely depleted (no available stock).
     * 
     * @param string $lotNumber The lot number to check
     * @return bool True if the lot is depleted, false otherwise
     */
    public function isLotDepleted(string $lotNumber): bool
    {
        $totalQuantity = $this->getLotQuantity($lotNumber);
        
        return $totalQuantity <= 0;
    }

    /**
     * Get the oldest consumable entry for a lot (by created_at).
     * 
     * This method is essential for FIFO operations, ensuring that the oldest
     * stock is used first when consuming inventory.
     * 
     * @param string $lotNumber The lot number to search
     * @return Consumable|null The oldest consumable entry or null if none found
     */
    public function getOldestEntryInLot(string $lotNumber): ?Consumable
    {
        $seedTypeId = $this->getSeedConsumableTypeId();
        if (!$seedTypeId) {
            return null;
        }

        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', strtoupper($lotNumber))
            ->where('is_active', true)
            ->whereRaw('(total_quantity - consumed_quantity) > 0') // Only entries with available stock
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Get all consumable entries for a lot ordered by age (oldest first).
     * 
     * @param string $lotNumber The lot number to search
     * @return Collection Collection of consumable entries
     */
    public function getEntriesInLot(string $lotNumber): Collection
    {
        $seedTypeId = $this->getSeedConsumableTypeId();
        if (!$seedTypeId) {
            return collect();
        }

        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', strtoupper($lotNumber))
            ->where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get summary information about a lot.
     * 
     * Provides comprehensive information about a lot including total quantity,
     * consumed quantity, available quantity, and entry count.
     * 
     * @param string $lotNumber The lot number to summarize
     * @return array Summary information with keys: total, consumed, available, entry_count, oldest_entry_date, newest_entry_date
     */
    public function getLotSummary(string $lotNumber): array
    {
        $entries = $this->getEntriesInLot($lotNumber);
        
        if ($entries->isEmpty()) {
            return [
                'total' => 0.0,
                'consumed' => 0.0,
                'available' => 0.0,
                'entry_count' => 0,
                'oldest_entry_date' => null,
                'newest_entry_date' => null,
            ];
        }

        $total = $entries->sum('total_quantity');
        $consumed = $entries->sum('consumed_quantity');
        $available = max(0, $total - $consumed);
        
        $entryDates = $entries->pluck('created_at')->sort();
        
        return [
            'total' => $total,
            'consumed' => $consumed,
            'available' => $available,
            'entry_count' => $entries->count(),
            'oldest_entry_date' => $entryDates->first(),
            'newest_entry_date' => $entryDates->last(),
        ];
    }

    /**
     * Get all unique lot numbers for seed consumables.
     * 
     * @return Collection Collection of unique lot numbers
     */
    public function getAllLotNumbers(): Collection
    {
        $seedTypeId = $this->getSeedConsumableTypeId();
        if (!$seedTypeId) {
            return collect();
        }

        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('is_active', true)
            ->whereNotNull('lot_no')
            ->distinct('lot_no')
            ->pluck('lot_no')
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Get lots that are running low on stock.
     * 
     * @param float $thresholdPercentage Percentage threshold (default 10%)
     * @return Collection Collection of lot summaries for low stock lots
     */
    public function getLowStockLots(float $thresholdPercentage = 10.0): Collection
    {
        $lotNumbers = $this->getAllLotNumbers();
        $lowStockLots = collect();
        
        foreach ($lotNumbers as $lotNumber) {
            $summary = $this->getLotSummary($lotNumber);
            
            if ($summary['total'] > 0) {
                $availablePercentage = ($summary['available'] / $summary['total']) * 100;
                
                if ($availablePercentage <= $thresholdPercentage) {
                    $summary['lot_number'] = $lotNumber;
                    $summary['available_percentage'] = $availablePercentage;
                    $lowStockLots->push($summary);
                }
            }
        }
        
        return $lowStockLots->sortBy('available_percentage');
    }

    /**
     * Check if a lot number exists in the system.
     * 
     * @param string $lotNumber The lot number to check
     * @return bool True if the lot exists, false otherwise
     */
    public function lotExists(string $lotNumber): bool
    {
        $seedTypeId = $this->getSeedConsumableTypeId();
        if (!$seedTypeId) {
            return false;
        }

        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', strtoupper($lotNumber))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the next available quantity from a lot for FIFO consumption.
     * 
     * This method finds the oldest entry with available stock and returns
     * how much can be consumed from it.
     * 
     * @param string $lotNumber The lot number
     * @param float $requestedQuantity The quantity requested for consumption
     * @return array Array with keys: consumable, available_quantity, can_fulfill
     */
    public function getNextAvailableQuantity(string $lotNumber, float $requestedQuantity): array
    {
        $oldestEntry = $this->getOldestEntryInLot($lotNumber);
        
        if (!$oldestEntry) {
            return [
                'consumable' => null,
                'available_quantity' => 0.0,
                'can_fulfill' => false,
            ];
        }
        
        $availableQuantity = max(0, $oldestEntry->total_quantity - $oldestEntry->consumed_quantity);
        
        return [
            'consumable' => $oldestEntry,
            'available_quantity' => $availableQuantity,
            'can_fulfill' => $availableQuantity >= $requestedQuantity,
        ];
    }

    /**
     * Log lot operation for debugging purposes.
     * 
     * @param string $operation The operation being performed
     * @param string $lotNumber The lot number
     * @param array $context Additional context data
     */
    protected function logLotOperation(string $operation, string $lotNumber, array $context = []): void
    {
        Log::info("Lot inventory operation: {$operation}", array_merge([
            'lot_number' => $lotNumber,
            'operation' => $operation,
        ], $context));
    }
}