<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Concerns\CanBeValidated;
use Filament\Support\Concerns\HasExtraAttributes;
use Filament\Forms\Components\Concerns\HasHelperText;
use Filament\Forms\Components\Concerns\HasHint;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Schemas\Components\Concerns\HasState;
use Closure;
use App\Models\Product;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Invoice Order Items Form Component
 * 
 * Specialized Filament form component for managing agricultural product order items
 * within invoice creation and editing workflows. Handles complex agricultural pricing
 * including variety-specific price variations and quantity-based pricing tiers.
 * 
 * @filament_component Custom field for invoice order item management
 * @agricultural_use Invoice creation for agricultural product orders with variety pricing
 * @business_context Supports microgreens pricing with package size variations and customer types
 * @pricing_support Handles PriceVariation relationships for agricultural product pricing
 * 
 * Key features:
 * - Agricultural product selection with variety-aware pricing
 * - Price variation support for different package sizes and customer types
 * - Quantity-based pricing calculations for bulk agricultural orders
 * - Integration with Product and PriceVariation models
 * 
 * @package App\Forms\Components
 * @author Shawn
 * @since 2024
 */
class InvoiceOrderItems extends Field
{
    use CanBeValidated;
    use HasExtraAttributes;
    use HasHelperText;
    use HasHint;
    use HasLabel;
    use HasPlaceholder;
    use HasState;
    
    protected string $view = 'forms.components.invoice-order-items';
    
    protected array $defaultItem = [];
    
    protected ?Closure $productOptions = null;
    
    public function default(mixed $state): static
    {
        $this->defaultItem = is_array($state) ? $state : [];
        
        return parent::default($state);
    }
    
    /**
     * Set custom callback for product options loading.
     * 
     * @agricultural_context Allows filtering products by category (seeds, microgreens, mixes)
     * @param Closure $callback Function returning product options array
     * @return static Fluent interface for method chaining
     * @callback_signature function(): array ['product_id' => 'product_name']
     */
    public function productOptions(Closure $callback): static
    {
        $this->productOptions = $callback;
        
        return $this;
    }
    
    /**
     * Get available product options for order item selection.
     * 
     * @agricultural_context Returns active agricultural products (seeds, microgreens, product mixes)
     * @return array Product options as ['id' => 'name'] for dropdown selection
     * @default_behavior Loads all active products if no custom callback provided
     */
    public function getProductOptions(): array
    {
        if ($this->productOptions) {
            return ($this->productOptions)();
        }
        
        return Product::query()->pluck('name', 'id')->toArray();
    }
    
    /**
     * Get default structure for new order items.
     * 
     * @agricultural_context Default order item structure for agricultural products
     * @return array Default item with agricultural product fields
     * @structure Contains item_id (Product), price_variation_id (packaging/customer), quantity, price
     */
    public function getDefaultItem(): array
    {
        return array_merge([
            'item_id' => null,
            'price_variation_id' => null,
            'quantity' => 1,
            'price' => 0,
        ], $this->defaultItem);
    }
    
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
     * @agricultural_context Provides product options and default item structure to view
     * @return array View data including product options and default agricultural item structure
     * @view_integration Data passed to 'forms.components.invoice-order-items' Blade template
     */
    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'productOptions' => $this->getProductOptions(),
            'defaultItem' => $this->getDefaultItem(),
        ]);
    }
}
