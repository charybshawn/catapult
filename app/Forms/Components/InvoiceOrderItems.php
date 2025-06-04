<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns;
use Illuminate\Contracts\Support\Arrayable;

class InvoiceOrderItems extends Field
{
    use Concerns\CanBeValidated;
    use Concerns\HasExtraAttributes;
    use Concerns\HasHelperText;
    use Concerns\HasHint;
    use Concerns\HasLabel;
    use Concerns\HasPlaceholder;
    use Concerns\HasState;
    
    protected string $view = 'forms.components.invoice-order-items';
    
    protected array $defaultItem = [];
    
    protected ?\Closure $productOptions = null;
    
    public function default(mixed $state): static
    {
        $this->defaultItem = is_array($state) ? $state : [];
        
        return parent::default($state);
    }
    
    public function productOptions(\Closure $callback): static
    {
        $this->productOptions = $callback;
        
        return $this;
    }
    
    public function getProductOptions(): array
    {
        if ($this->productOptions) {
            return ($this->productOptions)();
        }
        
        return \App\Models\Product::query()->pluck('name', 'id')->toArray();
    }
    
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
    
    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'productOptions' => $this->getProductOptions(),
            'defaultItem' => $this->getDefaultItem(),
        ]);
    }
}
