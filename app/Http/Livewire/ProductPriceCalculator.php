<?php

namespace App\Http\Livewire;

use App\Models\Product;
use Livewire\Component;
use Filament\Forms\Components\ViewField;

class ProductPriceCalculator extends Component
{
    public $productId;
    public $customerType = 'retail';
    public $quantity = 1;
    public $calculatedPrice = 0;
    
    protected $listeners = ['recalculatePrice.debounce.500ms' => 'recalculatePrice'];
    
    public function mount($productId = null)
    {
        $this->productId = $productId;
        $this->calculatedPrice = 0;
        
        if ($this->productId) {
            $this->calculatePrice();
        }
    }
    
    public function calculatePrice()
    {
        if (!$this->productId) {
            $this->calculatedPrice = 0;
            return;
        }
        
        $product = Product::find($this->productId);
        
        if ($product) {
            $this->calculatedPrice = $product->getPriceForCustomerType($this->customerType, $this->quantity);
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
        return view('livewire.product-price-calculator');
    }
} 