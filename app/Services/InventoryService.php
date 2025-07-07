<?php

namespace App\Services;

/**
 * @deprecated Use InventoryManagementService instead. This class will be removed in a future version.
 */

use App\Models\Consumable;
use App\Models\ConsumableTransaction;
use App\Models\Recipe;
use App\Models\User;
use App\Services\LotInventoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        // Use total_quantity - consumed_quantity for all consumables
        return max(0, $consumable->total_quantity - $consumable->consumed_quantity);
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
        
        // Total quantity is now managed directly, no need to calculate from initial_stock
        
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
            'total_quantity' => $consumable->total_quantity + $normalizedAmount,
        ];

        if ($lotNo) {
            $data['lot_no'] = $lotNo;
        }

        $consumable->update($data);
    }

    /**
     * Get count of consumables that need restocking
     */
    public function getLowStockCount(): int
    {
        return Consumable::whereRaw('(total_quantity - consumed_quantity) <= restock_threshold')->count();
    }

    /**
     * Get consumables that need restocking
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

    /**
     * Transaction-based methods for new consumption tracking
     */

    /**
     * Record consumable consumption using transaction tracking.
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
     * Update legacy consumed_quantity field for backward compatibility.
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

        // Update for seeds
        // Update all consumables the same way now
        $consumable->update([
            'total_quantity' => max(0, $totalAdded - $totalConsumed),
            'consumed_quantity' => $totalConsumed,
        ]);
    }

    /**
     * Get transaction history for a consumable.
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
     */
    public function isUsingTransactionTracking(Consumable $consumable): bool
    {
        return $consumable->consumableTransactions()->exists();
    }

    /**
     * FIFO Lot Consumption Methods
     */

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
            $lotInventoryService = new LotInventoryService();
            
            // Validate lot exists
            if (!$lotInventoryService->lotExists($lotNumber)) {
                throw new \Exception("Lot '{$lotNumber}' does not exist");
            }
            
            // Check if sufficient stock is available
            if (!$this->canConsumeFromLot($lotNumber, $amount)) {
                $available = $lotInventoryService->getLotQuantity($lotNumber);
                throw new \Exception("Insufficient stock in lot '{$lotNumber}'. Requested: {$amount}, Available: {$available}");
            }
            
            $consumedAmounts = [];
            $remainingToConsume = $amount;
            
            // Get all entries in the lot ordered by age (FIFO)
            $entries = $lotInventoryService->getEntriesInLot($lotNumber);
            
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
        $lotInventoryService = new LotInventoryService();
        
        // Check if lot exists
        if (!$lotInventoryService->lotExists($lotNumber)) {
            return false;
        }
        
        // Check if sufficient quantity is available
        $availableQuantity = $lotInventoryService->getLotQuantity($lotNumber);
        
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
        $lotInventoryService = new LotInventoryService();
        
        // Validate lot exists
        if (!$lotInventoryService->lotExists($lotNumber)) {
            throw new \Exception("Lot '{$lotNumber}' does not exist");
        }
        
        // Check if sufficient stock is available
        if (!$this->canConsumeFromLot($lotNumber, $amount)) {
            $available = $lotInventoryService->getLotQuantity($lotNumber);
            throw new \Exception("Insufficient stock in lot '{$lotNumber}'. Requested: {$amount}, Available: {$available}");
        }
        
        $consumptionPlan = [];
        $remainingToConsume = $amount;
        
        // Get all entries in the lot ordered by age (FIFO)
        $entries = $lotInventoryService->getEntriesInLot($lotNumber);
        
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
            'total_available' => $lotInventoryService->getLotQuantity($lotNumber),
            'entries_to_consume' => $consumptionPlan,
            'entries_count' => count($consumptionPlan),
            'can_fulfill' => $remainingToConsume <= 0,
        ];
    }
}