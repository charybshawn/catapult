<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns;

class SeedVariations extends Field
{
    use Concerns\CanBeValidated;
    use Concerns\HasExtraAttributes;
    use Concerns\HasHelperText;
    use Concerns\HasHint;
    use Concerns\HasLabel;
    use Concerns\HasPlaceholder;
    use Concerns\HasState;
    
    protected string $view = 'forms.components.seed-variations';
    
    protected array $defaultVariation = [];
    
    public function default(mixed $state): static
    {
        $this->defaultVariation = is_array($state) ? $state : [];
        
        return parent::default($state);
    }
    
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
            'defaultVariation' => $this->getDefaultVariation(),
        ]);
    }
}