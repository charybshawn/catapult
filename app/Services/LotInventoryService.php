<?php

namespace App\Services;

/**
 * @deprecated Use InventoryManagementService instead. Scheduled for removal in next major version.
 * @migration_path Functionality consolidated into InventoryManagementService
 * @removal_timeline Phase out planned for next major release
 */

use App\Models\Consumable;
use App\Models\ConsumableType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural seed lot inventory management service with FIFO consumption tracking.
 * 
 * Manages lot-level seed inventory operations for agricultural production, implementing
 * First-In-First-Out (FIFO) inventory principles to ensure proper seed rotation and
 * prevent waste. Tracks individual seed lot quantities, consumption patterns, and
 * availability for microgreens production planning and resource allocation.
 * 
 * @deprecated Superseded by unified InventoryManagementService architecture
 * @business_domain Agricultural seed lot inventory and FIFO consumption management
 * @agricultural_fifo Ensures oldest seed stock is consumed first for quality control
 * @lot_tracking Individual seed lot lifecycle and consumption monitoring
 * @production_planning Supports crop planning with accurate seed availability data
 * 
 * @features
 * - FIFO-based seed consumption prioritization
 * - Individual lot quantity and depletion tracking
 * - Comprehensive lot summary reporting and analytics
 * - Low stock detection and early warning systems
 * - Oldest stock identification for rotation compliance
 * - Lot existence validation for production planning
 * 
 * @example
 * $lotService = new LotInventoryService();
 * $availableQty = $lotService->getLotQuantity('LOT2024-001');
 * $oldestEntry = $lotService->getOldestEntryInLot('LOT2024-001');
 * 
 * @migration_note New code should use InventoryManagementService
 * @see InventoryManagementService For modern unified inventory operations
 * @see Consumable For individual seed lot entry tracking
 */
class LotInventoryService
{
    /**
     * Resolve seed consumable type identifier for agricultural inventory operations.
     * 
     * Dynamically retrieves the database identifier for seed consumable type,
     * enabling type-specific inventory queries and ensuring operations target
     * only agricultural seed supplies rather than other consumable types.
     * 
     * @type_resolution Dynamically resolves seed consumable type for filtering
     * @agricultural_focus Ensures operations target only seed inventory
     * @internal Utility method for type-specific inventory queries
     * 
     * @return int|null Seed consumable type ID or null if not found
     */
    private function getSeedConsumableTypeId(): ?int
    {
        return ConsumableType::where('code', 'seed')->value('id');
    }

    /**
     * Retrieve seed consumable type identifier for external agricultural operations.
     * 
     * Public interface for accessing seed consumable type ID, enabling external
     * services to perform seed-specific inventory queries and maintain consistency
     * with agricultural lot inventory management type filtering.
     * 
     * @public_api Exposes seed type ID for external agricultural services
     * @type_consistency Ensures consistent seed type identification across system
     * @agricultural_integration Supports external seed inventory operations
     * 
     * @return int|null Seed consumable type identifier for inventory filtering
     */
    public function getSeedTypeId(): ?int
    {
        return $this->getSeedConsumableTypeId();
    }

    /**
     * Calculate total available seed quantity remaining in agricultural lot.
     * 
     * Aggregates available quantity across all individual seed entries within
     * a specific lot, accounting for consumed amounts to provide accurate
     * availability for agricultural production planning and crop scheduling.
     * 
     * @lot_aggregation Sums available quantity across all entries in lot
     * @agricultural_planning Provides accurate seed availability for crop planning
     * @consumption_tracking Accounts for consumed quantities in availability calculation
     * @production_support Essential for determining crop production capacity
     * 
     * @param string $lotNumber Agricultural seed lot identifier to analyze
     * @return float Total available seed quantity in grams across all lot entries
     * 
     * @example
     * $available = $this->getLotQuantity('LOT2024-ARUGULA-001');
     * if ($available >= 500) {
     *     // Sufficient seed for planned crop production
     * }
     */
    public function getLotQuantity(string $lotNumber): float
    {
        $entries = $this->getEntriesInLot($lotNumber);
        
        return $entries->sum(function (Consumable $consumable) {
            return max(0, $consumable->total_quantity - $consumable->consumed_quantity);
        });
    }

