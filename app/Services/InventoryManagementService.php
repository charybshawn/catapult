<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\ConsumableTransaction;
use App\Models\ConsumableType;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\User;
use App\Notifications\ResourceActionRequired;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Config\Repository as ConfigRepository;

/**
 * Unified Inventory Management Service
 * 
 * This service consolidates all inventory-related operations, including:
 * - General inventory management (stock tracking, restocking alerts)
 * - Lot-based inventory operations for seed consumables
 * - Lot depletion monitoring and alerts
 * - FIFO (First In, First Out) consumption tracking
 * - Transaction-based inventory tracking
 * - Crop-related inventory deductions
 * 
 * @package App\Services
 */
class InventoryManagementService
{
    /**
     * Low stock threshold percentage for lot depletion alerts.
     */
    protected float $lowStockThreshold;

    /**
     * Configuration repository instance.
     */
    protected ConfigRepository $config;

    /**
     * Create a new service instance.
     */
    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
        $this->lowStockThreshold = $this->config->get('inventory.low_stock_threshold', 15.0);
    }

    // ===================================================================
    // General Inventory Operations (formerly InventoryService)
    // ===================================================================

    /**
     * Check if a consumable needs restocking.
     * 
     * @param Consumable $consumable
     * @return bool
     */
    public function needsRestock(Consumable $consumable): bool
    {
        $currentStock = $this->getCurrentStock($consumable);
        return $currentStock <= $consumable->restock_threshold;
    }

    /**
     * Check if a consumable is out of stock.
     * 
     * @param Consumable $consumable
     * @return bool
     */
    public function isOutOfStock(Consumable $consumable): bool
    {
        return $this->getCurrentStock($consumable) <= 0;
    }

    /**
     * Get current available stock for a consumable.
     * 
     * @param Consumable $consumable
     * @return float
     */
    public function getCurrentStock(Consumable $consumable): float
    {
        // Use total_quantity - consumed_quantity for all consumables
        return max(0, $consumable->total_quantity - $consumable->consumed_quantity);
    }

    /**
     * Calculate total value of consumable inventory.
     * 
     * @param Consumable $consumable
     * @return float
     */
    public function calculateTotalValue(Consumable $consumable): float
    {
        $currentStock = $this->getCurrentStock($consumable);
        return $currentStock * $consumable->cost_per_unit;
    }

    /**
     * Deduct quantity from consumable stock.
     * 
     * @param Consumable $consumable
     * @param float $amount
     * @param string|null $unit
     * @return void
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
     * Add quantity to consumable stock.
     * 
     * @param Consumable $consumable
     * @param float $amount
     * @param string|null $unit
     * @param string|null $lotNo
     * @return bool
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
     * Get formatted total weight display.
     * 
     * @param Consumable $consumable
     * @return string
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
     * Get count of consumables that need restocking.
     * 
     * @return int
     */
    public function getLowStockCount(): int
    {
        return Consumable::whereRaw('(total_quantity - consumed_quantity) <= restock_threshold')->count();
    }

    /**
     * Get consumables that need restocking.
     * 
     * @param int|null $limit
     * @return Collection
     */
    public function getLowStockItems($limit = null)
    {
        $query = Consumable::whereRaw('(total_quantity - consumed_quantity) <= restock_threshold')
            ->orderByRaw('((total_quantity - consumed_quantity) / NULLIF(restock_threshold, 0)) ASC');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    // ===================================================================
    // Transaction-based Inventory Tracking
    // ===================================================================

    /**
     * Record consumable consumption using transaction tracking.
     * 
     * @param Consumable $consumable
     * @param float $amount
     * @param string|null $unit
     * @param User|null $user
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $notes
     * @param array|null $metadata
     * @return ConsumableTransaction
     */
    public function recordConsumption(
        Consumable $consumable,
        float $amount,
        ?string $unit = null,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): ConsumableTransaction {
        return DB::transaction(function () use (
            $consumable, $amount, $unit, $user, $referenceType, $referenceId, $notes, $metadata
        ) {
            $normalizedAmount = $this->normalizeQuantity($consumable, $amount, $unit);
            
            // Calculate new balance
            $currentBalance = $this->getCurrentStockFromTransactions($consumable);
            $newBalance = max(0, $currentBalance - $normalizedAmount);
            
            // Create consumption transaction
            $transaction = ConsumableTransaction::createConsumption(
                $consumable,
                $normalizedAmount,
                $newBalance,
                $user,
                $referenceType,
                $referenceId,
                $notes,
                $metadata
            );

            // Update legacy consumed_quantity for backward compatibility
            $this->updateLegacyConsumedQuantity($consumable);

            Log::info('Consumable consumption recorded via transaction', [
                'consumable_id' => $consumable->id,
                'amount' => $amount,
                'unit' => $unit,
                'transaction_id' => $transaction->id,
                'new_balance' => $newBalance
            ]);

            return $transaction;
        });
    }

    /**
     * Record consumable addition using transaction tracking.
     * 
     * @param Consumable $consumable
     * @param float $amount
     * @param string|null $unit
     * @param User|null $user
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $notes
     * @param array|null $metadata
     * @return ConsumableTransaction
     */
    public function recordAddition(
        Consumable $consumable,
        float $amount,
        ?string $unit = null,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): ConsumableTransaction {
        return DB::transaction(function () use (
            $consumable, $amount, $unit, $user, $referenceType, $referenceId, $notes, $metadata
        ) {
            $normalizedAmount = $this->normalizeQuantity($consumable, $amount, $unit);
            
            // Calculate new balance
            $currentBalance = $this->getCurrentStockFromTransactions($consumable);
            $newBalance = $currentBalance + $normalizedAmount;
            
            // Create addition transaction
            $transaction = ConsumableTransaction::createAddition(
                $consumable,
                $normalizedAmount,
                $newBalance,
                $user,
                $referenceType,
                $referenceId,
                $notes,
                $metadata
            );

            // Update legacy fields for backward compatibility
            $this->updateLegacyStockFields($consumable);

            Log::info('Consumable addition recorded via transaction', [
                'consumable_id' => $consumable->id,
                'amount' => $amount,
                'unit' => $unit,
                'transaction_id' => $transaction->id,
                'new_balance' => $newBalance
            ]);

            return $transaction;
        });
    }

    /**
     * Get current stock from transaction history.
     * 
     * @param Consumable $consumable
     * @return float
     */
    public function getCurrentStockFromTransactions(Consumable $consumable): float
    {
        $latestTransaction = $consumable->consumableTransactions()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($latestTransaction) {
            return $latestTransaction->balance_after;
        }

        // Fall back to legacy calculation if no transactions exist
        return $this->getCurrentStock($consumable);
    }

    /**
     * Initialize transaction tracking for existing consumable.
     * 
     * @param Consumable $consumable
     * @return ConsumableTransaction|null
     */
    public function initializeTransactionTracking(Consumable $consumable): ?ConsumableTransaction
    {
        // Check if already initialized
        if ($consumable->consumableTransactions()->exists()) {
            return null;
        }

        $currentStock = $this->getCurrentStock($consumable);
        
        if ($currentStock <= 0) {
            return null;
        }

        // Create initial stock transaction
        return ConsumableTransaction::create([
            'consumable_id' => $consumable->id,
            'type' => ConsumableTransaction::TYPE_INITIAL,
            'quantity' => $currentStock,
            'balance_after' => $currentStock,
            'user_id' => auth()?->id(),
            'notes' => 'Initial stock from legacy system',
            'metadata' => [
                'total_quantity' => $consumable->total_quantity,
                'consumed_quantity' => $consumable->consumed_quantity,
                'migrated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Get transaction history for a consumable.
     * 
     * @param Consumable $consumable
     * @param int $limit
     * @return Collection
     */
    public function getTransactionHistory(Consumable $consumable, int $limit = 50)
    {
        return $consumable->consumableTransactions()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if consumable is using transaction-based tracking.
     * 
     * @param Consumable $consumable
     * @return bool
     */
    public function isUsingTransactionTracking(Consumable $consumable): bool
    {
        return $consumable->consumableTransactions()->exists();
    }

    // ===================================================================
    // Lot-based Inventory Operations (formerly LotInventoryService)
    // ===================================================================

    /**
     * Get the seed consumable type ID dynamically.
     * 
     * @return int|null
     */
    public function getSeedTypeId(): ?int
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
            $availableQuantity = max(0, $consumable->total_quantity - $consumable->consumed_quantity);
            
            // Convert to grams if the consumable is in kg (for seed inventory)
            if ($consumable->quantity_unit === 'kg') {
                return $availableQuantity * 1000;
            }
            
            return $availableQuantity;
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
        $seedTypeId = $this->getSeedTypeId();
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
        $seedTypeId = $this->getSeedTypeId();
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
     * @return array Summary information
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
        $seedTypeId = $this->getSeedTypeId();
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
        $seedTypeId = $this->getSeedTypeId();
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

    // ===================================================================
    // FIFO Lot Consumption Methods
    // ===================================================================

    /**
     * Consume specified amount from a lot using FIFO (oldest entries first).
     * 
     * @param string $lotNumber The lot number to consume from
     * @param float $amount The amount to consume
     * @param Recipe|null $recipe Optional recipe reference
     * @param User|null $user Optional user performing the consumption
     * @return array Array with consumed amounts per consumable entry
     * @throws \Exception If lot doesn't exist or insufficient stock
     */
    public function consumeFromLot(string $lotNumber, float $amount, ?Recipe $recipe = null, ?User $user = null): array
    {
        return DB::transaction(function () use ($lotNumber, $amount, $recipe, $user) {
            // Validate lot exists
            if (!$this->lotExists($lotNumber)) {
                throw new \Exception("Lot '{$lotNumber}' does not exist");
            }
            
            // Check if sufficient stock is available
            if (!$this->canConsumeFromLot($lotNumber, $amount)) {
                $available = $this->getLotQuantity($lotNumber);
                throw new \Exception("Insufficient stock in lot '{$lotNumber}'. Requested: {$amount}, Available: {$available}");
            }
            
            $consumedAmounts = [];
            $remainingToConsume = $amount;
            
            // Get all entries in the lot ordered by age (FIFO)
            $entries = $this->getEntriesInLot($lotNumber);
            
            foreach ($entries as $consumable) {
                if ($remainingToConsume <= 0) {
                    break;
                }
                
                $availableInEntry = $this->getCurrentStockFromTransactions($consumable);
                if ($availableInEntry <= 0) {
                    continue;
                }
                
                // Calculate how much to consume from this entry
                $toConsumeFromEntry = min($remainingToConsume, $availableInEntry);
                
                // Prepare transaction metadata
                $metadata = [
                    'lot_number' => $lotNumber,
                    'fifo_consumption' => true,
                ];
                
                if ($recipe) {
                    $metadata['recipe_id'] = $recipe->id;
                    $metadata['recipe_name'] = $recipe->name;
                }
                
                // Record consumption transaction
                $transaction = $this->recordConsumption(
                    $consumable,
                    $toConsumeFromEntry,
                    null, // unit
                    $user,
                    $recipe ? 'recipe' : null,
                    $recipe ? $recipe->id : null,
                    "FIFO lot consumption from lot {$lotNumber}",
                    $metadata
                );
                
                $consumedAmounts[] = [
                    'consumable_id' => $consumable->id,
                    'amount' => $toConsumeFromEntry,
                    'remaining_after' => $transaction->balance_after,
                    'transaction_id' => $transaction->id,
                ];
                
                $remainingToConsume -= $toConsumeFromEntry;
            }
            
            Log::info('FIFO lot consumption completed', [
                'lot_number' => $lotNumber,
                'total_consumed' => $amount,
                'entries_affected' => count($consumedAmounts),
                'recipe_id' => $recipe?->id,
                'user_id' => $user?->id,
            ]);
            
            return $consumedAmounts;
        });
    }

    /**
     * Check if lot has enough available quantity for consumption.
     * 
     * @param string $lotNumber The lot number to check
     * @param float $amount The amount to check for
     * @return bool True if consumption is possible, false otherwise
     */
    public function canConsumeFromLot(string $lotNumber, float $amount): bool
    {
        // Check if lot exists
        if (!$this->lotExists($lotNumber)) {
            return false;
        }
        
        // Check if sufficient quantity is available
        $availableQuantity = $this->getLotQuantity($lotNumber);
        
        return $availableQuantity >= $amount;
    }

    /**
     * Get detailed plan showing which entries will be consumed and how much.
     * 
     * @param string $lotNumber The lot number to plan for
     * @param float $amount The amount to plan consumption for
     * @return array Array with consumption plan details
     * @throws \Exception If lot doesn't exist or insufficient stock
     */
    public function getLotConsumptionPlan(string $lotNumber, float $amount): array
    {
        // Validate lot exists
        if (!$this->lotExists($lotNumber)) {
            throw new \Exception("Lot '{$lotNumber}' does not exist");
        }
        
        // Check if sufficient stock is available
        if (!$this->canConsumeFromLot($lotNumber, $amount)) {
            $available = $this->getLotQuantity($lotNumber);
            throw new \Exception("Insufficient stock in lot '{$lotNumber}'. Requested: {$amount}, Available: {$available}");
        }
        
        $consumptionPlan = [];
        $remainingToConsume = $amount;
        
        // Get all entries in the lot ordered by age (FIFO)
        $entries = $this->getEntriesInLot($lotNumber);
        
        foreach ($entries as $consumable) {
            if ($remainingToConsume <= 0) {
                break;
            }
            
            $availableInEntry = $this->getCurrentStockFromTransactions($consumable);
            if ($availableInEntry <= 0) {
                continue;
            }
            
            // Calculate how much would be consumed from this entry
            $toConsumeFromEntry = min($remainingToConsume, $availableInEntry);
            $remainingAfter = $availableInEntry - $toConsumeFromEntry;
            
            $consumptionPlan[] = [
                'consumable_id' => $consumable->id,
                'consumable_name' => $consumable->name,
                'current_stock' => $availableInEntry,
                'amount' => $toConsumeFromEntry,
                'remaining_after' => $remainingAfter,
                'created_at' => $consumable->created_at,
                'lot_number' => $lotNumber,
            ];
            
            $remainingToConsume -= $toConsumeFromEntry;
        }
        
        return [
            'lot_number' => $lotNumber,
            'requested_amount' => $amount,
            'total_available' => $this->getLotQuantity($lotNumber),
            'entries_to_consume' => $consumptionPlan,
            'entries_count' => count($consumptionPlan),
            'can_fulfill' => $remainingToConsume <= 0,
        ];
    }

    // ===================================================================
    // Lot Depletion Management (formerly LotDepletionService)
    // ===================================================================

    /**
     * Check all lots and return comprehensive status summary.
     * 
     * @return array Summary with keys: total_lots, active_lots, depleted_lots, low_stock_lots, lot_details
     */
    public function checkAllLots(): array
    {
        $allLots = $this->getAllLotNumbers();
        $lotDetails = [];
        $depletedCount = 0;
        $lowStockCount = 0;
        $activeCount = 0;
        
        foreach ($allLots as $lotNumber) {
            $summary = $this->getLotSummary($lotNumber);
            $isDepletedByQuantity = $summary['available'] <= 0;
            $isLowStock = false;
            
            if ($summary['total'] > 0) {
                $availablePercentage = ($summary['available'] / $summary['total']) * 100;
                $isLowStock = $availablePercentage <= $this->lowStockThreshold && $availablePercentage > 0;
            }
            
            // Check if any recipes are manually marked as depleted for this lot
            $recipesForLot = Recipe::where('lot_number', $lotNumber)
                ->where('is_active', true)
                ->get();
            
            $manuallyMarkedDepleted = $recipesForLot->where('lot_depleted_at', '!=', null)->count() > 0;
            
            $isDepleted = $isDepletedByQuantity || $manuallyMarkedDepleted;
            
            if ($isDepleted) {
                $depletedCount++;
            } elseif ($isLowStock) {
                $lowStockCount++;
            } else {
                $activeCount++;
            }
            
            $lotDetails[] = [
                'lot_number' => $lotNumber,
                'total_quantity' => $summary['total'],
                'available_quantity' => $summary['available'],
                'consumed_quantity' => $summary['consumed'],
                'entry_count' => $summary['entry_count'],
                'is_depleted' => $isDepleted,
                'is_low_stock' => $isLowStock,
                'available_percentage' => $summary['total'] > 0 ? ($summary['available'] / $summary['total']) * 100 : 0,
                'manually_marked_depleted' => $manuallyMarkedDepleted,
                'depleted_by_quantity' => $isDepletedByQuantity,
                'oldest_entry_date' => $summary['oldest_entry_date'],
                'newest_entry_date' => $summary['newest_entry_date'],
                'recipe_count' => $recipesForLot->count(),
            ];
        }
        
        return [
            'total_lots' => $allLots->count(),
            'active_lots' => $activeCount,
            'depleted_lots' => $depletedCount,
            'low_stock_lots' => $lowStockCount,
            'lot_details' => $lotDetails,
        ];
    }

    /**
     * Get all recipes that have depleted lots.
     * 
     * @return Collection
     */
    public function getDepletedRecipes(): Collection
    {
        return Recipe::where('is_active', true)
            ->whereNotNull('lot_number')
            ->get()
            ->filter(function ($recipe) {
                return $recipe->isLotDepleted();
            });
    }

    /**
     * Get all recipes that have low stock lots.
     * 
     * @return Collection
     */
    public function getLowStockRecipes(): Collection
    {
        return Recipe::where('is_active', true)
            ->whereNotNull('lot_number')
            ->get()
            ->filter(function ($recipe) {
                if ($recipe->isLotDepleted()) {
                    return false; // Skip depleted lots
                }
                
                $lotQuantity = $recipe->getLotQuantity();
                if ($lotQuantity <= 0) {
                    return false;
                }
                
                $summary = $this->getLotSummary($recipe->lot_number);
                if ($summary['total'] <= 0) {
                    return false;
                }
                
                $availablePercentage = ($summary['available'] / $summary['total']) * 100;
                return $availablePercentage <= $this->lowStockThreshold;
            });
    }

    /**
     * Send notifications about depleted lots to admin users.
     * 
     * @return void
     */
    public function sendDepletionAlerts(): void
    {
        $depletedRecipes = $this->getDepletedRecipes();
        
        if ($depletedRecipes->isEmpty()) {
            return;
        }
        
        $adminUsers = User::where('is_admin', true)->get();
        
        if ($adminUsers->isEmpty()) {
            Log::warning('No admin users found to send lot depletion alerts to');
            return;
        }
        
        $lotDetails = [];
        foreach ($depletedRecipes as $recipe) {
            $lotNumber = $recipe->lot_number;
            if (!isset($lotDetails[$lotNumber])) {
                $summary = $this->getLotSummary($lotNumber);
                $lotDetails[$lotNumber] = [
                    'lot_number' => $lotNumber,
                    'recipes' => [],
                    'summary' => $summary,
                ];
            }
            $lotDetails[$lotNumber]['recipes'][] = $recipe->name;
        }
        
        $subject = 'Critical Alert: Seed Lot Depletion Detected';
        $body = $this->buildDepletionNotificationBody($lotDetails);
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new ResourceActionRequired(
                $subject,
                $body,
                url('/admin/recipes'),
                'View Recipes'
            ));
        }
        
        Log::info('Lot depletion alerts sent', [
            'depleted_lots' => count($lotDetails),
            'affected_recipes' => $depletedRecipes->count(),
            'notified_users' => $adminUsers->count(),
        ]);
    }

    /**
     * Send notifications about low stock lots to admin users.
     * 
     * @return void
     */
    public function sendLowStockAlerts(): void
    {
        $lowStockRecipes = $this->getLowStockRecipes();
        
        if ($lowStockRecipes->isEmpty()) {
            return;
        }
        
        $adminUsers = User::where('is_admin', true)->get();
        
        if ($adminUsers->isEmpty()) {
            Log::warning('No admin users found to send low stock alerts to');
            return;
        }
        
        $lotDetails = [];
        foreach ($lowStockRecipes as $recipe) {
            $lotNumber = $recipe->lot_number;
            if (!isset($lotDetails[$lotNumber])) {
                $summary = $this->getLotSummary($lotNumber);
                $availablePercentage = $summary['total'] > 0 ? ($summary['available'] / $summary['total']) * 100 : 0;
                $lotDetails[$lotNumber] = [
                    'lot_number' => $lotNumber,
                    'recipes' => [],
                    'summary' => $summary,
                    'available_percentage' => $availablePercentage,
                ];
            }
            $lotDetails[$lotNumber]['recipes'][] = $recipe->name;
        }
        
        $subject = 'Warning: Low Stock Alert for Seed Lots';
        $body = $this->buildLowStockNotificationBody($lotDetails);
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new ResourceActionRequired(
                $subject,
                $body,
                url('/admin/recipes'),
                'View Recipes'
            ));
        }
        
        Log::info('Low stock alerts sent', [
            'low_stock_lots' => count($lotDetails),
            'affected_recipes' => $lowStockRecipes->count(),
            'notified_users' => $adminUsers->count(),
        ]);
    }

    /**
     * Automatically mark lots as depleted when they have zero available quantity.
     * 
     * @return int Number of recipes marked as depleted
     */
    public function markAutomaticDepletion(): int
    {
        $activeRecipes = Recipe::where('is_active', true)
            ->whereNotNull('lot_number')
            ->whereNull('lot_depleted_at')
            ->get();
        
        $markedCount = 0;
        
        foreach ($activeRecipes as $recipe) {
            $lotQuantity = $recipe->getLotQuantity();
            
            if ($lotQuantity <= 0) {
                $recipe->markLotDepleted();
                $markedCount++;
                
                Log::info('Automatically marked lot as depleted', [
                    'recipe_id' => $recipe->id,
                    'recipe_name' => $recipe->name,
                    'lot_number' => $recipe->lot_number,
                    'available_quantity' => $lotQuantity,
                ]);
            }
        }
        
        return $markedCount;
    }

    /**
     * Get critical lot alerts for dashboard display.
     * 
     * @return array
     */
    public function getCriticalAlerts(): array
    {
        $depletedRecipes = $this->getDepletedRecipes();
        $lowStockRecipes = $this->getLowStockRecipes();
        
        $alerts = [];
        
        // Add depleted lot alerts
        foreach ($depletedRecipes as $recipe) {
            $alerts[] = [
                'type' => 'critical',
                'title' => 'Lot Depleted',
                'message' => "Recipe '{$recipe->name}' has a depleted lot ({$recipe->lot_number})",
                'recipe_id' => $recipe->id,
                'lot_number' => $recipe->lot_number,
                'created_at' => $recipe->lot_depleted_at ?? now(),
            ];
        }
        
        // Add low stock alerts
        foreach ($lowStockRecipes as $recipe) {
            $summary = $this->getLotSummary($recipe->lot_number);
            $availablePercentage = $summary['total'] > 0 ? ($summary['available'] / $summary['total']) * 100 : 0;
            
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Stock',
                'message' => "Recipe '{$recipe->name}' lot ({$recipe->lot_number}) is running low (" . number_format($availablePercentage, 1) . "% remaining)",
                'recipe_id' => $recipe->id,
                'lot_number' => $recipe->lot_number,
                'available_percentage' => $availablePercentage,
                'available_quantity' => $summary['available'],
                'created_at' => now(),
            ];
        }
        
        // Sort by severity (critical first) and then by date
        usort($alerts, function ($a, $b) {
            if ($a['type'] === 'critical' && $b['type'] === 'warning') {
                return -1;
            } elseif ($a['type'] === 'warning' && $b['type'] === 'critical') {
                return 1;
            }
            return $b['created_at'] <=> $a['created_at'];
        });
        
        return $alerts;
    }

    /**
     * Set the low stock threshold percentage.
     * 
     * @param float $threshold
     * @return void
     */
    public function setLowStockThreshold(float $threshold): void
    {
        $this->lowStockThreshold = max(0, min(100, $threshold));
    }

    /**
     * Get the current low stock threshold percentage.
     * 
     * @return float
     */
    public function getLowStockThreshold(): float
    {
        return $this->lowStockThreshold;
    }

    // ===================================================================
    // Crop-related Inventory Operations (formerly CropInventoryService)
    // ===================================================================

    /**
     * Deduct seed inventory for a newly created crop.
     * 
     * This method handles the automatic deduction of seed inventory when a crop
     * is created, using the FIFO system to consume from the oldest stock first.
     * 
     * @param Crop $crop The crop that was created
     * @return bool True if deduction was successful, false otherwise
     */
    public function deductSeedForCrop(Crop $crop): bool
    {
        // Ensure recipe is loaded
        if (!$crop->relationLoaded('recipe')) {
            $crop->load('recipe');
        }

        if (!$crop->recipe || !$crop->recipe->seed_density_grams_per_tray) {
            Log::warning('Cannot deduct seed - missing recipe or seed density', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
            ]);
            return false;
        }

        $requiredAmount = $crop->recipe->seed_density_grams_per_tray;

        // Use lot-based inventory if lot_number is specified
        if ($crop->recipe->lot_number) {
            return $this->deductSeedFromLot($crop, $requiredAmount);
        }

        // Fallback to old consumable-based system (deprecated)
        return $this->deductSeedFromConsumable($crop, $requiredAmount);
    }

    /**
     * Check if a crop can be created based on seed availability.
     * 
     * @param int $recipeId The recipe ID
     * @param int $trayCount The number of trays to create
     * @return array ['can_create' => bool, 'message' => string, 'required' => float, 'available' => float]
     */
    public function checkSeedAvailability(int $recipeId, int $trayCount = 1): array
    {
        $recipe = Recipe::find($recipeId);
        
        if (!$recipe) {
            return [
                'can_create' => false,
                'message' => 'Recipe not found',
                'required' => 0,
                'available' => 0,
            ];
        }

        if (!$recipe->seed_density_grams_per_tray) {
            return [
                'can_create' => false,
                'message' => 'Recipe has no seed density configured',
                'required' => 0,
                'available' => 0,
            ];
        }

        $requiredAmount = $recipe->seed_density_grams_per_tray * $trayCount;
        $requiredInKg = $requiredAmount / 1000;

        // Check lot-based availability
        if ($recipe->lot_number) {
            $available = $this->getLotQuantity($recipe->lot_number);
            
            return [
                'can_create' => $available >= $requiredInKg,
                'message' => $available >= $requiredInKg 
                    ? 'Sufficient seed available' 
                    : 'Insufficient seed in lot',
                'required' => $requiredInKg,
                'available' => $available,
            ];
        }

        // Fallback to consumable-based check
        if ($recipe->seedConsumable) {
            $available = $this->getCurrentStock($recipe->seedConsumable);
            $requiredInConsumableUnits = $this->convertToConsumableUnits($requiredAmount, $recipe->seedConsumable);
            
            return [
                'can_create' => $available >= $requiredInConsumableUnits,
                'message' => $available >= $requiredInConsumableUnits 
                    ? 'Sufficient seed available' 
                    : 'Insufficient seed stock',
                'required' => $requiredInConsumableUnits,
                'available' => $available,
            ];
        }

        return [
            'can_create' => false,
            'message' => 'No seed source configured for recipe',
            'required' => $requiredInKg,
            'available' => 0,
        ];
    }

    // ===================================================================
    // Private Helper Methods
    // ===================================================================

    /**
     * Normalize quantity to base unit for calculations.
     * 
     * @param Consumable $consumable
     * @param float $amount
     * @param string|null $unit
     * @return float
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
     * Deduct stock for seed consumables.
     * 
     * @param Consumable $consumable
     * @param float $normalizedAmount
     * @return void
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
     * Deduct stock for general consumables.
     * 
     * @param Consumable $consumable
     * @param float $normalizedAmount
     * @return void
     */
    private function deductGeneralStock(Consumable $consumable, float $normalizedAmount): void
    {
        $newConsumedQuantity = $consumable->consumed_quantity + $normalizedAmount;
        
        $data = [
            'consumed_quantity' => $newConsumedQuantity,
        ];
        
        // Total quantity is now managed directly, no need to calculate from initial_stock
        
        $consumable->update($data);
    }

    /**
     * Check if lot number is compatible with existing stock.
     * 
     * @param Consumable $consumable
     * @param string $lotNo
     * @return bool
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
     * Perform the actual stock addition.
     * 
     * @param Consumable $consumable
     * @param float $normalizedAmount
     * @param string|null $lotNo
     * @return void
     */
    private function performStockAddition(Consumable $consumable, float $normalizedAmount, ?string $lotNo): void
    {
        $data = [
            'total_quantity' => $consumable->total_quantity + $normalizedAmount,
        ];

        if ($lotNo) {
            $data['lot_no'] = $lotNo;
        }

        $consumable->update($data);
    }

    /**
     * Update legacy consumed_quantity field for backward compatibility.
     * 
     * @param Consumable $consumable
     * @return void
     */
    protected function updateLegacyConsumedQuantity(Consumable $consumable): void
    {
        $totalConsumed = $consumable->consumableTransactions()
            ->whereIn('type', [
                ConsumableTransaction::TYPE_CONSUMPTION,
                ConsumableTransaction::TYPE_WASTE,
                ConsumableTransaction::TYPE_EXPIRATION,
                ConsumableTransaction::TYPE_TRANSFER_OUT,
            ])
            ->sum('quantity'); // This will be negative values, so sum gives total consumed

        $consumable->update([
            'consumed_quantity' => abs($totalConsumed),
        ]);
    }

    /**
     * Update legacy stock fields for backward compatibility.
     * 
     * @param Consumable $consumable
     * @return void
     */
    protected function updateLegacyStockFields(Consumable $consumable): void
    {
        $totalAdded = $consumable->consumableTransactions()
            ->whereIn('type', [
                ConsumableTransaction::TYPE_ADDITION,
                ConsumableTransaction::TYPE_TRANSFER_IN,
                ConsumableTransaction::TYPE_INITIAL,
            ])
            ->sum('quantity');

        $totalConsumed = abs($consumable->consumableTransactions()
            ->whereIn('type', [
                ConsumableTransaction::TYPE_CONSUMPTION,
                ConsumableTransaction::TYPE_WASTE,
                ConsumableTransaction::TYPE_EXPIRATION,
                ConsumableTransaction::TYPE_TRANSFER_OUT,
            ])
            ->sum('quantity'));

        // Update all consumables the same way now
        $consumable->update([
            'total_quantity' => max(0, $totalAdded - $totalConsumed),
            'consumed_quantity' => $totalConsumed,
        ]);
    }

    /**
     * Deduct seed from a specific lot using FIFO.
     * 
     * @param Crop $crop The crop being created
     * @param float $requiredAmount The amount of seed required in grams
     * @return bool True if successful, false otherwise
     */
    private function deductSeedFromLot(Crop $crop, float $requiredAmount): bool
    {
        $lotNumber = $crop->recipe->lot_number;
        
        // Check if lot has sufficient quantity
        $availableQuantity = $this->getLotQuantity($lotNumber);
        
        // Convert to consistent units for comparison
        $requiredInKg = $requiredAmount / 1000; // Convert grams to kg
        
        if ($availableQuantity < $requiredInKg) {
            Log::warning('Insufficient seed stock in lot for crop creation', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'lot_number' => $lotNumber,
                'required_amount' => $requiredAmount,
                'required_in_kg' => $requiredInKg,
                'available_in_lot' => $availableQuantity,
            ]);
            return false;
        }

        // Get the oldest consumable entry for FIFO deduction
        $consumable = $this->getOldestEntryInLot($lotNumber);
        
        if (!$consumable) {
            Log::error('No consumable entry found for lot', [
                'crop_id' => $crop->id,
                'lot_number' => $lotNumber,
            ]);
            return false;
        }

        // Deduct the seed amount
        try {
            $consumable->deduct($requiredAmount, 'g'); // Recipe seed density is always in grams
            
            Log::info('Seed automatically deducted for new crop (lot-based)', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'lot_number' => $lotNumber,
                'consumable_id' => $consumable->id,
                'amount_deducted' => $requiredAmount,
                'unit' => 'g',
                'remaining_in_lot' => $this->getLotQuantity($lotNumber),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error deducting seed from lot', [
                'crop_id' => $crop->id,
                'lot_number' => $lotNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Deduct seed from legacy consumable-based system.
     * 
     * @deprecated Use lot-based deduction instead
     * @param Crop $crop The crop being created
     * @param float $requiredAmount The amount of seed required in grams
     * @return bool True if successful, false otherwise
     */
    private function deductSeedFromConsumable(Crop $crop, float $requiredAmount): bool
    {
        if (!$crop->recipe->seedConsumable) {
            Log::warning('No seed consumable assigned to recipe', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
            ]);
            return false;
        }

        $seedConsumable = $crop->recipe->seedConsumable;
        $currentStock = $this->getCurrentStock($seedConsumable);
        
        // Convert required amount to the same unit as the seed consumable for comparison
        $requiredInSeedUnits = $this->convertToConsumableUnits($requiredAmount, $seedConsumable);
        
        if ($currentStock < $requiredInSeedUnits) {
            Log::warning('Insufficient seed stock for crop creation', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'seed_consumable_id' => $seedConsumable->id,
                'required_amount' => $requiredAmount,
                'current_stock' => $currentStock,
                'seed_unit' => $seedConsumable->quantity_unit,
            ]);
            return false;
        }

        try {
            // Deduct the seed amount specified in the recipe for this tray
            $seedConsumable->deduct($requiredAmount, 'g'); // Recipe seed density is always in grams
            
            Log::info('Seed automatically deducted for new crop (legacy)', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'seed_consumable_id' => $seedConsumable->id,
                'amount_deducted' => $requiredAmount,
                'unit' => 'g',
                'remaining_stock' => $currentStock - $requiredInSeedUnits,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error deducting seed inventory for new crop', [
                'crop_id' => $crop->id,
                'recipe_id' => $crop->recipe_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Convert grams to the consumable's unit of measurement.
     * 
     * @param float $amountInGrams The amount in grams
     * @param Consumable $consumable The consumable with its unit
     * @return float The amount in the consumable's unit
     */
    private function convertToConsumableUnits(float $amountInGrams, Consumable $consumable): float
    {
        return match($consumable->quantity_unit) {
            'kg' => $amountInGrams / 1000, // Convert grams to kg
            'g' => $amountInGrams, // Already in grams
            default => $amountInGrams // For other units, assume direct comparison
        };
    }

    /**
     * Build the notification body for depletion alerts.
     * 
     * @param array $lotDetails
     * @return string
     */
    protected function buildDepletionNotificationBody(array $lotDetails): string
    {
        $body = "The following seed lots have been depleted and require immediate attention:\n\n";
        
        foreach ($lotDetails as $details) {
            $body .= "**Lot {$details['lot_number']}**\n";
            $body .= "- Total Quantity: {$details['summary']['total']}g\n";
            $body .= "- Available Quantity: {$details['summary']['available']}g\n";
            $body .= "- Consumed Quantity: {$details['summary']['consumed']}g\n";
            $body .= "- Affected Recipes: " . implode(', ', $details['recipes']) . "\n";
            $body .= "- Inventory Entries: {$details['summary']['entry_count']}\n\n";
        }
        
        $body .= "**Action Required:**\n";
        $body .= "- Review and update recipe lot assignments\n";
        $body .= "- Order new seed stock for affected varieties\n";
        $body .= "- Consider suspending production for affected recipes\n\n";
        
        $body .= "Please address these issues promptly to maintain production schedules.";
        
        return $body;
    }

    /**
     * Build the notification body for low stock alerts.
     * 
     * @param array $lotDetails
     * @return string
     */
    protected function buildLowStockNotificationBody(array $lotDetails): string
    {
        $body = "The following seed lots are running low on stock and may need attention:\n\n";
        
        foreach ($lotDetails as $details) {
            $body .= "**Lot {$details['lot_number']}** (" . number_format($details['available_percentage'], 1) . "% remaining)\n";
            $body .= "- Total Quantity: {$details['summary']['total']}g\n";
            $body .= "- Available Quantity: {$details['summary']['available']}g\n";
            $body .= "- Consumed Quantity: {$details['summary']['consumed']}g\n";
            $body .= "- Affected Recipes: " . implode(', ', $details['recipes']) . "\n\n";
        }
        
        $body .= "**Recommended Actions:**\n";
        $body .= "- Monitor these lots closely\n";
        $body .= "- Consider placing orders for replacement seed stock\n";
        $body .= "- Review upcoming production schedules\n\n";
        
        $body .= "Early planning helps prevent production disruptions.";
        
        return $body;
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