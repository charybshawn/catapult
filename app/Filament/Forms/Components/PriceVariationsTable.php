<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class PriceVariationsTable extends Field
{
    protected string $view = 'filament.forms.components.price-variations-table';
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->default([]);
        $this->dehydrated(false);
    }
}