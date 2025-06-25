<?php

namespace App\Http\Livewire;

use App\Models\Item;
use Livewire\Component;

class ItemPriceCalculator extends Component
{
    public $itemId;
    public $customerType = 'retail';
    public $quantity = 1;
    public $calculatedPrice = 0;
    
    protected $listeners = ['recalculatePrice.debounce.500ms' => 'recalculatePrice'];
    
    public function mount($itemId = null)
    {
        $this->itemId = $itemId;
        
        if ($this->itemId) {
            $this->calculatePrice();
        }
    }
    
    public function calculatePrice()
    {
        if (!$this->itemId) {
            $this->calculatedPrice = 0;
            return;
        }
        
        $item = Item::find($this->itemId);
        
        if ($item) {
            $this->calculatedPrice = $item->getPriceForCustomerType($this->customerType, $this->quantity);
        } else {
            $this->calculatedPrice = 0;
        }
    }
    
    public function updatedCustomerType()
    {
        $this->dispatch('recalculatePrice.debounce.500ms');
    }
    
    public function updatedQuantity()
    {
        $this->dispatch('recalculatePrice.debounce.500ms');
    }
    
    public function recalculatePrice($params)
    {
        if (isset($params['customerType'])) {
            $this->customerType = $params['customerType'];
        }
        
        if (isset($params['quantity'])) {
            $this->quantity = $params['quantity'];
        }
        
        $this->calculatePrice();
    }
    
    public function render()
    {
        return view('livewire.item-price-calculator');
    }
} 