<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns;

class PriceVariationsPreview extends Field
{
    use Concerns\HasState;
    use Concerns\HasLabel;
    use Concerns\HasHelperText;
    
    protected string $view = 'forms.components.price-variations-preview';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dehydrated(false);
    }
    
    public function getState(): mixed
    {
        // Get the state from the priceVariations relationship data
        $livewire = $this->getLivewire();
        return $livewire->data['priceVariations'] ?? [];
    }
}
