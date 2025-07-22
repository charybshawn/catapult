<?php

namespace App\Actions\Recipe;

use App\Services\InventoryManagementService;

/**
 * Handle lot validation logic
 * 
 * Pure business logic extracted from RecipeResource
 */
class ValidateLotSelection
{
    public function __construct(
        protected InventoryManagementService $inventoryService
    ) {}
    
    /**
     * Validate lot selection with comprehensive checks
     */
    public function validate(?string $lotNumber): array
    {
        if (!$lotNumber) {
            return ['valid' => true, 'message' => null];
        }
        
        // Check if lot exists
        $existsResult = $this->validateLotExists($lotNumber);
        if (!$existsResult['valid']) {
            return $existsResult;
        }
        
        // Check if lot is depleted
        $depletedResult = $this->validateLotNotDepleted($lotNumber);
        if (!$depletedResult['valid']) {
            return $depletedResult;
        }
        
        // Check minimum quantity threshold
        $quantityResult = $this->validateMinimumQuantity($lotNumber);
        if (!$quantityResult['valid']) {
            return $quantityResult;
        }
        
        return ['valid' => true, 'message' => null];
    }
    
    /**
     * Validate that lot exists
     */
    protected function validateLotExists(string $lotNumber): array
    {
        if (!$this->inventoryService->lotExists($lotNumber)) {
            return [
                'valid' => false,
                'message' => "The selected lot '{$lotNumber}' does not exist."
            ];
        }
        
        return ['valid' => true, 'message' => null];
    }
    
    /**
     * Validate that lot is not depleted
     */
    protected function validateLotNotDepleted(string $lotNumber): array
    {
        if ($this->inventoryService->isLotDepleted($lotNumber)) {
            return [
                'valid' => false,
                'message' => "The selected lot '{$lotNumber}' is depleted and cannot be used."
            ];
        }
        
        return ['valid' => true, 'message' => null];
    }
    
    /**
     * Validate minimum quantity threshold
     */
    protected function validateMinimumQuantity(string $lotNumber, float $minimumRequired = 10.0): array
    {
        $availableQuantity = $this->inventoryService->getLotQuantity($lotNumber);
        
        if ($availableQuantity < $minimumRequired) {
            $formattedAvailable = round($availableQuantity, 1);
            return [
                'valid' => false,
                'message' => "The selected lot '{$lotNumber}' has insufficient stock ({$formattedAvailable}g available). Minimum {$minimumRequired}g required."
            ];
        }
        
        return ['valid' => true, 'message' => null];
    }
    
    /**
     * Get validation closure for Filament form rules
     */
    public function getValidationClosure(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $result = $this->validate($value);
            
            if (!$result['valid']) {
                $fail($result['message']);
            }
        };
    }
    
    /**
     * Check if lot has sufficient quantity for a specific requirement
     */
    public function hasSufficientQuantity(string $lotNumber, float $requiredQuantity): bool
    {
        $availableQuantity = $this->inventoryService->getLotQuantity($lotNumber);
        return $availableQuantity >= $requiredQuantity;
    }
    
    /**
     * Get lot quantity summary
     */
    public function getLotQuantitySummary(string $lotNumber): array
    {
        return [
            'available' => $this->inventoryService->getLotQuantity($lotNumber),
            'is_depleted' => $this->inventoryService->isLotDepleted($lotNumber),
            'exists' => $this->inventoryService->lotExists($lotNumber)
        ];
    }
}