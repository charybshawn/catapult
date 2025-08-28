<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Concerns\CanBeValidated;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Forms\Components\Concerns\HasHelperText;
use Filament\Forms\Components\Concerns\HasHint;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Schemas\Components\Concerns\HasState;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns;

/**
 * Seed Variations Form Component
 * 
 * Specialized Filament form component for managing seed catalog variations including
 * different package sizes, weights, and pricing from agricultural suppliers.
 * Handles complex seed catalog data with multi-currency pricing and availability.
 * 
 * @filament_component Multi-row field for seed variation management
 * @agricultural_use Seed catalog entry management with multiple package sizes and suppliers
 * @business_context Manages seed purchasing data including weights, SKUs, and pricing
 * @supplier_integration Handles seed supplier variations with different package offerings
 * 
 * Key features:
 * - Multiple seed package size management (grams, ounces, pounds)
 * - Multi-currency pricing support for international suppliers
 * - Availability tracking for seasonal seed offerings
 * - SKU management for supplier catalog integration
 * 
 * @package App\Forms\Components
 * @author Shawn
 * @since 2024
 */
class SeedVariations extends Field
{
    use CanBeValidated;
    use HasExtraAttributes;
    use HasHelperText;
    use HasHint;
    use HasLabel;
    use HasPlaceholder;
    use HasState;
    
    protected string $view = 'forms.components.seed-variations';
    
    protected array $defaultVariation = [];
    
    public function default(mixed $state): static
    {
        $this->defaultVariation = is_array($state) ? $state : [];
        
        return parent::default($state);
    }
    
    /**
     * Get default structure for new seed variations.
     * 
     * @agricultural_context Default seed variation with agricultural package fields
     * @return array Default variation structure for seed catalog entries
     * @structure Contains size, SKU, weight, pricing, currency, availability, and unit fields
     * @defaults Sets 'USD' currency, 'grams' unit, and available status
     */
    public function getDefaultVariation(): array
    {
        return array_merge([
            'size' => '',
            'sku' => '',
            'weight_kg' => null,
            'current_price' => 0,
            'currency' => 'USD',
            'is_available' => true,
            'unit' => 'grams',
        ], $this->defaultVariation);
    }
    
    /**
     * Get current seed variations state.
     * 
     * @agricultural_context Returns array of seed variations with pricing and availability
     * @return mixed Seed variations array, guaranteed to be array format
     * @data_structure Each variation contains size, weight, price, and availability data
     */
    public function getState(): mixed
    {
        $state = parent::getState();
        
        if (!is_array($state)) {
            return [];
        }
        
        return $state;
    }
    
    /**
     * Get data for the Blade view rendering.
     * 
     * @agricultural_context Provides default variation structure to seed variations view
     * @return array View data including default seed variation template
     * @view_integration Data passed to 'forms.components.seed-variations' Blade template
     */
    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'defaultVariation' => $this->getDefaultVariation(),
        ]);
    }
}