    /**
     * Determine if agricultural seed lot is completely depleted of available inventory.
     * 
     * Evaluates lot depletion status by checking if total available quantity
     * has reached zero, indicating the lot can no longer support agricultural
     * production and requires replacement or lot reassignment for affected recipes.
     * 
     * @depletion_check Identifies lots with zero available inventory
     * @agricultural_planning Critical for production scheduling and lot management
     * @inventory_status Determines lot viability for ongoing agricultural operations
     * @production_continuity Prevents planning with unavailable seed lots
     * 
     * @param string $lotNumber Agricultural seed lot identifier to evaluate
     * @return bool True if lot is completely depleted, false if inventory remains
     * 
     * @example
     * if ($this->isLotDepleted('LOT2024-PEA-003')) {
     *     // Need to reassign recipes to different lot or order new seed
     *     $this->reassignRecipesToNewLot($oldLot, $newLot);
     * }
     */
    public function isLotDepleted(string $lotNumber): bool
    {
        $totalQuantity = $this->getLotQuantity($lotNumber);
        
        return $totalQuantity <= 0;
    }

    /**
     * Retrieve oldest seed entry in lot for FIFO agricultural inventory consumption.
     * 
     * Identifies the oldest seed inventory entry within a lot based on creation date,
     * supporting First-In-First-Out inventory principles essential for seed quality
     * management and preventing deterioration through proper rotation practices.
     * 
     * @fifo_operations Supports proper agricultural inventory rotation principles
     * @seed_quality Ensures oldest seeds are consumed first to maintain freshness
     * @inventory_rotation Prevents seed deterioration through systematic consumption
     * @agricultural_standards Maintains quality control through proper stock rotation
     * 
     * @param string $lotNumber Agricultural seed lot identifier to search
     * @return Consumable|null Oldest available seed entry or null if lot empty
     * 
     * @fifo_criteria
     * - Targets seed consumable type only
     * - Filters to active inventory entries
     * - Requires available quantity (not fully consumed)
     * - Orders by creation date ascending (oldest first)
     * 
     * @example
     * $oldestEntry = $this->getOldestEntryInLot('LOT2024-SUNFLOWER-002');
     * if ($oldestEntry) {
     *     // Consume from oldest entry first for FIFO compliance
     *     $this->consumeFromEntry($oldestEntry, $requiredQuantity);
     * }
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
            ->whereRaw('(total_quantity - consumed_quantity) > 0') // Only entries with available seed stock
            ->orderBy('created_at', 'asc') // FIFO ordering: oldest entries first
            ->first();
    }

    /**
     * Retrieve all seed inventory entries in lot ordered by agricultural FIFO sequence.
     * 
     * Returns complete collection of seed entries within specified lot, ordered
     * chronologically from oldest to newest to support FIFO consumption patterns
     * and comprehensive lot analysis for agricultural inventory management.
     * 
     * @lot_inventory Complete collection of seed entries in chronological order
     * @fifo_sequence Ordered oldest-to-newest for proper consumption planning
     * @agricultural_analysis Supports comprehensive lot composition understanding
     * @inventory_visibility Provides complete lot entry overview for management
     * 
     * @param string $lotNumber Agricultural seed lot identifier to retrieve
     * @return Collection<Consumable> Chronologically ordered seed entries in lot
     * 
     * @entry_criteria
     * - Seed consumable type entries only
     * - Active inventory entries (not deleted)
     * - Ordered by creation date (oldest first)
     * - Includes both available and consumed entries
     * 
     * @example
     * $entries = $this->getEntriesInLot('LOT2024-RADISH-001');
     * foreach ($entries as $entry) {
     *     echo "Entry {$entry->id}: {$entry->available_quantity}g available";
     * }
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
            ->orderBy('created_at', 'asc') // FIFO sequence: chronological order
            ->get();
    }

    /**
     * Generate comprehensive agricultural seed lot summary for inventory analysis.
     * 
     * Compiles detailed lot statistics including quantity totals, consumption metrics,
     * availability analysis, entry composition, and temporal distribution. Essential
     * for agricultural inventory reporting, lot performance analysis, and production
     * planning decisions.
     * 
     * @lot_analytics Comprehensive statistical analysis of seed lot composition
     * @agricultural_reporting Detailed metrics for inventory management reporting
     * @inventory_intelligence Supports data-driven agricultural planning decisions
     * @consumption_analysis Tracks lot utilization patterns and efficiency
     * 
     * @param string $lotNumber Agricultural seed lot identifier to analyze
     * @return array Comprehensive lot summary with detailed metrics
     * 
     * @summary_structure
     * [
     *   'total' => float,              // Total seed quantity across all entries (grams)
     *   'consumed' => float,           // Total consumed quantity (grams)
     *   'available' => float,          // Remaining available quantity (grams)
     *   'entry_count' => int,          // Number of individual inventory entries
     *   'oldest_entry_date' => Carbon, // Date of first entry in lot
     *   'newest_entry_date' => Carbon  // Date of most recent entry in lot
     * ]
     * 
     * @example
     * $summary = $this->getLotSummary('LOT2024-KALE-005');
     * $utilizationRate = ($summary['consumed'] / $summary['total']) * 100;
     * echo "Lot utilization: {$utilizationRate}% consumed";
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
     * Retrieve all unique agricultural seed lot identifiers in inventory system.
     * 
     * Compiles complete list of distinct seed lot numbers across all active
     * inventory entries, providing comprehensive lot portfolio visibility for
     * agricultural inventory management and production planning operations.
     * 
     * @lot_portfolio Complete inventory of all seed lots in agricultural system
     * @agricultural_catalog Comprehensive lot identification for planning reference
     * @inventory_scope Provides complete picture of available seed lot options
     * @production_reference Supports lot selection for recipe and crop planning
     * 
     * @return Collection<string> Sorted collection of unique seed lot identifiers
     * 
     * @filtering_criteria
     * - Seed consumable type entries only
     * - Active inventory entries (not deleted)
     * - Non-null lot number assignments
     * - Deduplicated and sorted alphabetically
     * 
     * @example
     * $allLots = $this->getAllLotNumbers();
     * foreach ($allLots as $lotNumber) {
     *     $summary = $this->getLotSummary($lotNumber);
     *     echo "Lot {$lotNumber}: {$summary['available']}g available";
     * }
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
     * Identify agricultural seed lots approaching depletion below threshold percentage.
     * 
     * Analyzes all seed lots to identify those with available inventory below
     * specified percentage threshold, enabling proactive seed procurement and
     * lot management before critical shortages impact agricultural production.
     * 
     * @low_stock_detection Early warning system for seed inventory depletion
     * @proactive_management Enables preventive action before critical shortages
     * @agricultural_planning Supports strategic seed procurement decisions
     * @threshold_analysis Configurable sensitivity for low stock identification
     * 
     * @param float $thresholdPercentage Inventory percentage below which lots are considered low stock (default 10%)
     * @return Collection<array> Low stock lot summaries sorted by available percentage
     * 
     * @low_stock_structure Each lot summary includes:
     * - Standard lot summary fields (total, consumed, available, etc.)
     * - lot_number: Lot identifier
     * - available_percentage: Current availability as percentage of total
     * 
     * @example
     * $lowStockLots = $this->getLowStockLots(15.0); // 15% threshold
     * foreach ($lowStockLots as $lotSummary) {
     *     echo "ALERT: Lot {$lotSummary['lot_number']} at {$lotSummary['available_percentage']}%";
     *     // Consider reordering seed for this lot
     * }
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
     * Validate existence of agricultural seed lot in inventory system.
     * 
     * Verifies whether specified lot number exists in active seed inventory,
     * supporting lot validation for recipe assignment, production planning,
     * and inventory management operations requiring lot existence confirmation.
     * 
     * @lot_validation Confirms lot exists before assignment or planning operations
     * @agricultural_integrity Prevents planning with non-existent seed lots
     * @inventory_verification Validates lot identifiers for system operations
     * @data_consistency Ensures lot references are valid before use
     * 
     * @param string $lotNumber Agricultural seed lot identifier to validate
     * @return bool True if lot exists in active seed inventory, false otherwise
     * 
     * @validation_criteria
     * - Must be seed consumable type
     * - Must be active (not deleted)
     * - Case-insensitive lot number matching
     * 
     * @example
     * if ($this->lotExists('LOT2024-MICROGREEN-MIX-001')) {
     *     // Safe to assign this lot to recipes
     *     $recipe->assignToLot('LOT2024-MICROGREEN-MIX-001');
     * } else {
     *     // Handle invalid lot assignment attempt
     *     throw new InvalidLotException('Lot does not exist');
     * }
     */
    public function lotExists(string $lotNumber): bool
    {
        $seedTypeId = $this->getSeedConsumableTypeId();
        if (!$seedTypeId) {
            return false;
        }

        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', strtoupper($lotNumber)) // Case-insensitive lot matching
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Determine next available seed quantity for FIFO agricultural consumption.
     * 
     * Identifies oldest available seed entry in lot and evaluates fulfillment
     * capacity for requested consumption quantity, supporting proper FIFO
     * inventory rotation and agricultural production resource allocation.
     * 
     * @fifo_consumption Implements proper oldest-first consumption sequence
     * @availability_analysis Evaluates fulfillment capacity for production needs
     * @agricultural_rotation Supports proper seed inventory rotation practices
     * @production_planning Enables accurate resource allocation for crop production
     * 
     * @param string $lotNumber Agricultural seed lot identifier for consumption
     * @param float $requestedQuantity Seed quantity needed for agricultural production (grams)
     * @return array FIFO consumption analysis with availability and fulfillment details
     * 
     * @consumption_response
     * [
     *   'consumable' => Consumable|null,  // Oldest available seed entry
     *   'available_quantity' => float,    // Quantity available from oldest entry
     *   'can_fulfill' => bool             // Whether oldest entry can fulfill full request
     * ]
     * 
     * @example
     * $consumption = $this->getNextAvailableQuantity('LOT2024-BASIL-001', 250.0);
     * if ($consumption['can_fulfill']) {
     *     // Can fulfill from single entry (optimal FIFO)
     *     $this->consumeFromEntry($consumption['consumable'], 250.0);
     * } else {
     *     // Need to consume from multiple entries
     *     $this->consumeAcrossMultipleEntries($lotNumber, 250.0);
     * }
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
     * Record agricultural lot inventory operation for audit trail and debugging.
     * 
     * Logs lot-specific inventory operations with contextual information for
     * agricultural traceability, debugging, and operational analysis. Maintains
     * audit trail of lot manipulations and inventory management activities.
     * 
     * @audit_logging Maintains detailed record of lot inventory operations
     * @agricultural_traceability Supports compliance and inventory tracking
     * @debugging_support Provides operation history for troubleshooting
     * @internal Utility method for operation documentation
     * 
     * @param string $operation Agricultural operation being performed on lot
     * @param string $lotNumber Seed lot identifier affected by operation
     * @param array $context Additional operational context and metadata
     * @return void Operation logged to system audit trail
     */
    protected function logLotOperation(string $operation, string $lotNumber, array $context = []): void
    {
        Log::info("Lot inventory operation: {$operation}", array_merge([
            'lot_number' => $lotNumber,
            'operation' => $operation,
        ], $context));
    }
}