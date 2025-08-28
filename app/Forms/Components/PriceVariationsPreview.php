<?php

namespace App\Forms\Components;

use Filament\Schemas\Components\Concerns\HasState;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Forms\Components\Concerns\HasHelperText;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns;

/**
 * Price Variations Preview Component
 * 
 * Read-only Filament form component that displays agricultural product pricing
 * variations in a formatted preview. Used to show complex agricultural pricing
 * structures including package sizes, customer types, and quantity tiers.
 * 
 * @filament_component Read-only preview field for price variation display
 * @agricultural_use Display pricing for different agricultural product packages and customer types
 * @business_context Shows microgreens pricing across retail, wholesale, and bulk customer segments
 * @data_source Reads from 'priceVariations' relationship data in Livewire component
 * 
 * Key features:
 * - Read-only display (dehydrated false)
 * - Agricultural pricing structure visualization
 * - Integration with PriceVariation model data
 * - Responsive formatting for complex pricing tables
 * 
 * @package App\Forms\Components
 * @author Shawn
 * @since 2024
 */
class PriceVariationsPreview extends Field
{
    use HasState;
    use HasLabel;
    use HasHelperText;
    
    protected string $view = 'forms.components.price-variations-preview';
    
    /**
     * Configure the component as read-only preview.
     * 
     * @agricultural_context Set up as display-only component for pricing preview
     * @return void
     * @configuration Marks component as dehydrated(false) - not included in form submission
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dehydrated(false);
    }
    
    /**
     * Get price variation data from the parent Livewire component.
     * 
     * @agricultural_context Retrieves pricing data for agricultural products from form state
     * @return mixed Price variations array containing customer types, package sizes, and prices
     * @data_source Accesses 'priceVariations' from Livewire component data
     * @fallback Returns empty array if no price variations available
     */
    public function getState(): mixed
    {
        // Get the state from the priceVariations relationship data
        $livewire = $this->getLivewire();
        return $livewire->data['priceVariations'] ?? [];
    }
}
