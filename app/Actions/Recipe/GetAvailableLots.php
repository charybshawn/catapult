<?php

namespace App\Actions\Recipe;

use App\Models\Consumable;
use App\Services\InventoryManagementService;

/**
 * Handle lot inventory queries and formatting
 * 
 * Pure business logic extracted from RecipeResource
 */
class GetAvailableLots
{
    public function __construct(
        protected InventoryManagementService $inventoryService
    ) {}
    
    /**
     * Get available lots for selection with formatted display names
     */
    public function execute(): array
    {
        $lotNumbers = $this->inventoryService->getAllLotNumbers();
        $options = [];
        
        foreach ($lotNumbers as $lotNumber) {
            $formattedOption = $this->formatLotOption($lotNumber);
            if ($formattedOption) {
                $options[$lotNumber] = $formattedOption;
            }
        }
        
        return $options;
    }
    
    /**
     * Get available lots for filters (includes depleted lots)
     */
    public function getForFilters(): array
    {
        $lotNumbers = $this->inventoryService->getAllLotNumbers();
        $options = [];
        
        foreach ($lotNumbers as $lotNumber) {
            $formattedOption = $this->formatLotOptionForFilter($lotNumber);
            if ($formattedOption) {
                $options[$lotNumber] = $formattedOption;
            }
        }
        
        return $options;
    }
    
    /**
     * Format lot option for selection (excludes depleted lots)
     */
    protected function formatLotOption(string $lotNumber): ?string
    {
        $summary = $this->inventoryService->getLotSummary($lotNumber);
        
        // Skip depleted lots
        if ($summary['available'] <= 0) {
            return null;
        }
        
        $consumable = $this->getConsumableForLot($lotNumber);
        if (!$consumable) {
            return null;
        }
        
        return $this->buildLotLabel($lotNumber, $summary['available'], $consumable);
    }
    
    /**
     * Format lot option for filters (includes depleted lots)
     */
    protected function formatLotOptionForFilter(string $lotNumber): ?string
    {
        $summary = $this->inventoryService->getLotSummary($lotNumber);
        $consumable = $this->getConsumableForLot($lotNumber);
        
        if (!$consumable) {
            return null;
        }
        
        $available = $summary['available'];
        $unit = $consumable->quantity_unit ?? 'g';
        $seedName = $consumable->name ?? 'Unknown';
        $status = $available > 0 ? "({$available}{$unit})" : "(Depleted)";
        
        return "{$lotNumber} {$status} - {$seedName}";
    }
    
    /**
     * Get consumable record for a lot
     */
    protected function getConsumableForLot(string $lotNumber): ?Consumable
    {
        $seedTypeId = $this->inventoryService->getSeedTypeId();
        if (!$seedTypeId) {
            return null;
        }
        
        return Consumable::where('consumable_type_id', $seedTypeId)
            ->where('lot_no', $lotNumber)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Build formatted lot label
     */
    protected function buildLotLabel(string $lotNumber, float $available, Consumable $consumable): string
    {
        $unit = $consumable->quantity_unit ?? 'g';
        $seedName = $consumable->name ?? 'Unknown Seed';
        
        // Format: "LOT123 (1500g available) - Broccoli (Broccoli)"
        return "{$lotNumber} ({$available}{$unit} available) - {$seedName}";
    }
    
    /**
     * Check if a lot exists in the system
     */
    public function lotExists(string $lotNumber): bool
    {
        return $this->inventoryService->lotExists($lotNumber);
    }
    
    /**
     * Check if a lot is depleted
     */
    public function isLotDepleted(string $lotNumber): bool
    {
        return $this->inventoryService->isLotDepleted($lotNumber);
    }
    
    /**
     * Get available quantity for a lot
     */
    public function getLotQuantity(string $lotNumber): float
    {
        return $this->inventoryService->getLotQuantity($lotNumber);
    }
}