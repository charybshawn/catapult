<?php

namespace App\Forms\Components;

use Filament\Schemas\Components\Component;
use Closure;
use Filament\Forms\Components\Concerns\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Compact Relation Manager Component
 * 
 * Custom Filament form component for managing agricultural entity relationships
 * in a compact, table-like interface. Optimized for agricultural data entry
 * workflows where space efficiency and quick data validation are critical.
 * 
 * @filament_component Custom form field component
 * @agricultural_use Product mix components, crop variety selections, seed lot relationships
 * @ui_pattern Compact tabular interface with inline editing and totals validation
 * @validation Supports minimum/maximum item constraints and total validation
 * 
 * Common use cases:
 * - ProductMix variety components (percentages must total 100%)
 * - Order item selection with quantity validation
 * - Crop batch seed lot assignments
 * - Recipe stage component management
 * 
 * @package App\Forms\Components
 * @author Shawn
 * @since 2024
 */
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
    
    /**
     * Set the Eloquent relationship name for data binding.
     * 
     * @agricultural_context Binds to relationships like 'productMixComponents', 'cropVarieties'
     * @param string|Closure|null $relationship Eloquent relationship method name
     * @return static Fluent interface for method chaining
     * @example ->relationship('productMixComponents') for ProductMix seed variety components
     */
    public function relationship(string | Closure | null $relationship): static
    {
        $this->relationship = $relationship;
        
        return $this;
    }
    
    /**
     * Configure the table columns for the compact interface.
     * 
     * @agricultural_context Define columns for agricultural data like 'variety_name', 'percentage', 'grams_per_oz'
     * @param array|Closure $columns Column definitions with labels and field types
     * @return static Fluent interface for method chaining
     * @example ->columns(['variety' => 'Seed Variety', 'percentage' => 'Mix %', 'weight' => 'Grams'])
     */
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
    
    /**
     * Enable total calculation and validation display.
     * 
     * @agricultural_use Critical for ProductMix percentages (must equal 100%), order quantities, seed weights
     * @param bool|Closure $show Whether to show and validate totals
     * @return static Fluent interface for method chaining
     * @validation When enabled, validates against expectedTotal value
     */
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
    
    /**
     * Set the expected total for validation.
     * 
     * @agricultural_context ProductMix percentages (100.0), recipe total grams, order quantities
     * @param float|Closure|null $total Expected total value for validation
     * @return static Fluent interface for method chaining
     * @validation Component shows error if actual total doesn't match expected
     * @example ->expectedTotal(100.0) for ProductMix percentage validation
     */
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
    
    /**
     * Get the current component state as array.
     * 
     * @agricultural_context Returns array of relationship data (varieties, components, items)
     * @return array Component state data, guaranteed to be array
     * @data_structure Each item contains relationship foreign keys and agricultural values
     */
    public function getState(): array
    {
        $state = parent::getState();
        
        if (!is_array($state)) {
            $state = [];
        }
        
        return $state;
    }
}