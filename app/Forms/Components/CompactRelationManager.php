<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompactRelationManager extends Component
{
    use HasName;
    
    protected string $view = 'forms.components.compact-relation-manager';
    
    protected string | Closure | null $relationship = null;
    
    protected array | Closure $columns = [];
    
    protected array | Closure $options = [];
    
    protected string | Closure | null $optionLabel = null;
    
    protected string | Closure | null $optionValue = 'id';
    
    protected bool | Closure $showTotals = false;
    
    protected string | Closure | null $totalColumn = null;
    
    protected float | Closure | null $expectedTotal = null;
    
    protected string | Closure $addButtonLabel = 'Add Row';
    
    protected int | Closure $minItems = 0;
    
    protected int | Closure $maxItems = 999;
    
    public function relationship(string | Closure | null $relationship): static
    {
        $this->relationship = $relationship;
        
        return $this;
    }
    
    public function columns(array | Closure $columns): static
    {
        $this->columns = $columns;
        
        return $this;
    }
    
    public function options(array | Closure $options): static
    {
        $this->options = $options;
        
        return $this;
    }
    
    public function optionLabel(string | Closure | null $label): static
    {
        $this->optionLabel = $label;
        
        return $this;
    }
    
    public function optionValue(string | Closure $value): static
    {
        $this->optionValue = $value;
        
        return $this;
    }
    
    public function showTotals(bool | Closure $show = true): static
    {
        $this->showTotals = $show;
        
        return $this;
    }
    
    public function totalColumn(string | Closure | null $column): static
    {
        $this->totalColumn = $column;
        
        return $this;
    }
    
    public function expectedTotal(float | Closure | null $total): static
    {
        $this->expectedTotal = $total;
        
        return $this;
    }
    
    public function addButtonLabel(string | Closure $label): static
    {
        $this->addButtonLabel = $label;
        
        return $this;
    }
    
    public function minItems(int | Closure $min): static
    {
        $this->minItems = $min;
        
        return $this;
    }
    
    public function maxItems(int | Closure $max): static
    {
        $this->maxItems = $max;
        
        return $this;
    }
    
    public function getRelationship(): ?string
    {
        return $this->evaluate($this->relationship);
    }
    
    public function getColumns(): array
    {
        return $this->evaluate($this->columns);
    }
    
    public function getOptions(): array
    {
        return $this->evaluate($this->options);
    }
    
    public function getOptionLabel(): ?string
    {
        return $this->evaluate($this->optionLabel);
    }
    
    public function getOptionValue(): string
    {
        return $this->evaluate($this->optionValue);
    }
    
    public function getShowTotals(): bool
    {
        return $this->evaluate($this->showTotals);
    }
    
    public function getTotalColumn(): ?string
    {
        return $this->evaluate($this->totalColumn);
    }
    
    public function getExpectedTotal(): ?float
    {
        return $this->evaluate($this->expectedTotal);
    }
    
    public function getAddButtonLabel(): string
    {
        return $this->evaluate($this->addButtonLabel);
    }
    
    public function getMinItems(): int
    {
        return $this->evaluate($this->minItems);
    }
    
    public function getMaxItems(): int
    {
        return $this->evaluate($this->maxItems);
    }
    
    public function getState(): array
    {
        $state = parent::getState();
        
        if (!is_array($state)) {
            $state = [];
        }
        
        return $state;
    }
}