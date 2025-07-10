<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Repeater;

class CompactRepeater extends Repeater
{
    protected string $view = 'forms.components.compact-repeater';
    
    protected bool $isCompact = true;
    
    protected bool $isFlat = true;
    
    protected array $columnWidths = [];
    
    public function compact(bool $condition = true): static
    {
        $this->isCompact = $condition;
        
        return $this;
    }
    
    public function isCompact(): bool
    {
        return $this->isCompact;
    }
    
    public function columnWidths(array $widths): static
    {
        $this->columnWidths = $widths;
        
        return $this;
    }
    
    public function getColumnWidths(): array
    {
        return $this->columnWidths;
    }
    
    public function flat(bool $condition = true): static
    {
        $this->isFlat = $condition;
        
        return $this;
    }
    
    public function isFlat(): bool
    {
        return $this->isFlat;
    }
}