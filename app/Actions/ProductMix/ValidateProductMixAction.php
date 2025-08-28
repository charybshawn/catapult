<?php

namespace App\Actions\ProductMix;

use InvalidArgumentException;
use App\Models\ProductMix;

/**
 * Business logic for validating ProductMix data and components
 */
class ValidateProductMixAction
{
    /**
     * Validate product mix data before creation/update
     */
    public function validate(array $data): array
    {
        $validatedData = $this->validateBasicData($data);
        
        if (isset($data['masterSeedCatalogs'])) {
            $validatedData['masterSeedCatalogs'] = $this->validateMixComponents($data['masterSeedCatalogs']);
        }
        
        return $validatedData;
    }

    /**
     * Validate basic product mix information
     */
    protected function validateBasicData(array $data): array
    {
        $validatedData = [];
        
        // Name validation
        if (isset($data['name'])) {
            $validatedData['name'] = trim($data['name']);
            if (empty($validatedData['name'])) {
                throw new InvalidArgumentException('Product mix name is required');
            }
        }
        
        // Description validation
        if (isset($data['description'])) {
            $validatedData['description'] = trim($data['description']);
        }
        
        // Active status validation
        if (isset($data['is_active'])) {
            $validatedData['is_active'] = (bool) $data['is_active'];
        }
        
        return $validatedData;
    }

    /**
     * Validate mix components and percentages
     */
    protected function validateMixComponents(array $components): array
    {
        if (empty($components)) {
            throw new InvalidArgumentException('At least one mix component is required');
        }
        
        $validatedComponents = [];
        $totalPercentage = 0;
        
        foreach ($components as $component) {
            $validatedComponent = $this->validateSingleComponent($component);
            $validatedComponents[] = $validatedComponent;
            $totalPercentage += $validatedComponent['percentage'];
        }
        
        // Validate total percentage
        $this->validateTotalPercentage($totalPercentage);
        
        return $validatedComponents;
    }

    /**
     * Validate a single mix component
     */
    protected function validateSingleComponent(array $component): array
    {
        $validatedComponent = [];
        
        // Master seed catalog ID validation
        if (!isset($component['master_seed_catalog_id']) || empty($component['master_seed_catalog_id'])) {
            throw new InvalidArgumentException('Master seed catalog ID is required for each component');
        }
        $validatedComponent['master_seed_catalog_id'] = (int) $component['master_seed_catalog_id'];
        
        // Cultivar validation
        if (!isset($component['cultivar']) || empty($component['cultivar'])) {
            throw new InvalidArgumentException('Cultivar is required for each component');
        }
        $validatedComponent['cultivar'] = trim($component['cultivar']);
        
        // Percentage validation
        if (!isset($component['percentage']) || !is_numeric($component['percentage'])) {
            throw new InvalidArgumentException('Valid percentage is required for each component');
        }
        
        $percentage = floatval($component['percentage']);
        if ($percentage <= 0 || $percentage > 100) {
            throw new InvalidArgumentException('Percentage must be between 0.01 and 100');
        }
        
        $validatedComponent['percentage'] = round($percentage, 2);
        
        // Recipe ID validation (optional)
        if (isset($component['recipe_id']) && !empty($component['recipe_id'])) {
            $validatedComponent['recipe_id'] = (int) $component['recipe_id'];
        } else {
            $validatedComponent['recipe_id'] = null;
        }
        
        return $validatedComponent;
    }

    /**
     * Validate that total percentage equals 100%
     */
    protected function validateTotalPercentage(float $totalPercentage): void
    {
        $rounded = round($totalPercentage, 2);
        
        if ($rounded !== 100.00) {
            throw new InvalidArgumentException(
                "Total percentage must equal 100%. Current total: {$rounded}%"
            );
        }
    }

    /**
     * Validate that a product mix can be safely deleted
     */
    public function validateForDeletion(ProductMix $productMix): void
    {
        if ($productMix->products()->count() > 0) {
            throw new InvalidArgumentException(
                'Cannot delete product mix that is used by products'
            );
        }
    }

    /**
     * Validate that all required data is present for mix component mutations
     */
    public function validateMixComponentMutation(array $data): array
    {
        // Remove the variety_selection field as it's not part of the database
        unset($data['variety_selection']);
        
        // Ensure we have the required fields
        if (!isset($data['master_seed_catalog_id']) || !isset($data['percentage'])) {
            throw new InvalidArgumentException('Missing required fields: master_seed_catalog_id and percentage');
        }
        
        // Ensure recipe_id is properly handled (can be null)
        if (!isset($data['recipe_id'])) {
            $data['recipe_id'] = null;
        }
        
        return $data;
    }

    /**
     * Prepare data for filling form with existing mix component data
     */
    public function prepareMixComponentForFill(array $data): array
    {
        // When loading existing data, create the composite key for the select
        if (isset($data['master_seed_catalog_id']) && isset($data['cultivar'])) {
            $data['variety_selection'] = $data['master_seed_catalog_id'] . '|' . $data['cultivar'];
        }
        
        // Ensure percentage is properly cast
        if (isset($data['percentage'])) {
            $data['percentage'] = floatval($data['percentage']);
        }
        
        // Ensure recipe_id is properly handled (can be null)
        if (isset($data['recipe_id'])) {
            $data['recipe_id'] = $data['recipe_id'] ? intval($data['recipe_id']) : null;
        }
        
        return $data;
    }
}