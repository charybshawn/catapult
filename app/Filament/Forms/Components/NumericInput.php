<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;

class NumericInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->numeric()
            ->lazy() // Use lazy evaluation by default
            ->debounce(600); // Longer debounce for better typing experience
    }
    
    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }
}